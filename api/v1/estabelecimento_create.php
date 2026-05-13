<?php
/**
 * api/v1/estabelecimento_create.php
 * Endpoint para criação de estabelecimentos.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['superadmin_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autorizado."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

function uuid4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function validarHex(string $cor): string {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) ? $cor : '#6C63FF';
}

$input = json_decode(file_get_contents('php://input'), true);

// Validação básica
if (empty($input['nome']) || empty($input['email_login']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Nome, e-mail e senha são obrigatórios."]);
    exit;
}

if ($input['password'] !== $input['password_confirm']) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "As senhas não conferem."]);
    exit;
}

try {
    $pdo = DB::getInstance();
    
    // Verifica e-mail duplicado
    $check = $pdo->prepare("SELECT id FROM estabelecimentos WHERE email_login = ?");
    $check->execute([$input['email_login']]);
    if ($check->fetch()) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "E-mail já cadastrado."]);
        exit;
    }

    $token = uuid4();
    $hash  = password_hash($input['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO estabelecimentos (
            cliente_token, nome, logo_url, email_login, password_hash,
            mikrotik_ip, mikrotik_port, mikrotik_api_user, mikrotik_api_pass, mikrotik_profile,
            cor_primaria, cor_secundaria, cor_fundo1, cor_fundo2, boas_vindas,
            lgpd_nome_empresa, lgpd_cnpj, lgpd_email_dpo, lgpd_url_politica, lgpd_texto_consentimento
        ) VALUES (
            :token, :nome, :logo_url, :email, :hash,
            :ip, :port, :api_user, :api_pass, :profile,
            :cor1, :cor2, :fundo1, :fundo2, :boas_vindas,
            :lgpd_nome, :lgpd_cnpj, :lgpd_dpo, :lgpd_url, :lgpd_texto
        )
    ");

    $stmt->execute([
        ':token'      => $token,
        ':nome'       => $input['nome'],
        ':logo_url'   => $input['logo_url'] ?? '',
        ':email'      => $input['email_login'],
        ':hash'       => $hash,
        ':ip'         => $input['mikrotik_ip'] ?? '',
        ':port'       => (int) ($input['mikrotik_port'] ?? 443),
        ':api_user'   => $input['mikrotik_api_user'] ?? '',
        ':api_pass'   => $input['mikrotik_api_pass'] ?? '',
        ':profile'    => $input['mikrotik_profile'] ?? 'hotspot-guest',
        ':cor1'       => validarHex($input['cor_primaria'] ?? ''),
        ':cor2'       => validarHex($input['cor_secundaria'] ?? ''),
        ':fundo1'     => validarHex($input['cor_fundo1'] ?? ''),
        ':fundo2'     => validarHex($input['cor_fundo2'] ?? ''),
        ':boas_vindas' => $input['boas_vindas'] ?? '',
        ':lgpd_nome'  => $input['lgpd_nome_empresa'] ?? '',
        ':lgpd_cnpj'  => $input['lgpd_cnpj'] ?? '',
        ':lgpd_dpo'   => $input['lgpd_email_dpo'] ?? '',
        ':lgpd_url'   => $input['lgpd_url_politica'] ?? '',
        ':lgpd_texto' => $input['lgpd_texto_consentimento'] ?? ''
    ]);

    echo json_encode(["status" => "success", "token" => $token, "id" => $pdo->lastInsertId()]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao criar estabelecimento."]);
}
