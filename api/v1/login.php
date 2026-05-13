<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$email = $data['email'] ?? $_POST['email'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Preencha todos os campos."]);
    exit;
}

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM estabelecimentos WHERE email_login = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $estabelecimento = $stmt->fetch();

    if ($estabelecimento && password_verify($password, $estabelecimento['password_hash'])) {
        // Sucesso na verificação de senha
        $_SESSION['estabelecimento_id'] = $estabelecimento['id'];

        // Regenera a sessão para evitar fixação de sessão (Security Best Practice)
        session_regenerate_id(true);

        echo json_encode(["status" => "success"]);
    } else {
        // Falha (Nunca especifique se o que falhou foi o email ou a senha)
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "E-mail ou senha incorretos."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);
}
