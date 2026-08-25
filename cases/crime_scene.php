<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = 'Crime Scene - ' . $case['title'];

$inv = get_or_create_investigation($pdo, $uid, $caseId);
$collected = json_ids($inv['evidence_collected']);

// Handle "search" click -> collect an evidence item
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evidence_id'])) {
    $evId = (int)$_POST['evidence_id'];
    if (!in_array($evId, $collected, true)) {
        $collected[] = $evId;
        $stmt = $pdo->prepare('UPDATE investigation SET evidence_collected = :e WHERE id = :id');
        $stmt->execute(['e' => json_encode($collected), 'id' => $inv['id']]);

        $evStmt = $pdo->prepare('SELECT * FROM evidence WHERE id = :id');
        $evStmt->execute(['id' => $evId]);
        $ev = $evStmt->fetch();
        $flash = 'Evidence discovered: ' . $ev['name'];
        log_activity($pdo, $uid, 'evidence_found', $ev['name'] . ' (case #' . $caseId . ')');
    }
    header('Location: crime_scene.php?id=' . $caseId . '&found=1');
    exit;
}
if (isset($_GET['found'])) { $flash = 'Evidence added to your locker.'; }

$evidence = $pdo->prepare('SELECT * FROM evidence WHERE case_id = :c');
$evidence->execute(['c' => $caseId]);
$evidence = $evidence->fetchAll();

$totalEv = count($evidence);
$foundCount = count(array_intersect(array_column($evidence, 'id'), $collected));

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pd-reveal">
    <div>
      <div class="pd-eyebrow">Crime Scene</div>
      <h3 class="mb-0"><?= htmlspecialchars($case['title']) ?></h3>
    </div>
    <div class="text-end">
      <div class="small text-muted">Evidence Found</div>
      <div class="fw-bold" style="color:var(--pd-accent)"><?= $foundCount ?> / <?= $totalEv ?></div>
    </div>
  </div>

  <?php if ($flash): ?><div class="alert alert-success" data-autohide><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($flash) ?></div><?php endif; ?>

  <div class="pd-panel p-3 mb-4 pd-reveal">
    <p class="small text-muted mb-0"><i class="fa-solid fa-circle-info"></i> <?= nl2br(htmlspecialchars($case['crime_scene_desc'])) ?></p>
  </div>

  <div class="row g-4">
    <div class="col-lg-7 pd-reveal">
      <div class="crime-scene-wrap">
        <img src="<?= htmlspecialchars('../' . $case['crime_scene_image']) ?>" class="w-100 h-100" style="object-fit:cover;" alt="Crime scene">
        <?php foreach ($evidence as $ev):
          $found = in_array($ev['id'], $collected, true); ?>
          <form method="POST" style="position:absolute; left:<?= (int)$ev['hotspot_x'] ?>%; top:<?= (int)$ev['hotspot_y'] ?>%; transform: translate(-50%,-50%);">
            <input type="hidden" name="evidence_id" value="<?= (int)$ev['id'] ?>">
            <button type="submit" class="hotspot border-0 <?= $found ? 'found' : '' ?>" title="<?= $found ? htmlspecialchars($ev['name']) : 'Search here' ?>" <?= $found ? 'disabled' : '' ?>>
              <i class="fa-solid <?= $found ? 'fa-check' : 'fa-magnifying-glass' ?>"></i>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
      <p class="small text-muted mt-2"><i class="fa-solid fa-hand-pointer"></i> Click the glowing markers on the scene to search for evidence.</p>
    </div>

    <div class="col-lg-5 pd-reveal">
      <div class="pd-panel p-4">
        <div class="pd-eyebrow mb-3">Investigation Notes</div>
        <?php if ($foundCount === 0): ?>
          <p class="small text-muted">Nothing discovered yet. Start searching the scene.</p>
        <?php else: foreach ($evidence as $ev): if (!in_array($ev['id'], $collected, true)) continue; ?>
          <div class="evidence-card mb-2">
            <div class="d-flex justify-content-between">
              <strong class="small"><?= htmlspecialchars($ev['name']) ?></strong>
              <span class="small rel-<?= htmlspecialchars($ev['relevance']) ?>"><?= htmlspecialchars($ev['relevance']) ?></span>
            </div>
            <div class="small text-muted"><?= htmlspecialchars($ev['location_found']) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="d-flex flex-column gap-2 mt-3">
        <a href="evidence.php?id=<?= $caseId ?>" class="btn btn-pd">
          <i class="fa-solid fa-box-archive"></i> Open Evidence Locker
        </a>
        <a href="witnesses.php?id=<?= $caseId ?>" class="btn btn-pd">
          <i class="fa-solid fa-comments"></i> Interview Witnesses
        </a>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
