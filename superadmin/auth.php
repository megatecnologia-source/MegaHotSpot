<?php
/**
 * superadmin/auth.php
 * Proteção de páginas do superadmin.
 */
require_once __DIR__ . '/../config/security.php';
secure_session_start();

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}
