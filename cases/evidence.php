<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = 'Evidence Locker - ' . $case['title'];

$inv = get_or_create_investigation($pdo, $uid, $caseId);
$collected = json_ids($inv['evidence_collected']);

$allEvidence = $pdo->prepare('SELECT * FROM evidence WHERE case_id = :c ORDER BY relevance DESC');
$allEvidence->execute(['c' => $caseId]);
$allEvidence = $allEvidence->fetchAll();

$clueStmt = $pdo->prepare('SELECT * FROM clues WHERE evidence_id = :e');

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pd-reveal">
    <div>
      <div class="pd-eyebrow">Evidence Locker</div>
      <h3 class="mb-0"><?= htmlspecialchars($case['title']) ?></h3>
    </div>
    <a href="crime_scene.php?id=<?= $caseId ?>" class="btn btn-pd btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to Scene</a>
  </div>

  <?php if (!$collected): ?>
    <div class="alert alert-warning">Your locker is empty. Go back to the crime scene and search for evidence first.</div>
  <?php endif; ?>

  <div class="row g-3">
    <?php foreach ($allEvidence as $ev):
      $found = in_array($ev['id'], $collected, true); ?>
      <div class="col-md-6 pd-reveal">
        <div class="evidence-card h-100 <?= $found ? '' : 'opacity-50' ?>">
          <div class="d-flex justify-content-between align-items-start">
            <strong><?= $found ? htmlspecialchars($ev['name']) : '??? Undiscovered Evidence' ?></strong>
            <span class="rel-<?= htmlspecialchars($ev['relevance']) ?> small"><?= htmlspecialchars($ev['relevance']) ?></span>
          </div>
          <?php if ($found): ?>
            <div class="small text-muted mt-1"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($ev['type']) ?> &middot; <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($ev['location_found']) ?></div>
            <p class="small mt-2 mb-2"><?= htmlspecialchars($ev['description']) ?></p>
            <?php
              $clueStmt->execute(['e' => $ev['id']]);
              $clues = $clueStmt->fetchAll();
              if ($clues): ?>
                <div class="pd-panel p-2 mt-2">
                  <div class="small fw-semibold" style="color:var(--pd-accent)"><i class="fa-solid fa-lightbulb"></i> Analysis</div>
                  <?php foreach ($clues as $cl): ?>
                    <div class="small text-muted mt-1"><?= htmlspecialchars($cl['clue_text']) ?></div>
                  <?php endforeach; ?>
                </div>
            <?php endif; ?>
          <?php else: ?>
            <p class="small text-muted mt-2 mb-0">Search the crime scene to reveal this item.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-wrap gap-2 mt-4">
    <a href="witnesses.php?id=<?= $caseId ?>" class="btn btn-pd-solid"><i class="fa-solid fa-comments"></i> Interview Witnesses</a>
    <a href="suspects.php?id=<?= $caseId ?>" class="btn btn-pd"><i class="fa-solid fa-user-secret"></i> Suspect Board</a>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
