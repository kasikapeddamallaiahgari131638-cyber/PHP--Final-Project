<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_login();
$pageTitle = 'Case Library';
$uid = current_user_id();

$cases = $pdo->prepare('
  SELECT c.*, pp.status AS progress_status, pp.progress_percentage
  FROM cases c
  LEFT JOIN player_progress pp ON pp.case_id = c.id AND pp.user_id = :u
  WHERE c.status = "published"
  ORDER BY FIELD(c.difficulty, "Easy","Medium","Hard"), c.id');
$cases->execute(['u' => $uid]);
$cases = $cases->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="text-center mb-5 pd-reveal">
    <div class="pd-eyebrow">Case Library</div>
    <h2 class="mt-2">Choose Your Investigation</h2>
    <p class="text-muted">Every case has its own crime, evidence, witnesses, and solution.</p>
  </div>

  <div class="row g-4">
    <?php foreach ($cases as $c): ?>
      <div class="col-md-4 pd-reveal">
        <div class="pd-card h-100 p-4 d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start">
            <span class="pd-badge diff-<?= htmlspecialchars($c['difficulty']) ?>"><?= htmlspecialchars($c['difficulty']) ?></span>
            <span class="badge <?= $c['progress_status']==='completed' ? 'bg-success' : ($c['progress_status']==='in_progress' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
              <?= ucwords(str_replace('_',' ', $c['progress_status'] ?? 'not started')) ?>
            </span>
          </div>
          <h4 class="mt-3">#<?= (int)$c['id'] ?> &middot; <?= htmlspecialchars($c['title']) ?></h4>
          <p class="text-muted small flex-grow-1"><?= htmlspecialchars($c['description']) ?></p>
          <div class="small text-muted mb-2">
            <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($c['crime_type']) ?>
            &middot; <i class="fa-regular fa-clock"></i> ~<?= (int)$c['estimated_time'] ?> min
          </div>
          <?php if ($c['progress_percentage']): ?>
            <div class="pd-progress mb-3"><div style="width:<?= (int)$c['progress_percentage'] ?>%"></div></div>
          <?php endif; ?>
          <a href="details.php?id=<?= (int)$c['id'] ?>" class="btn btn-pd-solid mt-auto">
            <?= $c['progress_status']==='in_progress' ? 'Continue' : ($c['progress_status']==='completed' ? 'Review Case' : 'Start Investigation') ?>
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
