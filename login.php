<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
$pageTitle = 'Admin Login';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = :u');
    $stmt->execute(['u' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid admin credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Phantom Detective</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<section class="container py-5 min-vh-100 d-flex align-items-center">
  <div class="row justify-content-center w-100">
    <div class="col-md-5">
      <div class="pd-panel p-4 p-md-5">
        <div class="text-center mb-4">
          <i class="fa-solid fa-user-shield fa-2x" style="color:var(--pd-accent)"></i>
          <h3 class="mt-2 pd-brand">ADMIN <span>ACCESS</span></h3>
          <p class="text-muted small">Restricted &mdash; authorized personnel only.</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-4">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-pd-solid w-100">Login <i class="fa-solid fa-right-to-bracket"></i></button>
        </form>
        <p class="text-center small text-muted mt-3 mb-0">Demo admin: <code>admin</code> / <code>Admin@123</code></p>
        <p class="text-center small mt-2"><a href="../index.php">&larr; Back to site</a></p>
      </div>
    </div>
  </div>
</section>
</body>
</html>
