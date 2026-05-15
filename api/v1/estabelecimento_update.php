<?php
/**
 * api/v1/estabelecimento_update.php
 * Endpoint para atualização de estabelecimentos.
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

function validarHex(string $cor): string {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) ? $cor : '#6C63FF';
}

function uuid4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$input = $_POST;
$id    = (int) ($input['id'] ?? 0);

// Upload da Logo (se aplicável)
$logo_url = $input['logo_url'] ?? '';
if (($input['logo_type'] ?? 'url') === 'file' && isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['logo_file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $max_size = 2 * 1024 * 1024; // 2MB

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (in_array($mime, $allowed_types) && $file['size'] <= $max_size) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($mime === 'image/svg+xml') $ext = 'svg';
        $filename = uuid4() . '.' . $ext;
        $dest = __DIR__ . '/../../uploads/logos/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $logo_url = '/uploads/logos/' . $filename;
        }
    }
}
$input['logo_url'] = $logo_url;

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
