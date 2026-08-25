<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/game_helpers.php';
require_login();
$uid = current_user_id();
$caseId = (int)($_GET['id'] ?? 0);
$case = get_case_or_404($pdo, $caseId);
$pageTitle = 'Final Verdict - ' . $case['title'];

$inv = get_or_create_investigation($pdo, $uid, $caseId);
$collected = json_ids($inv['evidence_collected']);
$interviewed = json_ids($inv['witnesses_interviewed']);
$notes = json_decode($inv['deduction_notes'] ?: '{}', true) ?: [];

$stmt = $pdo->prepare('SELECT COUNT(*) c FROM evidence WHERE case_id = :c'); $stmt->execute(['c'=>$caseId]); $totalEvidence = (int)$stmt->fetch()['c'];
$stmt = $pdo->prepare('SELECT COUNT(*) c FROM witnesses WHERE case_id = :c'); $stmt->execute(['c'=>$caseId]); $totalWitnesses = (int)$stmt->fetch()['c'];

$suspects = $pdo->prepare('SELECT * FROM suspects WHERE case_id = :c');
$suspects->execute(['c' => $caseId]);
$suspects = $suspects->fetchAll();

$culprit = null;
foreach ($suspects as $s) { if ($s['is_culprit']) { $culprit = $s; break; } }

$allEvStmt = $pdo->prepare('SELECT * FROM evidence WHERE case_id = :c');
$allEvStmt->execute(['c' => $caseId]);
$allEvidenceList = $allEvStmt->fetchAll();

$alreadyCompleted = $inv['status'] === 'completed';
$result = null;

