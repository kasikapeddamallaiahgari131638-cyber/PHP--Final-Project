<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Register';

$errors = [];
$old = ['username' => '', 'email' => '', 'full_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username']  = trim($_POST['username'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($old['username'] === '' || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $old['username'])) {
        $errors[] = 'Username must be 3-30 characters (letters, numbers, underscore only).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
        $stmt->execute(['u' => $old['username'], 'e' => $old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'That username or email is already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, full_name) VALUES (:u, :e, :p, :f)'
        );
        $stmt->execute([
            'u' => $old['username'],
            'e' => $old['email'],
            'p' => $hash,
            'f' => $old['full_name'],
        ]);
        $newUserId = $pdo->lastInsertId();

        // Seed player_progress and leaderboard rows for all published cases
        $cases = $pdo->query('SELECT id FROM cases')->fetchAll();
        $insP = $pdo->prepare('INSERT INTO player_progress (user_id, case_id, status, progress_percentage) VALUES (:u, :c, "not_started", 0)');
        foreach ($cases as $c) { $insP->execute(['u' => $newUserId, 'c' => $c['id']]); }
        $pdo->prepare('INSERT INTO leaderboard (user_id, total_score, cases_solved, accuracy) VALUES (:u, 0, 0, 0)')
            ->execute(['u' => $newUserId]);

        log_activity($pdo, $newUserId, 'register', 'New detective registered');

        $_SESSION['registered'] = true;
        header('Location: login.php');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="pd-panel p-4 p-md-5 pd-reveal">
        <div class="text-center mb-4">
          <i class="fa-solid fa-id-badge fa-2x" style="color:var(--pd-accent)"></i>
          <h3 class="mt-2">Detective Registration</h3>
          <p class="text-muted small">Create your badge to start investigating.</p>
        </div>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 small">
              <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" id="registerForm" novalidate>
          <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($old['full_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($old['username']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($old['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="6">
          </div>
          <div class="mb-4">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
          </div>
          <button type="submit" class="btn btn-pd-solid w-100">Create Badge <i class="fa-solid fa-arrow-right"></i></button>
        </form>
        <p class="text-center small text-muted mt-3 mb-0">Already a detective? <a href="login.php">Login here</a></p>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
