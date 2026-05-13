<?php
session_start();
// Se já estiver logado, joga para o painel
if (isset($_SESSION['estabelecimento_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mega Hotspot</title>
    <!-- Usando Bootstrap CDN para design limpo e responsivo -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f4f6f9; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .login-card { 
            max-width: 400px; 
            width: 100%; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            border-radius: 12px; 
            border: none; 
        }
        .login-card .card-body { 
            padding: 2.5rem; 
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <div class="text-center mb-4">
                <h3 class="fw-bold text-primary">Mega Hotspot</h3>
                <p class="text-muted small">Faça login para ver seus leads</p>
            </div>
            
            <div id="error-msg" class="alert alert-danger d-none fs-6 p-2 text-center"></div>
            
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label text-secondary fw-semibold">E-mail</label>
                    <input type="email" name="email" class="form-control form-control-lg fs-6" required placeholder="seu@email.com">
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary fw-semibold">Senha</label>
                    <input type="password" name="password" class="form-control form-control-lg fs-6" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="btnSubmit">
                    Entrar <span id="spinner" class="spinner-border spinner-border-sm d-none ms-1"></span>
                </button>
            </form>
        </div>
    </div>
    
    <script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btnSubmit');
        const spinner = document.getElementById('spinner');
        const errDiv = document.getElementById('error-msg');
        
        btn.disabled = true;
        spinner.classList.remove('d-none');
        errDiv.classList.add('d-none');
        
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('../api/v1/login.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                window.location.href = 'index.php';
            } else {
                errDiv.textContent = data.message;
                errDiv.classList.remove('d-none');
                btn.disabled = false;
                spinner.classList.add('d-none');
            }
        } catch (error) {
            errDiv.textContent = 'Erro de comunicação com o servidor.';
            errDiv.classList.remove('d-none');
            btn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
    </script>
</body>
</html>
