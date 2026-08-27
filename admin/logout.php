<?php
require_once __DIR__ . '/../includes/admin_auth.php';
$_SESSION['admin_id'] = null;
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
header('Location: login.php');
exit;
