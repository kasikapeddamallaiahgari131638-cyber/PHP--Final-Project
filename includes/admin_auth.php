<?php
/**
 * Include at the top of any page that requires a logged-in ADMIN.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}
