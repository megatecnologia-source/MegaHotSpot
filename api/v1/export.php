<?php
/**
 * api/v1/export.php
 * Exportação de leads em formato CSV com suporte a acentuação (BOM UTF-8).
 */
session_start();

if (!isset($_SESSION['estabelecimento_id'])) {
    http_response_code(401);
    exit("Não autorizado.");
}

require_once __DIR__ . '/../../config/db.php';
$idEstab = (int) $_SESSION['estabelecimento_id'];

try {
    $pdo = DB::getInstance();
    
    // Busca todos os leads do estabelecimento logado
    $stmt = $pdo->prepare("
        SELECT nome, cpf, whatsapp, email, mac_address, data_cadastro, data_atualizacao
        FROM leads
        WHERE estabelecimento_id = :id
        ORDER BY data_cadastro DESC
    ");
    $stmt->execute([':id' => $idEstab]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = 'leads-' . date('Y-m-d-His') . '.csv';

    // Headers para download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // 1. Inserir BOM (Byte Order Mark) para que o Excel reconheça o UTF-8 automaticamente
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // 2. Cabeçalho do CSV (usando ponto-e-vírgula como separador padrão regional BR)
    fputcsv($out, ['Nome', 'CPF', 'WhatsApp', 'E-mail', 'Endereço MAC', 'Data Cadastro', 'Data Atualização'], ';');

    // 3. Dados
    foreach ($leads as $lead) {
        fputcsv($out, [
            $lead['nome'],
            $lead['cpf'],
            $lead['whatsapp'],
            $lead['email'] ?? '',
            $lead['mac_address'] ?? '',
            $lead['data_cadastro'],
            $lead['data_atualizacao'] ?? ''
        ], ';');
    }

    fclose($out);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    exit("Erro ao exportar dados.");
}
