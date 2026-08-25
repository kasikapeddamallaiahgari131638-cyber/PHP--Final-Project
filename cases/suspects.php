<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = 'Suspect Board - ' . $case['title'];

get_or_create_investigation($pdo, $uid, $caseId);

$suspects = $pdo->prepare('SELECT * FROM suspects WHERE case_id = :c');
$suspects->execute(['c' => $caseId]);
$suspects = $suspects->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pd-reveal">
    <div>
      <div class="pd-eyebrow">Suspect Board</div>
      <h3 class="mb-0"><?= htmlspecialchars($case['title']) ?></h3>
    </div>
    <span class="small text-muted"><?= count($suspects) ?> suspects identified</span>
  </div>

  <div class="row g-4">
    <?php foreach ($suspects as $s): ?>
      <div class="col-md-6 col-lg-4 pd-reveal">
        <div class="suspect-card h-100 suspicion-<?= htmlspecialchars($s['suspicion_level']) ?>">
          <div class="d-flex align-items-center gap-3 mb-2">
            <img src="<?= htmlspecialchars('../' . $s['photo']) ?>" width="60" height="60" class="rounded-circle" style="object-fit:cover;" alt="">
            <div>
              <strong><?= htmlspecialchars($s['name']) ?></strong>
              <div class="small text-muted"><?= htmlspecialchars($s['occupation']) ?>, <?= (int)$s['age'] ?></div>
            </div>
          </div>
          <div class="small text-muted mb-2"><i class="fa-solid fa-people-arrows"></i> <?= htmlspecialchars($s['relationship_to_victim']) ?></div>

          <div class="mb-2">
            <span class="badge">Suspicion: <?= htmlspecialchars($s['suspicion_level']) ?></span>
          </div>

          <div class="small mt-2"><strong style="color:var(--pd-accent)">Motive:</strong> <?= htmlspecialchars($s['motive']) ?></div>
          <div class="small mt-1"><strong style="color:var(--pd-accent)">Alibi:</strong> <?= htmlspecialchars($s['alibi']) ?></div>
          <div class="small mt-1"><strong class="text-danger">Against:</strong> <?= htmlspecialchars($s['evidence_against']) ?></div>
          <div class="small mt-1"><strong class="text-success">Supporting:</strong> <?= htmlspecialchars($s['evidence_supporting']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-wrap gap-2 mt-4">
    <a href="witnesses.php?id=<?= $caseId ?>" class="btn btn-pd"><i class="fa-solid fa-comments"></i> Witness Interviews</a>
    <a href="deduction.php?id=<?= $caseId ?>" class="btn btn-pd-solid"><i class="fa-solid fa-diagram-project"></i> Go to Deduction Board</a>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
