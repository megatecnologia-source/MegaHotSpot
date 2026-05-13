<?php
/**
 * api/v1/pending_users.php
 * Retorna uma lista de usuários (leads) com status 'pending' para o MikroTik.
 */
require_once __DIR__ . '/../../config/security.php';
// Nota: Aqui não usamos secure_session_start() pois o MikroTik não usa cookies de sessão.
// A autenticação é via X-Auth-Token.

header('Content-Type: application/json');

$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';

if (empty($token)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token ausente."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = DB::getInstance();

    // 1. Validar Token e pegar Estabelecimento
    $stmtEstab = $pdo->prepare("SELECT id, mikrotik_profile FROM estabelecimentos WHERE cliente_token = ? AND ativo = 1");
    $stmtEstab->execute([$token]);
    $estab = $stmtEstab->fetch();

    if (!$estab) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido ou inativo."]);
        exit;
    }

    // 2. Buscar leads pendentes
    $stmtLeads = $pdo->prepare("
        SELECT cpf, senha_hotspot 
        FROM leads 
        WHERE estabelecimento_id = ? AND mikrotik_sync_status = 'pending'
        LIMIT 20
    ");
    $stmtLeads->execute([$estab['id']]);
    $leads = $stmtLeads->fetchAll();

    $usuarios = [];
    foreach ($leads as $l) {
        $usuarios[] = [
            "username" => preg_replace('/\D/', '', $l['cpf']),
            "password" => $l['senha_hotspot'],
            "profile"  => $estab['mikrotik_profile']
        ];
    }

    echo json_encode([
        "status" => "success",
        "total" => count($usuarios),
        "usuarios" => $usuarios
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
