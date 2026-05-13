<?php
/**
 * superadmin/auth.php
 * Proteção de páginas do superadmin.
 */
session_start();
if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}
