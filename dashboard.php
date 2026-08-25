<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();
$pageTitle = 'Dashboard';
$uid = current_user_id();

$user = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$user->execute(['id' => $uid]);
$user = $user->fetch();

$lb = $pdo->prepare('SELECT * FROM leaderboard WHERE user_id = :id');
$lb->execute(['id' => $uid]);
$lb = $lb->fetch();

// Rank
$rankStmt = $pdo->prepare('SELECT COUNT(*) + 1 AS rnk FROM leaderboard WHERE total_score > (SELECT total_score FROM leaderboard WHERE user_id = :id)');
$rankStmt->execute(['id' => $uid]);
$rank = $rankStmt->fetch()['rnk'];

$totalCases = $pdo->query('SELECT COUNT(*) c FROM cases WHERE status = "published"')->fetch()['c'];

$progress = $pdo->prepare('
  SELECT pp.*, c.title, c.difficulty, c.crime_type, c.estimated_time
  FROM player_progress pp JOIN cases c ON c.id = pp.case_id
  WHERE pp.user_id = :id ORDER BY pp.updated_at DESC');
$progress->execute(['id' => $uid]);
$progress = $progress->fetchAll();

$completedCount = 0; $inProgress = null;
foreach ($progress as $p) {
    if ($p['status'] === 'completed') $completedCount++;
    if ($p['status'] === 'in_progress' && !$inProgress) $inProgress = $p;
}
$progressPct = $totalCases > 0 ? round(($completedCount / $totalCases) * 100) : 0;

$recent = $pdo->prepare('
  SELECT i.*, c.title FROM investigation i JOIN cases c ON c.id = i.case_id
  WHERE i.user_id = :id ORDER BY i.started_at DESC LIMIT 5');
$recent->execute(['id' => $uid]);
$recent = $recent->fetchAll();

$topBoard = $pdo->query('
  SELECT l.*, u.full_name, u.username FROM leaderboard l JOIN users u ON u.id = l.user_id
  ORDER BY l.total_score DESC LIMIT 5')->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="container py-5">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pd-reveal">
    <div>
      <div class="pd-eyebrow">Detective Dashboard</div>
      <h2 class="mb-0">Welcome back, <?= htmlspecialchars($user['full_name']) ?></h2>
    </div>
    <a href="cases/index.php" class="btn btn-pd-solid mt-3 mt-md-0"><i class="fa-solid fa-folder-open"></i> Browse Cases</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3 pd-reveal">
      <div class="pd-panel p-3 text-center"><div class="pd-stat"><?= (int)$totalCases ?></div><div class="small text-muted">Total Cases</div></div>
    </div>
    <div class="col-6 col-md-3 pd-reveal">
      <div class="pd-panel p-3 text-center"><div class="pd-stat"><?= $completedCount ?></div><div class="small text-muted">Completed</div></div>
    </div>
    <div class="col-6 col-md-3 pd-reveal">
      <div class="pd-panel p-3 text-center"><div class="pd-stat"><?= (int)$user['total_score'] ?></div><div class="small text-muted">Total Score</div></div>
    </div>
    <div class="col-6 col-md-3 pd-reveal">
      <div class="pd-panel p-3 text-center"><div class="pd-stat">#<?= (int)$rank ?></div><div class="small text-muted">Current Rank</div></div>
    </div>
  </div>

  <div class="pd-panel p-4 mb-4 pd-reveal">
    <div class="d-flex justify-content-between mb-2">
      <span class="small text-muted">Overall Progress</span>
      <span class="small text-muted"><?= $progressPct ?>%</span>
    </div>
    <div class="pd-progress"><div style="width: <?= $progressPct ?>%"></div></div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <?php if ($inProgress): ?>
        <div class="pd-panel p-4 mb-4 pd-reveal">
          <div class="pd-eyebrow">Current Investigation</div>
          <h4 class="mt-2"><?= htmlspecialchars($inProgress['title']) ?></h4>
          <span class="pd-badge diff-<?= htmlspecialchars($inProgress['difficulty']) ?>"><?= htmlspecialchars($inProgress['difficulty']) ?></span>
          <span class="pd-badge ms-1"><?= htmlspecialchars($inProgress['crime_type']) ?></span>
          <div class="mt-3">
            <a href="cases/crime_scene.php?id=<?= $inProgress['case_id'] ?>" class="btn btn-pd-solid">Continue Investigation <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      <?php endif; ?>

      <div class="pd-panel p-4 pd-reveal">
        <div class="pd-eyebrow mb-3">Available Cases</div>
        <div class="row g-3">
          <?php foreach ($progress as $p): ?>
            <div class="col-md-6">
              <div class="evidence-card h-100">
                <div class="d-flex justify-content-between">
                  <strong><?= htmlspecialchars($p['title']) ?></strong>
                  <span class="pd-badge diff-<?= htmlspecialchars($p['difficulty']) ?>"><?= htmlspecialchars($p['difficulty']) ?></span>
                </div>
                <div class="small text-muted mt-1"><?= htmlspecialchars($p['crime_type']) ?> &middot; ~<?= (int)$p['estimated_time'] ?> min</div>
                <div class="mt-2">
                  <span class="badge <?= $p['status']==='completed' ? 'bg-success' : ($p['status']==='in_progress' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                    <?= ucwords(str_replace('_',' ',$p['status'])) ?>
                  </span>
                </div>
                <a href="cases/details.php?id=<?= $p['case_id'] ?>" class="btn btn-pd btn-sm mt-3">View Case</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="pd-panel p-4 mb-4 pd-reveal">
        <div class="pd-eyebrow mb-3">Recent Investigations</div>
        <?php if (!$recent): ?>
          <p class="small text-muted">No investigations started yet.</p>
        <?php else: foreach ($recent as $r): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--pd-border) !important;">
            <div>
              <div class="small fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
              <div class="small text-muted"><?= ucwords(str_replace('_',' ',$r['status'])) ?></div>
            </div>
            <span class="small" style="color:var(--pd-accent)"><?= (int)$r['score'] ?> pts</span>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="pd-panel p-4 pd-reveal">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="pd-eyebrow mb-0">Leaderboard</div>
          <a href="leaderboard.php" class="small">View all</a>
        </div>
        <?php foreach ($topBoard as $i => $t): ?>
          <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--pd-border) !important;">
            <div class="small"><span style="color:var(--pd-accent)">#<?= $i+1 ?></span> <?= htmlspecialchars($t['full_name']) ?></div>
            <div class="small"><?= (int)$t['total_score'] ?> pts</div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
