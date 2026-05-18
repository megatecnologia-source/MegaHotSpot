<?php
/**
 * superadmin/index.php
 * Dashboard principal do Super Admin.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->query("
        SELECT e.id, e.nome, e.email_login, e.mikrotik_ip, e.ativo, e.data_criacao,
               (SELECT COUNT(*) FROM leads l WHERE l.estabelecimento_id = e.id) AS total_leads
        FROM estabelecimentos e
        ORDER BY e.data_criacao DESC
    ");
    $estabelecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Super Admin — Mega Hotspot</title>
    <!-- We keep Bootstrap Icons for convenience -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --cor-primaria: #ff6b00;
            --bg-color: #0a0a0f;
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

        .brand small {
            font-size: 0.6rem;
            color: var(--fg-color);
            opacity: 0.7;
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

        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
        }

        .btn-outline-secondary {
            border-color: var(--muted-color);
            color: var(--muted-color);
        }

        .btn-outline-secondary:hover {
            background-color: var(--muted-color);
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
            border: 1px solid var(--muted-color);
            color: var(--muted-color);
        }

        .bg-success { border-color: #4CAF50; color: #4CAF50; }
        .bg-danger { border-color: #ef4444; color: #ef4444; }
        .bg-secondary { border-color: var(--muted-color); color: var(--muted-color); }

        /* Utilities */
        .fw-bold { font-weight: 700; }
        .text-muted { color: var(--muted-color); }
        .small { font-size: 0.8rem; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
    </style>
</head>
<body>

<nav class="admin-nav">
    <a class="brand" href="#">MEGA HOTSPOT <small>SUPER ADMIN</small></a>
    <div class="user-info">
        <span class="d-none d-md-block opacity-75">Logado como Admin</span>
        <a href="logout.php" class="logout-btn">Sair</a>
    </div>
</nav>

<div class="container">
    <div class="table-section">
        <div class="section-header">
            <h2 class="section-title">Estabelecimentos Clientes</h2>
            <a href="novo.php" class="btn">+ Novo Cliente</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail de Acesso</th>
                        <th>IP MikroTik</th>
                        <th class="text-center">Leads</th>
                        <th class="text-center">Status</th>
                        <th>Cadastro</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estabelecimentos as $e): ?>
                    <tr>
                        <td><strong class="fw-bold"><?= htmlspecialchars($e['nome']) ?></strong></td>
                        <td><?= htmlspecialchars($e['email_login']) ?></td>
                        <td><code class="text-muted"><?= htmlspecialchars($e['mikrotik_ip'] ?: '---') ?></code></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= $e['total_leads'] ?></span></td>
                        <td class="text-center">
                            <?php if ($e['ativo']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= date('d/m/Y', strtotime($e['data_criacao'])) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleAtivo(<?= $e['id'] ?>)">
                                <?= $e['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                            <a href="editar.php?id=<?= $e['id'] ?>" class="btn btn-sm">Editar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($estabelecimentos)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Nenhum cliente cadastrado.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function toggleAtivo(id) {
    if (!confirm('Deseja alterar o status deste estabelecimento?')) return;
    try {
        const res = await fetch('../api/v1/estabelecimento_toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type':'application/json',
                'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token'] ?>'
            },
            body: JSON.stringify({id})
        });
        const data = await res.json();
        if (data.status === 'success') {
            location.reload();
        } else {
            alert(data.message || 'Erro ao alterar status.');
        }
    } catch (err) {
        alert('Erro de conexão.');
    }
}
</script>

</body>
</html>