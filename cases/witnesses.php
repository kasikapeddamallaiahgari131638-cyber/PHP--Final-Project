<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = 'Witness Interviews - ' . $case['title'];

$inv = get_or_create_investigation($pdo, $uid, $caseId);
$interviewed = json_ids($inv['witnesses_interviewed']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['witness_id'])) {
    $wid = (int)$_POST['witness_id'];
    if (!in_array($wid, $interviewed, true)) {
        $interviewed[] = $wid;
        $stmt = $pdo->prepare('UPDATE investigation SET witnesses_interviewed = :w WHERE id = :id');
        $stmt->execute(['w' => json_encode($interviewed), 'id' => $inv['id']]);
        log_activity($pdo, $uid, 'witness_interviewed', 'Witness #' . $wid . ' (case #' . $caseId . ')');
    }
    header('Location: witnesses.php?id=' . $caseId . '&open=' . $wid);
    exit;
}

$witnesses = $pdo->prepare('SELECT * FROM witnesses WHERE case_id = :c');
$witnesses->execute(['c' => $caseId]);
$witnesses = $witnesses->fetchAll();

$openId = (int)($_GET['open'] ?? 0);

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pd-reveal">
    <div>
      <div class="pd-eyebrow">Witness Interviews</div>
      <h3 class="mb-0"><?= htmlspecialchars($case['title']) ?></h3>
    </div>
    <div class="text-end small text-muted">Interviewed <?= count($interviewed) ?> / <?= count($witnesses) ?></div>
  </div>

  <div class="accordion" id="witnessAccordion">
    <?php foreach ($witnesses as $w):
      $done = in_array($w['id'], $interviewed, true);
      $dialogue = json_decode($w['dialogue'], true) ?: [];
      $expand = ($openId === (int)$w['id']) || (!$openId && $w === $witnesses[0]);
    ?>
      <div class="pd-panel mb-3 pd-reveal overflow-hidden">
        <div class="p-3 p-md-4 d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#w<?= $w['id'] ?>" style="cursor:pointer;">
          <div>
            <strong><?= htmlspecialchars($w['name']) ?></strong>
            <span class="small text-muted d-block"><?= htmlspecialchars($w['relationship']) ?></span>
          </div>
          <span class="badge <?= $done ? 'bg-success' : 'bg-secondary' ?>"><?= $done ? 'Interviewed' : 'Not Interviewed' ?></span>
        </div>
        <div id="w<?= $w['id'] ?>" class="collapse <?= $expand ? 'show' : '' ?>">
          <div class="p-3 p-md-4 pt-0">
            <?php foreach ($dialogue as $qa): ?>
              <div class="mb-3">
                <div class="small fw-semibold" style="color:var(--pd-accent)"><i class="fa-solid fa-quote-left"></i> <?= htmlspecialchars($qa['q']) ?></div>
                <div class="small text-muted mt-1">"<?= htmlspecialchars($qa['a']) ?>"</div>
              </div>
            <?php endforeach; ?>

            <?php if ($done): ?>
              <div class="pd-panel p-3 mt-2">
                <div class="small fw-semibold" style="color:var(--pd-accent)"><i class="fa-solid fa-lightbulb"></i> Important Clue</div>
                <p class="small text-muted mb-2"><?= htmlspecialchars($w['important_clue']) ?></p>
                <?php if ($w['contradiction']): ?>
                  <div class="small fw-semibold text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Contradiction Found</div>
                  <p class="small text-muted mb-0"><?= htmlspecialchars($w['contradiction']) ?></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <form method="POST">
                <input type="hidden" name="witness_id" value="<?= (int)$w['id'] ?>">
                <button type="submit" class="btn btn-pd-solid btn-sm mt-2"><i class="fa-solid fa-check"></i> Mark Interview Complete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-wrap gap-2 mt-4">
    <a href="evidence.php?id=<?= $caseId ?>" class="btn btn-pd"><i class="fa-solid fa-box-archive"></i> Evidence Locker</a>
    <a href="suspects.php?id=<?= $caseId ?>" class="btn btn-pd-solid"><i class="fa-solid fa-user-secret"></i> Suspect Board</a>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
