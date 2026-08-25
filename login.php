<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Login';

$error = '';
$justRegistered = !empty($_SESSION['registered']);
unset($_SESSION['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = :i1 OR email = :i2) AND status = "active"');
    $stmt->execute(['i1' => $identifier, 'i2' => $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        log_activity($pdo, $user['id'], 'login', 'User logged in');
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid credentials, or your account has been deactivated.';
}

include __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="pd-panel p-4 p-md-5 pd-reveal">
        <div class="text-center mb-4">
          <i class="fa-solid fa-magnifying-glass fa-2x" style="color:var(--pd-accent)"></i>
          <h3 class="mt-2">Detective Login</h3>
          <p class="text-muted small">Continue your investigations.</p>
        </div>

        <?php if ($justRegistered): ?>
          <div class="alert alert-success small" data-autohide>Registration successful! You can now login.</div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label>Username or Email</label>
            <input type="text" name="identifier" class="form-control" required autofocus>
          </div>
          <div class="mb-4">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-pd-solid w-100">Login <i class="fa-solid fa-right-to-bracket"></i></button>
        </form>
        <p class="text-center small text-muted mt-3 mb-1">New detective? <a href="register.php">Register here</a></p>
        <p class="text-center small text-muted mb-0">Demo account: <code>detective</code> / <code>Detective@123</code></p>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
