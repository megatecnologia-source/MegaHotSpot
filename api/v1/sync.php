<?php
/**
 * api/v1/sync.php
 * Recebe o lead do portal captivo, salva no banco e sincroniza com o MikroTik.
 */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// CORS básico - Ajustar conforme domínio real em produção
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Aceita apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed. Only POST is accepted."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Captura o JSON do body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload."]);
    exit;
}

// Extrai o Token (prioriza Header)
$token = null;
if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'];
} elseif (isset($input['token'])) {
    $token = $input['token'];
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Token is missing."]);
    exit;
}

try {
    $pdo = DB::getInstance();

    // Valida o Token e recupera o Estabelecimento e dados MikroTik
    $stmt = $pdo->prepare("
        SELECT id, mikrotik_ip, mikrotik_port, mikrotik_api_user, mikrotik_api_pass, mikrotik_profile 
        FROM estabelecimentos 
        WHERE cliente_token = :token LIMIT 1
    ");
    $stmt->execute([':token' => $token]);
    $estabelecimento = $stmt->fetch();

    if (!$estabelecimento) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Forbidden. Invalid token."]);
        exit;
    }

    $estabelecimentoId = $estabelecimento['id'];

    // Extrai os campos do Lead
    $nome           = $input['nome'] ?? null;
    $cpf            = $input['cpf'] ?? null;
    $whatsapp       = $input['whatsapp'] ?? null;
    $email          = $input['email'] ?? null;
    $mac            = $input['mac'] ?? null;
    $senhaHotspot   = $input['senha_hotspot'] ?? null;

    // Validação — obrigatórios
    if (empty($nome) || empty($cpf) || empty($whatsapp) || empty($senhaHotspot)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Campos obrigatórios ausentes: nome, cpf, whatsapp, senha_hotspot."]);
        exit;
    }

    // 1. UPSERT Lead no Banco de Dados
    $upsertStmt = $pdo->prepare("
        INSERT INTO leads 
            (estabelecimento_id, cpf, nome, whatsapp, email, mac_address)
        VALUES 
            (:estabelecimento_id, :cpf, :nome, :whatsapp, :email, :mac_address)
        ON DUPLICATE KEY UPDATE
            nome          = VALUES(nome),
            whatsapp      = VALUES(whatsapp),
            email         = VALUES(email),
            mac_address   = VALUES(mac_address)
    ");

    $upsertStmt->execute([
        ':estabelecimento_id' => $estabelecimentoId,
        ':cpf'               => $cpf,
        ':nome'              => $nome,
        ':whatsapp'          => $whatsapp,
        ':email'             => $email,
        ':mac_address'       => $mac
    ]);

    // 2. Gravar senha_hotspot para sincronização posterior (modelo Pull)
    // O MikroTik consultará /api/v1/pending_users.php para buscar os pendentes.
    $stmtSenha = $pdo->prepare("
        UPDATE leads SET senha_hotspot = :senha, mikrotik_sync_status = 'pending'
        WHERE estabelecimento_id = :estab_id AND cpf = :cpf
    ");
    $stmtSenha->execute([
        ':senha'    => $senhaHotspot,
        ':estab_id' => $estabelecimentoId,
        ':cpf'      => $cpf
    ]);

    // 3. Tentativa imediata de push (funciona se o MikroTik tiver IP público configurado)
    $hotspotCreated = false;
    if (!empty($estabelecimento['mikrotik_ip'])) {
        require_once __DIR__ . '/router.php';
        try {
            $router = new MikroTikRouter(
                $estabelecimento['mikrotik_ip'],
                (int) $estabelecimento['mikrotik_port'],
                $estabelecimento['mikrotik_api_user'],
                $estabelecimento['mikrotik_api_pass']
            );
            $cpfLimpo = preg_replace('/\D/', '', $cpf);
            $result = $router->criarOuAtualizarUsuario($cpfLimpo, $senhaHotspot, $estabelecimento['mikrotik_profile']);
            
            if ($result['ok']) {
                // Push direto funcionou: marcar como synced imediatamente
                $hotspotCreated = true;
                $stmtSynced = $pdo->prepare("
                    UPDATE leads SET mikrotik_sync_status = 'synced', mikrotik_synced_at = NOW()
                    WHERE estabelecimento_id = :estab_id AND cpf = :cpf
                ");
                $stmtSynced->execute([':estab_id' => $estabelecimentoId, ':cpf' => $cpf]);
            }
            // Se falhar, o status permanece 'pending' e o Scheduler vai resolver
        } catch (Throwable $routerEx) {
            // Silenciosamente ignora — lead já está salvo como pending
        }
    }

    // 4. Retorna Sucesso (201)
    // O portal já pode redirecionar para o login direto no MikroTik.
    // O usuário pode não existir ainda se for CGNAT, mas o Scheduler sync em até 30s.
    http_response_code(201);
    echo json_encode([
        "status"               => "success",
        "hotspot_user_created" => $hotspotCreated,
        "sync_mode"            => $hotspotCreated ? "push" : "pull_pending"
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);
    // Log interno do erro (opcional, pode usar o logging do MikroTikRouter se quiser estender)
    exit;
}
