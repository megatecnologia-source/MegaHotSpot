<?php
/**
 * superadmin/editar.php
 * Edição de estabelecimentos, teste de conexão e download de pacote.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.php"); exit; }

try {
    $pdo = DB::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM estabelecimentos WHERE id = ?");
    $stmt->execute([$id]);
    $est = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$est) { header("Location: index.php"); exit; }
} catch (Throwable $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente — Mega Hotspot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --cor-primaria: #e05c00; }
        .navbar { background-color: var(--cor-primaria); }
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1.5rem; font-weight: 700; color: #444; }
        .preview-box { border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; }
        .hs-btn-preview { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 700; color: #fff; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">MEGA HOTSPOT <small class="fw-light" style="font-size: 0.6em;">SUPER ADMIN</small></a>
        <div class="d-flex">
            <a href="../api/v1/gerar_pacote.php?id=<?= $id ?>" class="btn btn-success btn-sm me-2">📦 Download Pacote ZIP</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">Voltar</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h2>Editar: <?= htmlspecialchars($est['nome']) ?></h2>
                        <div class="text-end">
                            <small class="text-muted d-block">Token do Cliente:</small>
                            <code class="fw-bold"><?= $est['cliente_token'] ?></code>
                        </div>
                    </div>
                    
                    <form id="formEditar">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        
                        <!-- Seção 1: Acesso -->
                        <div class="section-title">1. Dados de Acesso ao Painel</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Estabelecimento</label>
                                <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($est['nome']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail de Login</label>
                                <input type="email" name="email_login" class="form-control" required value="<?= htmlspecialchars($est['email_login']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nova Senha <small>(deixe em branco para não alterar)</small></label>
                                <input type="password" name="password" class="form-control" minlength="8">
                            </div>
                        </div>

                        <!-- Seção 2: MikroTik -->
                        <div class="section-title d-flex justify-content-between align-items-center">
                            <span>2. Integração MikroTik (REST API)</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnTestRouter">Testar Conexão</button>
                        </div>
                        <div id="routerAlert" class="alert d-none mt-2"></div>
                        <div class="row g-3 mb-4 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">IP/Host do MikroTik</label>
                                <input type="text" name="mikrotik_ip" class="form-control" value="<?= htmlspecialchars($est['mikrotik_ip']) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Porta</label>
                                <input type="number" name="mikrotik_port" class="form-control" value="<?= $est['mikrotik_port'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Usuário API</label>
                                <input type="text" name="mikrotik_api_user" class="form-control" value="<?= htmlspecialchars($est['mikrotik_api_user']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Senha API</label>
                                <input type="password" name="mikrotik_api_pass" class="form-control" value="<?= htmlspecialchars($est['mikrotik_api_pass']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Profile Hotspot</label>
                                <input type="text" name="mikrotik_profile" class="form-control" value="<?= htmlspecialchars($est['mikrotik_profile']) ?>">
                            </div>
                        </div>

                        <!-- Seção 3: Branding -->
                        <div class="section-title">3. Identidade Visual (Branding)</div>
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Logo URL</label>
                                        <input type="url" name="logo_url" class="form-control" value="<?= htmlspecialchars($est['logo_url']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cor Primária (Botões)</label>
                                        <input type="color" name="cor_primaria" id="cor_primaria" class="form-control form-control-color w-100" value="<?= $est['cor_primaria'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cor Secundária</label>
                                        <input type="color" name="cor_secundaria" class="form-control form-control-color w-100" value="<?= $est['cor_secundaria'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fundo (Início)</label>
                                        <input type="color" name="cor_fundo1" id="cor_fundo1" class="form-control form-control-color w-100" value="<?= $est['cor_fundo1'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fundo (Fim)</label>
                                        <input type="color" name="cor_fundo2" id="cor_fundo2" class="form-control form-control-color w-100" value="<?= $est['cor_fundo2'] ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Mensagem de Boas-vindas</label>
                                        <input type="text" name="boas_vindas" class="form-control" value="<?= htmlspecialchars($est['boas_vindas']) ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block text-center">Preview</label>
                                <div class="preview-box" id="preview_bg">
                                    <div class="text-white small mb-3">Exemplo de Botão</div>
                                    <button type="button" class="hs-btn-preview" id="preview_btn">Conectar Agora</button>
                                </div>
                            </div>
                        </div>

                        <!-- Seção 4: LGPD -->
                        <div class="section-title">4. Compliance LGPD</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label">Nome Jurídico da Empresa</label>
                                <input type="text" name="lgpd_nome_empresa" class="form-control" required value="<?= htmlspecialchars($est['lgpd_nome_empresa']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CNPJ</label>
                                <input type="text" name="lgpd_cnpj" class="form-control" value="<?= htmlspecialchars($est['lgpd_cnpj']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail do Responsável (DPO)</label>
                                <input type="email" name="lgpd_email_dpo" class="form-control" value="<?= htmlspecialchars($est['lgpd_email_dpo']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL da Política de Privacidade</label>
                                <input type="url" name="lgpd_url_politica" class="form-control" value="<?= htmlspecialchars($est['lgpd_url_politica']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Texto de Consentimento</label>
                                <textarea name="lgpd_texto_consentimento" class="form-control" rows="4"><?= htmlspecialchars($est['lgpd_texto_consentimento']) ?></textarea>
                            </div>
                        </div>

                        <div id="alert" class="alert d-none"></div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">Salvar Alterações</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updatePreview() {
    const cor1 = document.getElementById('cor_primaria').value;
    const bg1 = document.getElementById('cor_fundo1').value;
    const bg2 = document.getElementById('cor_fundo2').value;
    document.getElementById('preview_btn').style.backgroundColor = cor1;
    document.getElementById('preview_bg').style.background = `linear-gradient(135deg, ${bg1} 0%, ${bg2} 100%)`;
}
document.querySelectorAll('input[type=color]').forEach(input => input.addEventListener('input', updatePreview));
updatePreview();

// Submit Update
document.getElementById('formEditar').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alert = document.getElementById('alert');
    const btnSubmit = document.getElementById('btnSubmit');
    alert.classList.add('d-none');
    btnSubmit.disabled = true;

    try {
        const formData = new FormData(e.target);
        const res = await fetch('../api/v1/estabelecimento_update.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token'] ?>'
            }
        });
        const data = await res.json();
        if (data.status === 'success') {
            alert.textContent = 'Salvo com sucesso!';
            alert.className = 'alert alert-success';
            alert.classList.remove('d-none');
            setTimeout(() => alert.classList.add('d-none'), 3000);
        } else {
            alert.textContent = data.message || 'Erro ao salvar.';
            alert.className = 'alert alert-danger';
            alert.classList.remove('d-none');
        }
    } catch (err) {
        alert.textContent = 'Erro de conexão.';
        alert.className = 'alert alert-danger';
        alert.classList.remove('d-none');
    } finally {
        btnSubmit.disabled = false;
    }
});

// Test Connection
document.getElementById('btnTestRouter').addEventListener('click', async () => {
    const alert = document.getElementById('routerAlert');
    const btn = document.getElementById('btnTestRouter');
    alert.className = 'alert alert-info';
    alert.textContent = 'Testando...';
    alert.classList.remove('d-none');
    btn.disabled = true;

    try {
        const res = await fetch(`../api/v1/router_test.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token'] ?>'
            },
            body: JSON.stringify({id: <?= $id ?>})
        });
        const data = await res.json();
        if (data.status === 'success') {
            alert.className = 'alert alert-success';
            alert.textContent = '✅ ' + data.message;
        } else {
            alert.className = 'alert alert-danger';
            alert.textContent = '❌ ' + data.message;
        }
    } catch (err) {
        alert.className = 'alert alert-danger';
        alert.textContent = 'Erro ao conectar com a API central.';
    } finally {
        btn.disabled = false;
    }
});
</script>

</body>
</html>
