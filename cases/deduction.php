<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = 'Deduction Board - ' . $case['title'];

$inv = get_or_create_investigation($pdo, $uid, $caseId);
$collected = json_ids($inv['evidence_collected']);
$interviewed = json_ids($inv['witnesses_interviewed']);

$evidence = $pdo->prepare('SELECT * FROM evidence WHERE id IN (' . (count($collected) ? implode(',', array_map('intval', $collected)) : '0') . ') AND case_id = :c');
$evidence->execute(['c' => $caseId]);
$evidence = $evidence->fetchAll();

$suspects = $pdo->prepare('SELECT * FROM suspects WHERE case_id = :c');
$suspects->execute(['c' => $caseId]);
$suspects = $suspects->fetchAll();

$notes = json_decode($inv['deduction_notes'] ?: '{}', true) ?: [];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = [
        'evidence_id' => (int)($_POST['evidence_id'] ?? 0),
        'suspect_id'  => (int)($_POST['suspect_id'] ?? 0),
        'motive'      => trim($_POST['motive'] ?? ''),
        'opportunity' => trim($_POST['opportunity'] ?? ''),
        'conclusion'  => trim($_POST['conclusion'] ?? ''),
    ];
    if (!$notes['evidence_id'] || !$notes['suspect_id'] || !$notes['motive'] || !$notes['conclusion']) {
        $error = 'Please complete every link in the deduction chain before proceeding.';
    } else {
        $stmt = $pdo->prepare('UPDATE investigation SET deduction_notes = :n WHERE id = :id');
        $stmt->execute(['n' => json_encode($notes), 'id' => $inv['id']]);
        update_progress_percentage($pdo, $uid, $caseId, 80);
        header('Location: verdict.php?id=' . $caseId);
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="mb-4 pd-reveal">
    <div class="pd-eyebrow">Deduction Board</div>
    <h3 class="mb-0"><?= htmlspecialchars($case['title']) ?></h3>
    <p class="text-muted small mt-2">Connect the pieces: Evidence &rarr; Suspect &rarr; Motive &rarr; Opportunity &rarr; Conclusion.</p>
  </div>

  <?php if (!$evidence || !$interviewed): ?>
    <div class="alert alert-warning">
      You should collect evidence and interview witnesses before deducing. You can still proceed, but your case will be weaker.
    </div>
  <?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST" class="pd-panel p-4 p-md-5 pd-reveal">
    <div class="row g-4 align-items-center text-center mb-4">
      <div class="col">
        <div class="deduction-slot <?= !empty($notes['evidence_id']) ? 'filled' : '' ?>">
          <i class="fa-solid fa-magnifying-glass mb-1 d-block"></i> Evidence
        </div>
      </div>
      <div class="col-auto deduction-arrow"><i class="fa-solid fa-arrow-right"></i></div>
      <div class="col">
        <div class="deduction-slot <?= !empty($notes['suspect_id']) ? 'filled' : '' ?>">
          <i class="fa-solid fa-user-secret mb-1 d-block"></i> Suspect
        </div>
      </div>
      <div class="col-auto deduction-arrow"><i class="fa-solid fa-arrow-right"></i></div>
      <div class="col">
        <div class="deduction-slot <?= !empty($notes['motive']) ? 'filled' : '' ?>">
          <i class="fa-solid fa-brain mb-1 d-block"></i> Motive
        </div>
      </div>
      <div class="col-auto deduction-arrow"><i class="fa-solid fa-arrow-right"></i></div>
      <div class="col">
        <div class="deduction-slot <?= !empty($notes['conclusion']) ? 'filled' : '' ?>">
          <i class="fa-solid fa-flag-checkered mb-1 d-block"></i> Conclusion
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label>1. Which evidence points to your suspect? (Evidence &rarr; Clue)</label>
        <select name="evidence_id" class="form-select" required>
          <option value="">-- Select evidence --</option>
          <?php foreach ($evidence as $ev): ?>
            <option value="<?= $ev['id'] ?>" <?= (int)($notes['evidence_id'] ?? 0) === (int)$ev['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ev['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label>2. Who is your suspect? (Suspect)</label>
        <select name="suspect_id" class="form-select" required>
          <option value="">-- Select suspect --</option>
          <?php foreach ($suspects as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (int)($notes['suspect_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label>3. What is their motive?</label>
        <input type="text" name="motive" class="form-control" value="<?= htmlspecialchars($notes['motive'] ?? '') ?>" placeholder="e.g. financial desperation, jealousy, revenge" required>
      </div>
      <div class="col-md-6">
        <label>4. What was their opportunity?</label>
        <input type="text" name="opportunity" class="form-control" value="<?= htmlspecialchars($notes['opportunity'] ?? '') ?>" placeholder="e.g. alone backstage during the murder window">
      </div>
      <div class="col-12">
        <label>5. Final conclusion (your reasoning)</label>
        <textarea name="conclusion" rows="4" class="form-control" placeholder="Explain, in your own words, how the evidence and testimony point to this suspect." required><?= htmlspecialchars($notes['conclusion'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
      <button type="submit" class="btn btn-pd-solid btn-lg"><i class="fa-solid fa-gavel"></i> Proceed to Final Verdict</button>
      <a href="suspects.php?id=<?= $caseId ?>" class="btn btn-pd btn-lg">Back to Suspects</a>
    </div>
  </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
