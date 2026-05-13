<?php
/**
 * config/security.php
 * Utilitários de segurança: Sessão, CSRF e Sanitização.
 */

function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações de segurança para o cookie de sessão
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        
        // Se estiver em HTTPS (comum em produção), marcar como Secure
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

        session_start();
    }

    // Gerar CSRF token se não existir
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Valida o token CSRF enviado via POST ou Header.
 */
function validate_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Falha na validação CSRF."]);
        exit;
    }
}

/**
 * Atalho para escape de HTML (Prevenção XSS).
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
