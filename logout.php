<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], 'logout', 'User logged out');
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
