<?php
/**
 * Include at the top of any page that requires a logged-in PLAYER.
 * Redirects to login.php if no valid session exists.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . base_path('login.php'));
        exit;
    }
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Returns a relative path prefix so links work whether we are in the
 * project root or inside /cases/ or /admin/.
 */
function base_path(string $target): string
{
    $script = $_SERVER['SCRIPT_NAME'];
    if (strpos($script, '/cases/') !== false || strpos($script, '/admin/') !== false) {
        return '../' . $target;
    }
    return $target;
}

function log_activity(PDO $pdo, ?int $userId, string $action, string $details = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs (user_id, action, details) VALUES (:uid, :action, :details)'
    );
    $stmt->execute(['uid' => $userId, 'action' => $action, 'details' => $details]);
}
