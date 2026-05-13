<?php
/**
 * api/v1/confirm_sync.php
 * Chamado pelo Scheduler do MikroTik após criar os usuários localmente.
 * Marca leads como 'synced' no banco central.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error"]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if (empty($token)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token ausente."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usernames = $input['usernames'] ?? [];

if (empty($usernames) || !is_array($usernames)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Campo 'usernames' obrigatório (array)."]);
    exit;
}

try {
    $pdo = DB::getInstance();

    // Valida token
    $stmtEst = $pdo->prepare("SELECT id FROM estabelecimentos WHERE cliente_token = :token AND ativo = 1 LIMIT 1");
    $stmtEst->execute([':token' => $token]);
    $est = $stmtEst->fetch();

    if (!$est) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido."]);
        exit;
    }

    // Monta CPFs formatados a partir dos usernames (números puros)
    // O banco guarda CPF formatado (999.999.999-99), username é só números
    $updated = 0;
    foreach ($usernames as $username) {
        $username = preg_replace('/\D/', '', $username); // Garante que é só números
        if (strlen($username) !== 11) continue;

        // Formata no padrão do banco
        $cpf = substr($username, 0, 3) . '.' . substr($username, 3, 3) . '.' 
             . substr($username, 6, 3) . '-' . substr($username, 9, 2);

        $stmt = $pdo->prepare("
            UPDATE leads
            SET mikrotik_sync_status = 'synced', mikrotik_synced_at = NOW()
            WHERE estabelecimento_id = :estab_id
              AND cpf = :cpf
              AND mikrotik_sync_status = 'pending'
        ");
        $stmt->execute([':estab_id' => $est['id'], ':cpf' => $cpf]);
        $updated += $stmt->rowCount();
    }

    echo json_encode(["status" => "success", "synced" => $updated]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
