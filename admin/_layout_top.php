<?php
// Expects $pageTitle and $activeNav to be set before include.
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> | Phantom Detective Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <div class="sidebar-admin p-3" style="width:230px; min-height:100vh;">
    <a href="dashboard.php" class="pd-brand d-block mb-4"><i class="fa-solid fa-magnifying-glass"></i> PHANTOM <span>ADMIN</span></a>
    <a href="dashboard.php" class="<?= $activeNav==='dashboard'?'active':'' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="users.php" class="<?= $activeNav==='users'?'active':'' ?>"><i class="fa-solid fa-users"></i> Users</a>
    <a href="cases.php" class="<?= $activeNav==='cases'?'active':'' ?>"><i class="fa-solid fa-folder-open"></i> Cases</a>
    <a href="evidence.php" class="<?= $activeNav==='evidence'?'active':'' ?>"><i class="fa-solid fa-box-archive"></i> Evidence</a>
    <a href="witnesses.php" class="<?= $activeNav==='witnesses'?'active':'' ?>"><i class="fa-solid fa-comments"></i> Witnesses</a>
    <a href="suspects.php" class="<?= $activeNav==='suspects'?'active':'' ?>"><i class="fa-solid fa-user-secret"></i> Suspects</a>
    <a href="leaderboard.php" class="<?= $activeNav==='leaderboard'?'active':'' ?>"><i class="fa-solid fa-ranking-star"></i> Leaderboard</a>
    <a href="reports.php" class="<?= $activeNav==='reports'?'active':'' ?>"><i class="fa-solid fa-chart-column"></i> Reports</a>
    <hr class="border-secondary">
    <a href="../index.php"><i class="fa-solid fa-house"></i> Visit Site</a>
    <a href="logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
  <div class="flex-grow-1 p-4" style="max-width:calc(100% - 230px);">
