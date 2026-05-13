<?php
/**
 * api/v1/pending_users.php
 * Consultado pelo Scheduler do MikroTik (a cada 30s).
 * Retorna lista de leads pendentes de sincronização.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error"]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Auth via token no header
$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if (empty($token)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token ausente."]);
    exit;
}

try {
    $pdo = DB::getInstance();

    // Valida token e busca o profile do estabelecimento
    $stmtEst = $pdo->prepare("
        SELECT id, mikrotik_profile 
        FROM estabelecimentos 
        WHERE cliente_token = :token AND ativo = 1 
        LIMIT 1
    ");
    $stmtEst->execute([':token' => $token]);
    $est = $stmtEst->fetch();

    if (!$est) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido."]);
        exit;
    }

    // Busca leads pendentes (máx 50 por vez para não sobrecarregar o script)
    $stmt = $pdo->prepare("
        SELECT cpf, senha_hotspot
        FROM leads
        WHERE estabelecimento_id = :id
          AND mikrotik_sync_status = 'pending'
          AND senha_hotspot IS NOT NULL
        ORDER BY data_cadastro ASC
        LIMIT 50
    ");
    $stmt->execute([':id' => $est['id']]);
    $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata a resposta: CPF sem formatação (usado como username no MikroTik)
    $usuarios = array_map(function ($row) use ($est) {
        return [
            "username" => preg_replace('/\D/', '', $row['cpf']), // Apenas números
            "password" => $row['senha_hotspot'],
            "profile"  => $est['mikrotik_profile']
        ];
    }, $pendentes);

    echo json_encode([
        "status"  => "success",
        "total"   => count($usuarios),
        "usuarios" => $usuarios
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
