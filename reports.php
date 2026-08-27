<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Reports';
$activeNav = 'reports';

$totalPlayers = $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
$totalCases = $pdo->query('SELECT COUNT(*) c FROM cases')->fetch()['c'];
$totalCompleted = $pdo->query('SELECT COUNT(*) c FROM investigation WHERE status="completed"')->fetch()['c'];
$avgScore = $pdo->query('SELECT COALESCE(ROUND(AVG(score),1),0) c FROM investigation WHERE status="completed"')->fetch()['c'];

$topDetectives = $pdo->query('SELECT u.full_name, l.total_score FROM leaderboard l JOIN users u ON u.id=l.user_id ORDER BY l.total_score DESC LIMIT 5')->fetchAll();
$mostPlayed = $pdo->query('SELECT c.title, COUNT(i.id) plays FROM investigation i JOIN cases c ON c.id=i.case_id GROUP BY c.id ORDER BY plays DESC LIMIT 5')->fetchAll();
$completionByCase = $pdo->query('
  SELECT c.title,
    SUM(CASE WHEN i.status="completed" THEN 1 ELSE 0 END) completed,
    SUM(CASE WHEN i.status="in_progress" THEN 1 ELSE 0 END) in_progress
  FROM cases c LEFT JOIN investigation i ON i.case_id = c.id
  GROUP BY c.id')->fetchAll();
$successRate = $pdo->query('
  SELECT c.title, SUM(CASE WHEN i.is_correct=1 THEN 1 ELSE 0 END) correct, COUNT(i.id) total
  FROM investigation i JOIN cases c ON c.id = i.case_id WHERE i.status="completed" GROUP BY c.id')->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<h3 class="mb-4">Reports &amp; Analytics</h3>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$totalPlayers ?></div><div class="small text-muted">Registered Players</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$totalCases ?></div><div class="small text-muted">Cases Available</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.6rem"><?= (int)$totalCompleted ?></div><div class="small text-muted">Cases Completed</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.6rem"><?= $avgScore ?></div><div class="small text-muted">Average Score</div></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="pd-panel p-4">
      <div class="pd-eyebrow mb-3">Most Successful Detectives</div>
      <canvas id="chartDetectives" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="pd-panel p-4">
      <div class="pd-eyebrow mb-3">Most Played Cases</div>
      <canvas id="chartPlayed" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="pd-panel p-4">
      <div class="pd-eyebrow mb-3">Case Completion Status</div>
      <canvas id="chartCompletion" height="220"></canvas>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="pd-panel p-4">
      <div class="pd-eyebrow mb-3">Verdict Success Rate by Case</div>
      <canvas id="chartSuccess" height="220"></canvas>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#9aa0ad';
Chart.defaults.borderColor = '#2a2f3d';
const gold = '#c8a24a', red = '#8b1e1e', green='#4caf6d';

new Chart(document.getElementById('chartDetectives'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($topDetectives,'full_name')) ?>,
    datasets: [{ label: 'Score', data: <?= json_encode(array_map('intval', array_column($topDetectives,'total_score'))) ?>, backgroundColor: gold }]
  },
  options: { plugins: { legend: { display:false } } }
});

new Chart(document.getElementById('chartPlayed'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($mostPlayed,'title')) ?>,
    datasets: [{ label: 'Plays', data: <?= json_encode(array_map('intval', array_column($mostPlayed,'plays'))) ?>, backgroundColor: '#8fb3d9' }]
  },
  options: { indexAxis: 'y', plugins: { legend: { display:false } } }
});

new Chart(document.getElementById('chartCompletion'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($completionByCase,'title')) ?>,
    datasets: [
      { label: 'Completed', data: <?= json_encode(array_map('intval', array_column($completionByCase,'completed'))) ?>, backgroundColor: green },
      { label: 'In Progress', data: <?= json_encode(array_map('intval', array_column($completionByCase,'in_progress'))) ?>, backgroundColor: gold }
    ]
  },
  options: { scales: { x: { stacked:true }, y: { stacked:true } } }
});

new Chart(document.getElementById('chartSuccess'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($successRate,'title')) ?>,
    datasets: [{ data: <?= json_encode(array_map(function($r){ return $r['total']>0 ? round($r['correct']/$r['total']*100) : 0; }, $successRate)) ?>, backgroundColor: [gold, red, green, '#8fb3d9', '#c07ad1'] }]
  }
});
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
