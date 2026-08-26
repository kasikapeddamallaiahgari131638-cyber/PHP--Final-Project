<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Work out relative path prefix so this header works from root, /cases/, /admin/
$script = $_SERVER['SCRIPT_NAME'];
$inSub  = (strpos($script, '/cases/') !== false || strpos($script, '/admin/') !== false);
$root   = $inSub ? '../' : '';
$pageTitle = $pageTitle ?? 'Phantom Detective';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | Phantom Detective</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= $root ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark pd-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand pd-brand" href="<?= $root ?>index.php">
      <i class="fa-solid fa-magnifying-glass"></i> PHANTOM <span>DETECTIVE</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pdNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="pdNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>cases/index.php"><i class="fa-solid fa-folder-open"></i> Cases</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>leaderboard.php"><i class="fa-solid fa-ranking-star"></i> Leaderboard</a></li>
          <li class="nav-item">
            <span class="nav-link pd-badge-name"><i class="fa-solid fa-user-secret"></i> <?= htmlspecialchars($_SESSION['full_name'] ?? 'Detective') ?></span>
          </li>
          <li class="nav-item"><a class="btn btn-outline-danger btn-sm ms-lg-2" href="<?= $root ?>logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>index.php#features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= $root ?>index.php#cases">Cases</a></li>
          <li class="nav-item"><a class="btn btn-outline-light btn-sm me-lg-2" href="<?= $root ?>login.php">Login</a></li>
          <li class="nav-item"><a class="btn pd-btn-accent btn-sm" href="<?= $root ?>register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
