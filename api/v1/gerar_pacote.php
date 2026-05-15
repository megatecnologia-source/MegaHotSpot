<?php
/**
 * api/v1/gerar_pacote.php
 * Gera um arquivo ZIP personalizado para o MikroTik do cliente.
 */
require_once __DIR__ . '/../../config/security.php';
secure_session_start();

if (!isset($_SESSION['superadmin_id'])) {
    http_response_code(401);
    exit("Não autorizado.");
}

require_once __DIR__ . '/../../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) exit("ID inválido.");

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM estabelecimentos WHERE id = ?");
    $stmt->execute([$id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$est) exit("Estabelecimento não encontrado.");

    // 1. Gerar Bloco HOTSPOT_CONFIG
    $lgpdTexto = $est['lgpd_texto_consentimento'] 
        ?? 'Autorizo o uso dos meus dados conforme a LGPD — Lei 13.709/2018.';
    
    // Escapar aspas simples para o JS
    $nomeEscaped = addslashes($est['nome']);
    $lgpdTextoEscaped = addslashes($lgpdTexto);
    $boasVindasEscaped = addslashes($est['boas_vindas']);

    $logoUrl = $est['logo_url'];
    $isLocalLogo = false;
    $localLogoPath = '';
    $mikrotikLogoPath = '';

    if (strpos($logoUrl, '/uploads/logos/') === 0) {
        $isLocalLogo = true;
        $localLogoPath = __DIR__ . '/../..' . $logoUrl; 
        $ext = pathinfo($localLogoPath, PATHINFO_EXTENSION) ?: 'png';
        $mikrotikLogoPath = 'img/logo_cliente.' . $ext;
        
        // Caminho relativo dentro da pasta do MikroTik
        $logoUrl = $mikrotikLogoPath; 
    }

    $configBlock = <<<JS
const HOTSPOT_CONFIG = {
  apiUrl : "https://api.megatecnologias.com/api/v1/sync.php",
  token  : "{$est['cliente_token']}",
  nome          : "{$nomeEscaped}",
  logoUrl       : "{$logoUrl}",
  corPrimaria   : "{$est['cor_primaria']}",
  corSecundaria : "{$est['cor_secundaria']}",
  corFundo1     : "{$est['cor_fundo1']}",
  corFundo2     : "{$est['cor_fundo2']}",
  boasVindas    : "{$boasVindasEscaped}",
  lgpd: {
    nomeEmpresa : "{$est['lgpd_nome_empresa']}",
    cnpj        : "{$est['lgpd_cnpj']}",
    emailDpo    : "{$est['lgpd_email_dpo']}",
    urlPolitica : "{$est['lgpd_url_politica']}",
    textoConsentimento: "{$lgpdTextoEscaped}",
    textoRodape : "Dados protegidos. Não compartilhamos com terceiros sem autorização."
  }
};
JS;

    // 2. Ler e modificar o login.html
    $templatePath = __DIR__ . '/../../hotspot/login.html';
    if (!file_exists($templatePath)) exit("Template login.html não encontrado.");
    
    $template = file_get_contents($templatePath);
    
    // Substitui o bloco HOTSPOT_CONFIG padrão pelo personalizado
    $loginPersonalizado = preg_replace(
        '/const HOTSPOT_CONFIG\s*=\s*\{.*?\};/s',
        $configBlock,
        $template
    );

    // 3. Criar ZIP
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'hotspot_');
    
    if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        exit("Não foi possível criar o arquivo temporário ZIP.");
    }

    // Adiciona o login.html personalizado
    $zip->addFromString('hotspot/login.html', $loginPersonalizado);

    // Adiciona arquivos estáticos
    $hotspotDir = __DIR__ . '/../../hotspot/';
    $fixos = [
        'css/style.css',
        'alogin.html',
        'status.html',
        'logout.html',
        'error.html',
        'md5.js',
        'img/user.svg',
        'img/password.svg'
    ];

    foreach ($fixos as $f) {
        $fullPath = $hotspotDir . $f;
        if (file_exists($fullPath)) {
            $zip->addFile($fullPath, 'hotspot/' . $f);
        }
    }

    // Incluir o arquivo de logo fisicamente no ZIP se existir
    if ($isLocalLogo && file_exists($localLogoPath)) {
        $zip->addFile($localLogoPath, 'hotspot/' . $mikrotikLogoPath);
    }

    $zip->close();

    // 4. Servir para download
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($est['nome']));
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="hotspot-' . $slug . '.zip"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($tmpFile);
    
    unlink($tmpFile);
    exit;

} catch (Throwable $e) {
    exit("Erro ao gerar pacote: " . $e->getMessage());
}