if (!$alreadyCompleted && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $verdictSuspect = (int)($_POST['suspect_id'] ?? 0);
    $verdictEvidence = (int)($_POST['evidence_id'] ?? 0);
    $verdictMotive = trim($_POST['motive'] ?? '');
    $verdictExplanation = trim($_POST['explanation'] ?? '');

    $isCorrect = ($culprit && $verdictSuspect === (int)$culprit['id']) ? 1 : 0;

    $evidencePct = $totalEvidence > 0 ? count($collected) / $totalEvidence : 0;
    $witnessPct  = $totalWitnesses > 0 ? count($interviewed) / $totalWitnesses : 0;

    $evidenceScore  = round($evidencePct * 30);
    $witnessScore   = round($witnessPct * 20);
    $deductionScore = $isCorrect ? 40 : 0;

    $minutesTaken = max(1, round((time() - strtotime($inv['started_at'])) / 60));
    $timeBonus = ($minutesTaken <= ($case['estimated_time'] * 2)) ? 10 : 0;

    $score = $evidenceScore + $witnessScore + $deductionScore + $timeBonus;
    $accuracy = round((($evidencePct * 0.4) + ($witnessPct * 0.3) + ($isCorrect * 0.3)) * 100, 2);

    $upd = $pdo->prepare('
        UPDATE investigation SET
          status = "completed",
          verdict_suspect_id = :vs, verdict_evidence_id = :ve,
          verdict_motive = :vm, verdict_explanation = :vx,
          is_correct = :ic, score = :sc, accuracy = :ac, completed_at = NOW()
        WHERE id = :id');
    $upd->execute([
        'vs' => $verdictSuspect, 've' => $verdictEvidence, 'vm' => $verdictMotive,
        'vx' => $verdictExplanation, 'ic' => $isCorrect, 'sc' => $score, 'ac' => $accuracy,
        'id' => $inv['id'],
    ]);

    update_progress_percentage($pdo, $uid, $caseId, 100);

    // Recompute aggregate user stats from all completed investigations (avoids double counting on retries)
    $agg = $pdo->prepare('SELECT COUNT(*) cnt, COALESCE(SUM(score),0) total, COALESCE(AVG(accuracy),0) avgacc
                           FROM investigation WHERE user_id = :u AND status = "completed"');
    $agg->execute(['u' => $uid]);
    $agg = $agg->fetch();

    $pdo->prepare('UPDATE users SET total_score = :t, cases_completed = :c WHERE id = :id')
        ->execute(['t' => (int)$agg['total'], 'c' => (int)$agg['cnt'], 'id' => $uid]);

    $pdo->prepare('
        INSERT INTO leaderboard (user_id, total_score, cases_solved, accuracy)
        VALUES (:u, :t1, :c1, :a1)
        ON DUPLICATE KEY UPDATE total_score = :t2, cases_solved = :c2, accuracy = :a2')
        ->execute([
            'u' => $uid,
            't1' => (int)$agg['total'], 'c1' => (int)$agg['cnt'], 'a1' => (float)$agg['avgacc'],
            't2' => (int)$agg['total'], 'c2' => (int)$agg['cnt'], 'a2' => (float)$agg['avgacc'],
        ]);

    log_activity($pdo, $uid, 'verdict_submitted', ($isCorrect ? 'Correct' : 'Incorrect') . " verdict on case #$caseId, score $score");

    $result = [
        'correct' => $isCorrect, 'score' => $score, 'accuracy' => $accuracy,
        'evidenceScore' => $evidenceScore, 'witnessScore' => $witnessScore,
        'deductionScore' => $deductionScore, 'timeBonus' => $timeBonus,
        'minutesTaken' => $minutesTaken,
    ];
    // refresh $inv for display below
    $stmt = $pdo->prepare('SELECT * FROM investigation WHERE id = :id');
    $stmt->execute(['id' => $inv['id']]);
    $inv = $stmt->fetch();
    $alreadyCompleted = true;
}

include __DIR__ . '/../includes/header.php';
?>
<section class="container py-5">
  <div class="mb-4 pd-reveal">
    <div class="pd-eyebrow">Final Verdict</div>
    <h3 class="mb-0"><?= htmlspecialchars($case['title']) ?></h3>
  </div>

  <?php if ($alreadyCompleted): ?>
    <div class="pd-panel p-4 p-md-5 text-center pd-reveal">
      <?php if ($inv['is_correct']): ?>
        <i class="fa-solid fa-circle-check fa-3x mb-3" style="color:var(--pd-success)"></i>
        <h2 class="text-success">Case Solved!</h2>
        <p class="text-muted">Your deduction was correct. Justice has been served.</p>
      <?php else: ?>
        <i class="fa-solid fa-circle-xmark fa-3x mb-3" style="color:var(--pd-danger)"></i>
        <h2 class="text-danger">Incorrect Verdict</h2>
        <p class="text-muted">The real culprit was <strong><?= htmlspecialchars($culprit['name'] ?? 'unknown') ?></strong>.</p>
      <?php endif; ?>

      <div class="row g-3 my-4">
        <div class="col-6 col-md-3"><div class="pd-panel p-3"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$inv['score'] ?></div><div class="small text-muted">Score</div></div></div>
        <div class="col-6 col-md-3"><div class="pd-panel p-3"><div class="pd-stat" style="font-size:1.6rem"><?= number_format((float)$inv['accuracy'],1) ?>%</div><div class="small text-muted">Accuracy</div></div></div>
        <div class="col-6 col-md-3"><div class="pd-panel p-3"><div class="pd-stat" style="font-size:1.6rem"><?= count($collected) ?>/<?= $totalEvidence ?></div><div class="small text-muted">Evidence</div></div></div>
        <div class="col-6 col-md-3"><div class="pd-panel p-3"><div class="pd-stat" style="font-size:1.6rem"><?= count($interviewed) ?>/<?= $totalWitnesses ?></div><div class="small text-muted">Witnesses</div></div></div>
      </div>

      <div class="pd-panel p-4 text-start">
        <div class="pd-eyebrow mb-2">The True Solution</div>
        <p class="small text-muted mb-0"><?= htmlspecialchars($case['solution_explanation']) ?></p>
      </div>

      <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
        <a href="../cases/index.php" class="btn btn-pd-solid"><i class="fa-solid fa-folder-open"></i> More Cases</a>
        <a href="../leaderboard.php" class="btn btn-pd"><i class="fa-solid fa-ranking-star"></i> Leaderboard</a>
        <a href="../dashboard.php" class="btn btn-pd">Dashboard</a>
      </div>
    </div>
  <?php else: ?>
    <?php if (!$notes): ?>
      <div class="alert alert-warning">Please complete the <a href="deduction.php?id=<?= $caseId ?>">Deduction Board</a> first.</div>
    <?php endif; ?>

    <form method="POST" class="pd-panel p-4 p-md-5 pd-reveal">
      <p class="text-muted small">This is it, detective. Make your final accusation. Choose carefully &mdash; you only get one official verdict per case.</p>

      <div class="row g-3">
        <div class="col-md-6">
          <label>Suspected Culprit</label>
          <select name="suspect_id" class="form-select" required>
            <option value="">-- Choose the culprit --</option>
            <?php foreach ($suspects as $s): ?>
              <option value="<?= $s['id'] ?>" <?= (int)($notes['suspect_id'] ?? 0)===(int)$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label>Main Evidence</label>
          <select name="evidence_id" class="form-select" required>
            <option value="">-- Choose key evidence --</option>
            <?php foreach ($allEvidenceList as $ev): ?>
              <option value="<?= $ev['id'] ?>" <?= (int)($notes['evidence_id'] ?? 0)===(int)$ev['id']?'selected':'' ?>><?= htmlspecialchars($ev['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label>Motive</label>
          <input type="text" name="motive" class="form-control" value="<?= htmlspecialchars($notes['motive'] ?? '') ?>" required>
        </div>
        <div class="col-12">
          <label>Explanation</label>
          <textarea name="explanation" rows="4" class="form-control" required><?= htmlspecialchars($notes['conclusion'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-pd-danger btn-lg"><i class="fa-solid fa-gavel"></i> Submit Final Verdict</button>
        <a href="deduction.php?id=<?= $caseId ?>" class="btn btn-pd btn-lg">Back to Deduction Board</a>
      </div>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
