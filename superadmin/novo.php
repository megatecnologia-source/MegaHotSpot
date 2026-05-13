<?php
/**
 * superadmin/novo.php
 * Cadastro de novos estabelecimentos com branding e LGPD.
 */
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Cliente — Mega Hotspot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --cor-primaria: #e05c00; }
        .navbar { background-color: var(--cor-primaria); }
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1.5rem; font-weight: 700; color: #444; }
        .preview-box { border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; background: #0f0f1a; }
        .hs-btn-preview { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 700; color: #fff; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">MEGA HOTSPOT <small class="fw-light" style="font-size: 0.6em;">SUPER ADMIN</small></a>
        <a href="index.php" class="btn btn-outline-light btn-sm">Voltar</a>
    </div>
</nav>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-4 p-md-5">
                    <h2 class="mb-4">Cadastrar Novo Estabelecimento</h2>
                    
                    <form id="formNovo">
                        
                        <!-- Seção 1: Acesso -->
                        <div class="section-title">1. Dados de Acesso ao Painel</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Estabelecimento</label>
                                <input type="text" name="nome" id="nome_estab" class="form-control" required placeholder="Ex: Bar do João">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail de Login</label>
                                <input type="email" name="email_login" class="form-control" required placeholder="admin@estabelecimento.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Senha Inicial</label>
                                <input type="password" name="password" class="form-control" required minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar Senha</label>
                                <input type="password" name="password_confirm" class="form-control" required>
                            </div>
                        </div>

                        <!-- Seção 2: MikroTik -->
                        <div class="section-title">2. Integração MikroTik (REST API)</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">IP/Host do MikroTik</label>
                                <input type="text" name="mikrotik_ip" class="form-control" placeholder="192.168.88.1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Porta</label>
                                <input type="number" name="mikrotik_port" class="form-control" value="443">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Usuário API</label>
                                <input type="text" name="mikrotik_api_user" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Senha API</label>
                                <input type="password" name="mikrotik_api_pass" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Profile Hotspot</label>
                                <input type="text" name="mikrotik_profile" class="form-control" value="hotspot-guest">
                            </div>
                        </div>

                        <!-- Seção 3: Branding -->
                        <div class="section-title">3. Identidade Visual (Branding)</div>
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Logo URL</label>
                                        <input type="url" name="logo_url" class="form-control" placeholder="https://...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cor Primária (Botões)</label>
                                        <input type="color" name="cor_primaria" id="cor_primaria" class="form-control form-control-color w-100" value="#6C63FF">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cor Secundária</label>
                                        <input type="color" name="cor_secundaria" class="form-control form-control-color w-100" value="#4CAF50">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fundo (Início)</label>
                                        <input type="color" name="cor_fundo1" id="cor_fundo1" class="form-control form-control-color w-100" value="#0f0f1a">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fundo (Fim)</label>
                                        <input type="color" name="cor_fundo2" id="cor_fundo2" class="form-control form-control-color w-100" value="#1a1a3e">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Mensagem de Boas-vindas</label>
                                        <input type="text" name="boas_vindas" class="form-control" placeholder="Wi-Fi Grátis para você!">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block text-center">Preview em tempo real</label>
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
                                <input type="text" name="lgpd_nome_empresa" id="lgpd_nome" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CNPJ</label>
                                <input type="text" name="lgpd_cnpj" class="form-control" placeholder="00.000.000/0001-00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail do Responsável (DPO)</label>
                                <input type="email" name="lgpd_email_dpo" class="form-control" placeholder="privacidade@...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">URL da Política de Privacidade</label>
                                <input type="url" name="lgpd_url_politica" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Texto de Consentimento</label>
                                <textarea name="lgpd_texto_consentimento" id="lgpd_texto" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div id="alert" class="alert d-none"></div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="btnSubmit">Criar Estabelecimento</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Sucesso -->
<div class="modal fade" id="modalSucesso" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">✅ Estabelecimento Criado!</h5>
            </div>
            <div class="modal-body text-center p-4">
                <p>O cliente foi cadastrado com sucesso. Guarde o token abaixo:</p>
                <div class="input-group mb-3">
                    <input type="text" id="tokenField" class="form-control text-center fw-bold" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copyToken()">Copiar</button>
                </div>
                <p class="small text-muted">Use este token para configurar o portal captivo do cliente.</p>
            </div>
            <div class="modal-footer">
                <a href="index.php" class="btn btn-primary w-100">Voltar para a Listagem</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preview em tempo real
function updatePreview() {
    const cor1 = document.getElementById('cor_primaria').value;
    const bg1 = document.getElementById('cor_fundo1').value;
    const bg2 = document.getElementById('cor_fundo2').value;
    
    document.getElementById('preview_btn').style.backgroundColor = cor1;
    document.getElementById('preview_bg').style.background = `linear-gradient(135deg, ${bg1} 0%, ${bg2} 100%)`;
}

document.querySelectorAll('input[type=color]').forEach(input => {
    input.addEventListener('input', updatePreview);
});

// Auto-fill LGPD
document.getElementById('lgpd_nome').addEventListener('input', (e) => {
    const nome = e.target.value || '[NOME_EMPRESA]';
    const texto = `Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) por ${nome} para fins de comunicação comercial e relacionamento, conforme a Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).`;
    document.getElementById('lgpd_texto').value = texto;
});

updatePreview();

// Submit
document.getElementById('formNovo').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alert = document.getElementById('alert');
    const btnSubmit = document.getElementById('btnSubmit');
    
    alert.classList.add('d-none');
    btnSubmit.disabled = true;

    try {
        const formData = new FormData(e.target);
        const res = await fetch('../api/v1/estabelecimento_create.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?= $_SESSION['csrf_token'] ?>'
            }
        });
        
        const data = await res.json();
        
        if (data.status === 'success') {
            document.getElementById('tokenField').value = data.token;
            new bootstrap.Modal('#modalSucesso').show();
        } else {
            alert.textContent = data.message || 'Erro ao criar.';
            alert.className = 'alert alert-danger';
            alert.classList.remove('d-none');
            btnSubmit.disabled = false;
        }
    } catch (err) {
        alert.textContent = 'Erro de conexão.';
        alert.className = 'alert alert-danger';
        alert.classList.remove('d-none');
        btnSubmit.disabled = false;
    }
});

function copyToken() {
    const field = document.getElementById('tokenField');
    field.select();
    document.execCommand('copy');
    alert('Token copiado!');
}
</script>

</body>
</html>
