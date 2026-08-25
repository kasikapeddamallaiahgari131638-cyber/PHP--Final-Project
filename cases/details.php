<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = $case['title'];

$evCount = $pdo->prepare('SELECT COUNT(*) c FROM evidence WHERE case_id = :c');
$evCount->execute(['c' => $caseId]);
$evCount = $evCount->fetch()['c'];

$wCount = $pdo->prepare('SELECT COUNT(*) c FROM witnesses WHERE case_id = :c');
$wCount->execute(['c' => $caseId]);
$wCount = $wCount->fetch()['c'];

$sCount = $pdo->prepare('SELECT COUNT(*) c FROM suspects WHERE case_id = :c');
$sCount->execute(['c' => $caseId]);
$sCount = $sCount->fetch()['c'];

$pp = $pdo->prepare('SELECT * FROM player_progress WHERE user_id = :u AND case_id = :c');
$pp->execute(['u' => $uid, 'c' => $caseId]);
$pp = $pp->fetch();

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="pd-panel p-4 p-md-5 pd-reveal">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <span class="pd-badge diff-<?= htmlspecialchars($case['difficulty']) ?>"><?= htmlspecialchars($case['difficulty']) ?></span>
        <span class="pd-badge ms-1"><?= htmlspecialchars($case['crime_type']) ?></span>
        <h2 class="mt-3">Case #<?= (int)$case['id'] ?>: <?= htmlspecialchars($case['title']) ?></h2>
        <p class="text-muted"><i class="fa-solid fa-user"></i> Victim: <strong><?= htmlspecialchars($case['victim_name']) ?></strong></p>
      </div>
      <img src="../assets/images/scene_default.svg" width="180" class="rounded d-none d-md-block" alt="">
    </div>

    <p class="mt-3"><?= nl2br(htmlspecialchars($case['description'])) ?></p>

    <div class="row g-3 my-3">
      <div class="col-4"><div class="pd-panel text-center p-3"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$evCount ?></div><div class="small text-muted">Evidence Items</div></div></div>
      <div class="col-4"><div class="pd-panel text-center p-3"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$wCount ?></div><div class="small text-muted">Witnesses</div></div></div>
      <div class="col-4"><div class="pd-panel text-center p-3"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$sCount ?></div><div class="small text-muted">Suspects</div></div></div>
    </div>

    <?php if ($pp && $pp['status'] === 'completed'): ?>
      <div class="alert alert-success">You have already completed this case. You may review it, but your recorded score won't change.</div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-3 mt-4">
      <a href="crime_scene.php?id=<?= $caseId ?>" class="btn btn-pd-solid btn-lg">
        <i class="fa-solid fa-magnifying-glass"></i> <?= $pp && $pp['status']!=='not_started' ? 'Continue Investigation' : 'Begin Investigation' ?>
      </a>
      <a href="index.php" class="btn btn-pd btn-lg">Back to Cases</a>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
