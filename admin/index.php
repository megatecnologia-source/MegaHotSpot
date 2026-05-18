<?php
/**
 * admin/index.php
 * Painel administrativo do estabelecimento.
 */
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$pdo = DB::getInstance();
$idEstab = $_SESSION['estabelecimento_id'];

// 1. Carregar Nome do Estabelecimento e Cores
$stmtEst = $pdo->prepare("SELECT nome, cor_primaria, cor_secundaria, cor_fundo1, cor_fundo2 FROM estabelecimentos WHERE id = ?");
$stmtEst->execute([$idEstab]);
$estab = $stmtEst->fetch(PDO::FETCH_ASSOC);
$nomeEstab = $estab['nome'];
$corPrimaria = $estab['cor_primaria'] ?? '#6C63FF';
$corSecundaria = $estab['cor_secundaria'] ?? '#4CAF50';
$corFundo1 = $estab['cor_fundo1'] ?? '#0a0a0f';
$corFundo2 = $estab['cor_fundo2'] ?? '#0a0a0f';

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
    <!-- We keep Bootstrap Icons for convenience as it was already there and fits the "Industrial" icon style well -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --cor-primaria: <?= $corPrimaria ?>;
            --cor-secundaria: <?= $corSecundaria ?>;
            --bg-color: <?= $corFundo1 ?>;
            --fg-color: #e2e8f0;
            --border-color: #27272a;
            --muted-color: #71717a;
            --font-display: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Monaco, 'Courier New', monospace;
            --font-body: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, Monaco, 'Courier New', monospace;
        }

        /* Global Reset & Base */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-color);
            background-image: 
                linear-gradient(var(--border-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--border-color) 1px, transparent 1px);
            background-size: 20px 20px;
            color: var(--fg-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header / Nav */
        .admin-nav {
            border-bottom: 2px solid var(--fg-color);
            background-color: rgba(10, 10, 15, 0.95);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-family: var(--font-display);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--cor-primaria);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.85rem;
        }

        .logout-btn {
            color: var(--fg-color);
            text-decoration: none;
            border: 1px solid var(--fg-color);
            padding: 0.4rem 1rem;
            text-transform: uppercase;
            font-family: var(--font-display);
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background-color: var(--cor-primaria);
            border-color: var(--cor-primaria);
            color: #fff;
        }

        /* Layout Container */
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 3rem 2rem;
            flex: 1;
        }

        /* Grid for Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background-color: rgba(10, 10, 15, 0.95);
            border: 1px solid var(--border-color);
            padding: 1.5rem;
            box-shadow: 4px 4px 0px 0px var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-card.alert-border {
            border-left: 4px solid var(--cor-primaria);
        }

        .stat-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted-color);
        }

        .stat-value {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--fg-color);
        }

        .stat-value.accent {
            color: var(--cor-primaria);
        }

        /* Table Section */
        .table-section {
            background-color: rgba(10, 10, 15, 0.95);
            border: 2px solid var(--fg-color);
            padding: 2rem;
            box-shadow: 8px 8px 0px 0px var(--border-color);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }

        .section-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Search Box */
        .search-box {
            position: relative;
            max-width: 300px;
            width: 100%;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 0.5rem 0.5rem 2rem;
            background: #12121a;
            border: 1px solid var(--border-color);
            color: var(--fg-color);
            font-family: var(--font-body);
            font-size: 0.85rem;
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted-color);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1px solid var(--fg-color);
            background: transparent;
            color: var(--fg-color);
            font-family: var(--font-display);
            font-size: 0.85rem;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background-color: var(--cor-primaria);
            border-color: var(--cor-primaria);
            color: #fff;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.85rem;
        }

        th, td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            font-family: var(--font-display);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted-color);
            font-weight: 600;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-family: var(--font-display);
            text-transform: uppercase;
            font-weight: 700;
        }

        .bg-success { border: 1px solid var(--cor-secundaria); color: var(--cor-secundaria); }
        .bg-warning { border: 1px solid #eab308; color: #eab308; }
        .bg-danger { border: 1px solid #ef4444; color: #ef4444; }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: var(--muted-color);
        }

        .pagination-btns {
            display: flex;
            gap: 0.5rem;
        }

        .page-link {
            padding: 0.3rem 0.8rem;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--fg-color);
            cursor: pointer;
            font-family: var(--font-display);
        }

        .page-link:hover:not(:disabled) {
            border-color: var(--cor-primaria);
            color: var(--cor-primaria);
        }

        .page-link:disabled {
            color: var(--muted-color);
            cursor: not-allowed;
        }

        /* Utilities */
        .fw-bold { font-weight: 700; }
        .text-muted { color: var(--muted-color); }
        .small { font-size: 0.8rem; }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <a class="brand" href="#"><i class="bi bi-wifi"></i> MEGA HOTSPOT</a>
        <div class="user-info">
            <span class="d-none d-md-block opacity-75"><?= htmlspecialchars($nomeEstab) ?></span>
            <a href="logout.php" class="logout-btn">Sair</a>
        </div>
    </nav>

    <div class="container">
        
        <!-- Métricas -->
        <div class="metrics-grid">
            <div class="stat-card">
                <div class="stat-title">Total de Leads</div>
                <div class="stat-value"><?= number_format($stats['total'], 0, ',', '.') ?></div>
            </div>
            <div class="stat-card alert-border">
                <div class="stat-title">Capturados Hoje</div>
                <div class="stat-value accent"><?= $stats['hoje'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Últimos 7 dias</div>
                <div class="stat-value"><?= $stats['semana'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Neste Mês</div>
                <div class="stat-value"><?= $stats['mes'] ?></div>
            </div>
            <div class="stat-card <?= $stats['pendentes'] > 0 ? 'alert-border' : '' ?>">
                <div class="stat-title">Pendentes de Sync</div>
                <div class="stat-value <?= $stats['pendentes'] > 0 ? 'accent' : '' ?>"><?= $stats['pendentes'] ?></div>
                <div class="stat-title" style="font-size: 0.65rem; margin-top: auto;"><?= $stats['pendentes'] > 0 ? 'aguardando MikroTik' : 'tudo sincronizado' ?></div>
            </div>
        </div>

        <!-- Tabela Principal -->
        <div class="table-section">
            <div class="section-header">
                <h2 class="section-title">Leads</h2>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="input-busca" placeholder="Buscar por nome, CPF ou WhatsApp...">
                    </div>
                    <a href="../api/v1/export.php" class="btn">
                        <i class="bi bi-download"></i> Exportar CSV
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tabela-leads">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>WhatsApp</th>
                            <th>E-mail</th>
                            <th>Data Cadastro</th>
                            <th>Sync</th>
                            <th style="text-align: right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="lista-leads">
                        <tr><td colspan="7" style="text-align: center; padding: 3rem;"><div class="spinner-border text-primary"></div></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="pagination-container">
                <div id="info-paginacao">Mostrando 0 de 0 leads</div>
                <div class="pagination-btns">
                    <button class="page-link" id="btn-prev">Anterior</button>
                    <button class="page-link" id="btn-next">Próximo</button>
                </div>
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
                lista.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 3rem;" class="text-muted">Nenhum registro encontrado.</td></tr>';
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
                    <td style="text-align: right;">
                        <a href="https://wa.me/${lead.whatsapp.replace(/\D/g, '')}" target="_blank" class="btn" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">
                            <i class="bi bi-whatsapp"></i> <span class="d-none d-sm-inline">WhatsApp</span>
                        </a>
                    </td>
                </tr>
            `).join('');

            const inicio = ((data.pagina - 1) * data.por_pagina) + 1;
            const fim    = Math.min(data.pagina * data.por_pagina, data.total);
            info.textContent = `Mostrando ${inicio} a ${fim} de ${data.total} leads`;

            document.getElementById('btn-prev').disabled = (paginaAtual <= 1);
            document.getElementById('btn-next').disabled = (fim >= data.total);

        } catch (err) {
            lista.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 3rem;" class="text-danger">Erro ao carregar dados.</td></tr>';
        }
    }

    function formatarData(str) {
        const d = new Date(str);
        return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    }

    function getSyncBadge(status) {
        if (status === 'synced')  return '<span class="badge bg-success">✓ Sync</span>';
        if (status === 'pending') return '<span class="badge bg-warning">⏳ Pendente</span>';
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