<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Mega Hotspot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --cor-primaria: #e05c00; } /* Cor diferenciada para SuperAdmin */
        body { background: #f4f7f6; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: #fff; }
        .btn-primary { background-color: var(--cor-primaria); border-color: var(--cor-primaria); }
        .btn-primary:hover { background-color: #c44d00; border-color: #c44d00; }
        .logo-text { font-weight: 800; font-size: 1.5rem; color: #333; text-align: center; margin-bottom: 0.5rem; }
        .badge-super { background: var(--cor-primaria); font-size: 0.7rem; vertical-align: middle; margin-left: 5px; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-text">Mega Hotspot <span class="badge badge-super">SUPER ADMIN</span></div>
    <p class="text-center text-muted mb-4">Acesse o painel de controle mestre</p>

    <div id="alert" class="alert alert-danger d-none"></div>

    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required placeholder="admin@megatecnologias.com">
        </div>
        <div class="mb-4">
            <label class="form-label">Senha</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2" id="btnSubmit">
            <span id="btnText">Entrar</span>
            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"></span>
        </button>
    </form>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const alert = document.getElementById('alert');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnSubmit = document.getElementById('btnSubmit');

    alert.classList.add('d-none');
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');
    btnSubmit.disabled = true;

    try {
        const formData = new FormData(e.target);
        const res = await fetch('../api/v1/superlogin.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData)),
            headers: { 'Content-Type': 'application/json' }
        });
        
        const data = await res.json();
        
        if (data.status === 'success') {
            window.location.href = 'index.php';
        } else {
            alert.textContent = data.message || 'Erro ao entrar.';
            alert.classList.remove('d-none');
        }
    } catch (err) {
        alert.textContent = 'Erro de conexão com o servidor.';
        alert.classList.remove('d-none');
    } finally {
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');
        btnSubmit.disabled = false;
    }
});
</script>

</body>
</html>
