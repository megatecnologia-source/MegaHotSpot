<?php
/**
 * api/v1/estabelecimento_toggle.php
 * Alterna o status (ativo/inativo) de um estabelecimento.
 */
require_once __DIR__ . '/../../config/security.php';
secure_session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['superadmin_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autorizado."]);
    exit;
}

// Validação CSRF
validate_csrf_token();

require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int) ($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("UPDATE estabelecimentos SET ativo = NOT ativo WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    echo json_encode(["status" => "success"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
