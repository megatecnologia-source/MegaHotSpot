<?php
/**
 * api/v1/router_test.php
 * Testa a conexão com o MikroTik de um estabelecimento.
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
require_once __DIR__ . '/router.php';

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT mikrotik_ip, mikrotik_port, mikrotik_api_user, mikrotik_api_pass FROM estabelecimentos WHERE id = ?");
    $stmt->execute([$id]);
    $est = $stmt->fetch();

    if (!$est || empty($est['mikrotik_ip'])) {
        echo json_encode(["status" => "error", "message" => "Dados MikroTik não configurados para este cliente."]);
        exit;
    }

    $router = new MikroTikRouter(
        $est['mikrotik_ip'],
        (int) $est['mikrotik_port'],
        $est['mikrotik_api_user'],
        $est['mikrotik_api_pass']
    );

    // Tenta uma operação simples de leitura via REST API
    // Usamos um caminho genérico que costuma existir: /ip/hotspot/user
    $ch = curl_init("https://{$est['mikrotik_ip']}:{$est['mikrotik_port']}/rest/ip/hotspot/user?.limit=1");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => "{$est['mikrotik_api_user']}:{$est['mikrotik_api_pass']}",
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 5
    ]);
    
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        echo json_encode(["status" => "success", "message" => "Conexão estabelecida com sucesso!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Falha na conexão. HTTP Code: $code. Verifique IP, Porta e Credenciais."]);
    }

} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Erro interno ao testar: " . $e->getMessage()]);
}
