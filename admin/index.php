<?php
/**
 * admin/index.php
 * Painel administrativo do estabelecimento.
 */
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$pdo = DB::getInstance();
$idEstab = $_SESSION['estabelecimento_id'];

// 1. Carregar Nome do Estabelecimento
$stmtEst = $pdo->prepare("SELECT nome FROM estabelecimentos WHERE id = ?");
$stmtEst->execute([$idEstab]);
$nomeEstab = $stmtEst->fetchColumn();

// 2. Carregar Métricas
$stats = [];

// Total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE estabelecimento_id = ?");
$stmt->execute([$idEstab]);
$stats['total'] = $stmt->fetchColumn();

// Hoje
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE estabelecimento_id = ? AND DATE(data_cadastro) = CURDATE()");
$stmt->execute([$idEstab]);
$stats['hoje'] = $stmt->fetchColumn();

// 7 Dias
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE estabelecimento_id = ? AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$idEstab]);
$stats['semana'] = $stmt->fetchColumn();

// Mês
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE estabelecimento_id = ? AND MONTH(data_cadastro) = MONTH(NOW()) AND YEAR(data_cadastro) = YEAR(NOW())");
$stmt->execute([$idEstab]);
$stats['mes'] = $stmt->fetchColumn();

// Pendentes de sync MikroTik
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE estabelecimento_id = ? AND mikrotik_sync_status = 'pending'");
$stmt->execute([$idEstab]);
$stats['pendentes'] = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin — <?= htmlspecialchars($nomeEstab) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card-stat { border: none; border-radius: 12px; transition: transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .card-stat:hover { transform: translateY(-3px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .table-card { background: #fff; border-radius: 15px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); padding: 1.5rem; }
        .search-box { position: relative; max-width: 400px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #adb5bd; }
        .search-box input { padding-left: 40px; border-radius: 20px; border: 1px solid #dee2e6; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="bi bi-wifi me-2 text-primary"></i>MEGA HOTSPOT</a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3 d-none d-md-block small opacity-75"><?= htmlspecialchars($nomeEstab) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <!-- Métricas -->
        <div class="row g-4 mb-5">
            <div class="col-6 col-lg-3">
                <div class="card card-stat p-3 h-100">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></div>
                    <div class="small text-muted mb-1">Total de Leads</div>
                    <div class="h3 fw-bold mb-0"><?= number_format($stats['total'], 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-stat p-3 h-100 border-start border-primary border-4">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-calendar-check"></i></div>
                    <div class="small text-muted mb-1">Capturados Hoje</div>
                    <div class="h3 fw-bold mb-0 text-success"><?= $stats['hoje'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-stat p-3 h-100">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-graph-up"></i></div>
                    <div class="small text-muted mb-1">Últimos 7 dias</div>
                    <div class="h3 fw-bold mb-0"><?= $stats['semana'] ?></div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-stat p-3 h-100">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-calendar-range"></i></div>
                    <div class="small text-muted mb-1">Neste Mês</div>
                    <div class="h3 fw-bold mb-0"><?= $stats['mes'] ?></div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card card-stat p-3 h-100 <?= $stats['pendentes'] > 0 ? 'border-start border-warning border-4' : '' ?>">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-repeat"></i></div>
                    <div class="small text-muted mb-1">Pendentes de Sync</div>
                    <div class="h3 fw-bold mb-0 <?= $stats['pendentes'] > 0 ? 'text-warning' : 'text-success' ?>">
                        <?= $stats['pendentes'] ?>
                    </div>
                    <small class="text-muted d-block mt-1"><?= $stats['pendentes'] > 0 ? 'aguardando MikroTik' : 'tudo sincronizado' ?></small>
                </div>
            </div>
        </div>

        <!-- Tabela Principal -->
        <div class="table-card">
            <div class="row align-items-center mb-4 g-3">
                <div class="col-md-4">
                    <h4 class="mb-0 fw-bold">Leads</h4>
                </div>
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="input-busca" class="form-control" placeholder="Buscar por nome, CPF ou WhatsApp...">
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../api/v1/export.php" class="btn btn-outline-dark rounded-pill px-4">
                        <i class="bi bi-download me-2"></i>Exportar CSV
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tabela-leads">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>WhatsApp</th>
                            <th>E-mail</th>
                            <th>Data Cadastro</th>
                            <th>Sync</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="lista-leads">
                        <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="small text-muted" id="info-paginacao">Mostrando 0 de 0 leads</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 rounded-pill overflow-hidden">
                        <li class="page-item"><button class="page-link" id="btn-prev">Anterior</button></li>
                        <li class="page-item"><button class="page-link" id="btn-next">Próximo</button></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script>
    let paginaAtual = 1;
    let buscaTimer = null;

    async function carregarLeads() {
        const busca = document.getElementById('input-busca').value.trim();
        const lista = document.getElementById('lista-leads');
        const info  = document.getElementById('info-paginacao');
        
        try {
            const res = await fetch(`../api/v1/leads.php?page=${paginaAtual}&per_page=15&busca=${encodeURIComponent(busca)}`);
            const data = await res.json();
            
            if (data.leads.length === 0) {
                lista.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">Nenhum registro encontrado.</td></tr>';
                info.textContent = 'Mostrando 0 de 0 leads';
                return;
            }

            lista.innerHTML = data.leads.map(lead => `
                <tr>
                    <td class="fw-bold">${escapeHTML(lead.nome)}</td>
                    <td class="text-muted small">${lead.cpf}</td>
                    <td>${lead.whatsapp}</td>
                    <td class="small">${lead.email || '-'}</td>
                    <td class="small text-muted">${formatarData(lead.data_cadastro)}</td>
                    <td>${getSyncBadge(lead.mikrotik_sync_status)}</td>
                    <td class="text-end">
                        <a href="https://wa.me/${lead.whatsapp.replace(/\D/g, '')}" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                            <i class="bi bi-whatsapp"></i> <span class="d-none d-sm-inline">WhatsApp</span>
                        </a>
                    </td>
                </tr>
            `).join('');

            const inicio = ((data.pagina - 1) * data.por_pagina) + 1;
            const fim    = Math.min(data.pagina * data.por_pagina, data.total);
            info.textContent = `Mostrando ${inicio} a ${fim} de ${data.total} leads`;

            document.getElementById('btn-prev').parentElement.classList.toggle('disabled', paginaAtual <= 1);
            document.getElementById('btn-next').parentElement.classList.toggle('disabled', fim >= data.total);

        } catch (err) {
            lista.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-danger">Erro ao carregar dados.</td></tr>';
        }
    }

    function formatarData(str) {
        const d = new Date(str);
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    }

    function getSyncBadge(status) {
        if (status === 'synced')  return '<span class="badge bg-success">✓ Sync</span>';
        if (status === 'pending') return '<span class="badge bg-warning text-dark">⏳ Pendente</span>';
        return '<span class="badge bg-danger">✗ Erro</span>';
    }

    function escapeHTML(str) {
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    document.getElementById('input-busca').addEventListener('input', () => {
        clearTimeout(buscaTimer);
        paginaAtual = 1;
        buscaTimer = setTimeout(carregarLeads, 400);
    });

    document.getElementById('btn-prev').addEventListener('click', () => {
        if (paginaAtual > 1) { paginaAtual--; carregarLeads(); }
    });

    document.getElementById('btn-next').addEventListener('click', () => {
        paginaAtual++; carregarLeads();
    });

    document.addEventListener('DOMContentLoaded', carregarLeads);
    </script>
</body>
</html>
