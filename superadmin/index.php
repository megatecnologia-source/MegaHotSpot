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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --cor-primaria: #e05c00; }
        .navbar { background-color: var(--cor-primaria); }
        .navbar-brand { font-weight: 800; color: #fff !important; }
        .card-stat { border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .table { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .btn-toggle { font-size: 0.75rem; padding: 2px 8px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">MEGA HOTSPOT <small class="fw-light" style="font-size: 0.6em;">SUPER ADMIN</small></a>
        <div class="d-flex">
            <span class="navbar-text me-3 d-none d-md-inline">Logado como Admin</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Sair</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Estabelecimentos Clientes</h2>
        <a href="novo.php" class="btn btn-primary">+ Novo Cliente</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
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
                    <td><strong><?= htmlspecialchars($e['nome']) ?></strong></td>
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
                        <button class="btn btn-sm btn-outline-secondary me-1 btn-toggle" onclick="toggleAtivo(<?= $e['id'] ?>)">
                            <?= $e['ativo'] ? 'Desativar' : 'Ativar' ?>
                        </button>
                        <a href="editar.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-info">Editar</a>
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

<script>
async function toggleAtivo(id) {
    if (!confirm('Deseja alterar o status deste estabelecimento?')) return;
    try {
        const res = await fetch('../api/v1/estabelecimento_toggle.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
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
