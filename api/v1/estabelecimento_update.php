<?php
/**
 * api/v1/estabelecimento_update.php
 * Endpoint para atualização de estabelecimentos.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['superadmin_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autorizado."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

function validarHex(string $cor): string {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) ? $cor : '#6C63FF';
}

$input = json_decode(file_get_contents('php://input'), true);
$id    = (int) ($input['id'] ?? 0);

if ($id <= 0 || empty($input['nome']) || empty($input['email_login'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID, nome e e-mail são obrigatórios."]);
    exit;
}

try {
    $pdo = DB::getInstance();
    
    // SQL Base
    $sql = "UPDATE estabelecimentos SET 
                nome = :nome, logo_url = :logo_url, email_login = :email,
                mikrotik_ip = :ip, mikrotik_port = :port, 
                mikrotik_api_user = :api_user, mikrotik_api_pass = :api_pass, mikrotik_profile = :profile,
                cor_primaria = :cor1, cor_secundaria = :cor2, cor_fundo1 = :fundo1, cor_fundo2 = :fundo2,
                boas_vindas = :boas_vindas,
                lgpd_nome_empresa = :lgpd_nome, lgpd_cnpj = :lgpd_cnpj, 
                lgpd_email_dpo = :lgpd_dpo, lgpd_url_politica = :lgpd_url, lgpd_texto_consentimento = :lgpd_texto";
    
    $params = [
        ':id'         => $id,
        ':nome'       => $input['nome'],
        ':logo_url'   => $input['logo_url'] ?? '',
        ':email'      => $input['email_login'],
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
    ];

    // Adiciona senha se fornecida
    if (!empty($input['password'])) {
        $sql .= ", password_hash = :hash";
        $params[':hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(["status" => "success"]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao atualizar estabelecimento."]);
}
