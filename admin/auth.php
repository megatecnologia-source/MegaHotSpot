<?php
require_once __DIR__ . '/../config/security.php';
secure_session_start();

// Se não houver estabelecimento_id na sessão, envia para a página de login
if (!isset($_SESSION['estabelecimento_id'])) {
    header("Location: login.php");
    exit;
}
