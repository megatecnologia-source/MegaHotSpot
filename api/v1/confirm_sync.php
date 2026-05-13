<?php
/**
 * api/v1/confirm_sync.php
 * Recebe uma lista de usernames do MikroTik e marca como 'synced' no banco.
 */
require_once __DIR__ . '/../../config/security.php';
header('Content-Type: application/json');

$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if (empty($token) || empty($input['usernames'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Dados inválidos."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = DB::getInstance();

    // 1. Validar Token
    $stmtEstab = $pdo->prepare("SELECT id FROM estabelecimentos WHERE cliente_token = ? AND ativo = 1");
    $stmtEstab->execute([$token]);
    $estab = $stmtEstab->fetch();

    if (!$estab) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Não autorizado."]);
        exit;
    }

    // 2. Atualizar status para synced em lote
    $placeholders = implode(',', array_fill(0, count($input['usernames']), '?'));
    $sql = "UPDATE leads SET mikrotik_sync_status = 'synced', mikrotik_synced_at = NOW() 
            WHERE estabelecimento_id = ? AND cpf IN ($placeholders)";
    
    // Como os usernames enviados pelo MikroTik são o CPF limpo, precisamos garantir que bate
    // O ideal seria que o MikroTik enviasse o CPF formatado ou que o banco tivesse uma coluna cpf_limpo.
    // Para simplificar, vamos assumir que o banco tem o CPF que bate com o enviado ou ajustar o SQL.
    
    // Ajuste: vamos remover a formatação do CPF no SQL da busca para bater com o username limpo do MikroTik
    $sql = "UPDATE leads SET mikrotik_sync_status = 'synced', mikrotik_synced_at = NOW() 
            WHERE estabelecimento_id = ? AND REPLACE(REPLACE(cpf, '.', ''), '-', '') IN ($placeholders)";

    $stmtUpdate = $pdo->prepare($sql);
    $params = array_merge([$estab['id']], $input['usernames']);
    $stmtUpdate->execute($params);

    echo json_encode(["status" => "success", "updated" => $stmtUpdate->rowCount()]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
