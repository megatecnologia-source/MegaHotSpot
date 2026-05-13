<?php
/**
 * api/v1/superlogin.php
 * Endpoint de autenticação para o superadmin.
 */
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$data  = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? $_POST['email'] ?? '';
$pass  = $data['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($pass)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Preencha todos os campos."]);
    exit;
}

try {
    $pdo  = DB::getInstance();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM superadmins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $row  = $stmt->fetch();

    if ($row && password_verify($pass, $row['password_hash'])) {
        $_SESSION['superadmin_id'] = $row['id'];
        session_regenerate_id(true);
        echo json_encode(["status" => "success"]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Credenciais inválidas."]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);

}
