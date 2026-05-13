<?php
/**
 * api/v1/leads.php
 * Listagem paginada de leads para o painel admin.
 */
session_start();
header('Content-Type: application/json');

// Aceita apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit;
}

// Verifica autenticação da sessão
if (!isset($_SESSION['estabelecimento_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autenticado."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$estabelecimentoId = (int) $_SESSION['estabelecimento_id'];
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = min(100, max(1, (int) ($_GET['per_page'] ?? 50)));
$busca    = trim($_GET['busca'] ?? '');
$offset   = ($page - 1) * $perPage;

try {
    $pdo = DB::getInstance();

    // 1. Construir cláusula WHERE
    $whereClause = "WHERE estabelecimento_id = :id";
    $params = [':id' => $estabelecimentoId];
    
    if ($busca !== '') {
        $whereClause .= " AND (nome LIKE :busca OR cpf LIKE :busca OR whatsapp LIKE :busca)";
        $params[':busca'] = "%$busca%";
    }

    // 2. Contar total de registros
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads $whereClause");
    foreach ($params as $key => $val) {
        $countStmt->bindValue($key, $val);
    }
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    // 3. Buscar leads da página atual
    $stmt = $pdo->prepare("
        SELECT id, cpf, nome, whatsapp, email, mac_address, 
               data_cadastro, data_atualizacao, mikrotik_sync_status
        FROM leads 
        $whereClause
        ORDER BY data_cadastro DESC
        LIMIT :limit OFFSET :offset
    ");

    // Binds manuais para garantir tipos corretos no LIMIT/OFFSET
    $stmt->bindValue(':id', $estabelecimentoId, PDO::PARAM_INT);
    if ($busca !== '') {
        $stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Retornar resposta JSON
    echo json_encode([
        "total"      => $total,
        "pagina"     => $page,
        "por_pagina" => $perPage,
        "leads"      => $leads
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);
}
