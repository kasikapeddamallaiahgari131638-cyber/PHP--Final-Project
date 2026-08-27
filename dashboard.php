<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';

$stats = [
  'users'     => $pdo->query('SELECT COUNT(*) c FROM users')->fetch()['c'],
  'cases'     => $pdo->query('SELECT COUNT(*) c FROM cases')->fetch()['c'],
  'evidence'  => $pdo->query('SELECT COUNT(*) c FROM evidence')->fetch()['c'],
  'witnesses' => $pdo->query('SELECT COUNT(*) c FROM witnesses')->fetch()['c'],
  'suspects'  => $pdo->query('SELECT COUNT(*) c FROM suspects')->fetch()['c'],
  'completed' => $pdo->query('SELECT COUNT(*) c FROM investigation WHERE status="completed"')->fetch()['c'],
  'in_progress' => $pdo->query('SELECT COUNT(*) c FROM investigation WHERE status="in_progress"')->fetch()['c'],
  'avg_score' => $pdo->query('SELECT COALESCE(ROUND(AVG(score),1),0) c FROM investigation WHERE status="completed"')->fetch()['c'],
];

$recentActivity = $pdo->query('
  SELECT al.*, u.username FROM activity_logs al
  LEFT JOIN users u ON u.id = al.user_id
  ORDER BY al.created_at DESC LIMIT 10')->fetchAll();

$recentUsers = $pdo->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Dashboard Overview</h3>
  <span class="text-muted small"><i class="fa-solid fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['users'] ?></div><div class="small text-muted">Registered Users</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['cases'] ?></div><div class="small text-muted">Total Cases</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['completed'] ?></div><div class="small text-muted">Cases Completed</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['in_progress'] ?></div><div class="small text-muted">In Progress</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['evidence'] ?></div><div class="small text-muted">Evidence Items</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['witnesses'] ?></div><div class="small text-muted">Witnesses</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= (int)$stats['suspects'] ?></div><div class="small text-muted">Suspects</div></div></div>
  <div class="col-6 col-md-3"><div class="pd-panel p-3 text-center"><div class="pd-stat" style="font-size:1.7rem"><?= $stats['avg_score'] ?></div><div class="small text-muted">Avg. Score</div></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="pd-panel p-4">
      <div class="pd-eyebrow mb-3">Recent Activity</div>
      <div class="pd-scroll-x">
        <table class="table table-borderless pd-table small mb-0">
          <thead><tr><th>User</th><th>Action</th><th>Details</th><th>When</th></tr></thead>
          <tbody>
            <?php foreach ($recentActivity as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['username'] ?? 'system') ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars(str_replace('_',' ',$a['action'])) ?></span></td>
                <td class="text-muted"><?= htmlspecialchars($a['details']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($a['created_at']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recentActivity): ?><tr><td colspan="4" class="text-muted">No activity yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="pd-panel p-4">
      <div class="pd-eyebrow mb-3">Newest Detectives</div>
      <?php foreach ($recentUsers as $u): ?>
        <div class="d-flex justify-content-between border-bottom py-2" style="border-color:var(--pd-border) !important;">
          <div>
            <div class="small fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
            <div class="small text-muted">@<?= htmlspecialchars($u['username']) ?></div>
          </div>
          <span class="badge <?= $u['status']==='active'?'bg-success':'bg-secondary' ?>"><?= $u['status'] ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!$recentUsers): ?><p class="text-muted small mb-0">No users yet.</p><?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
