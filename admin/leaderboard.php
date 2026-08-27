<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Leaderboard Management';
$activeNav = 'leaderboard';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    $pdo->prepare('UPDATE leaderboard SET total_score=0, cases_solved=0, accuracy=0 WHERE user_id=:id')->execute(['id'=>$_POST['id']]);
    $pdo->prepare('UPDATE users SET total_score=0, cases_completed=0 WHERE id=:id')->execute(['id'=>$_POST['id']]);
    $pdo->prepare('DELETE FROM investigation WHERE user_id=:id')->execute(['id'=>$_POST['id']]);
    $pdo->prepare('UPDATE player_progress SET status="not_started", progress_percentage=0 WHERE user_id=:id')->execute(['id'=>$_POST['id']]);
    $flash = 'Player progress and score reset.';
}

$board = $pdo->query('SELECT l.*, u.full_name, u.username FROM leaderboard l JOIN users u ON u.id=l.user_id ORDER BY l.total_score DESC')->fetchAll();
include __DIR__ . '/_layout_top.php';
?>
<h3 class="mb-4">Leaderboard Management</h3>
<?php if ($flash): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<div class="pd-panel p-3 p-md-4">
  <div class="pd-scroll-x">
    <table class="table table-borderless pd-table align-middle">
      <thead><tr><th>Rank</th><th>Detective</th><th>Score</th><th>Cases Solved</th><th>Accuracy</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($board as $i => $b): ?>
          <tr>
            <td>#<?= $i+1 ?></td>
            <td><?= htmlspecialchars($b['full_name']) ?> <span class="text-muted small">@<?= htmlspecialchars($b['username']) ?></span></td>
            <td style="color:var(--pd-accent)" class="fw-bold"><?= (int)$b['total_score'] ?></td>
            <td><?= (int)$b['cases_solved'] ?></td>
            <td><?= number_format((float)$b['accuracy'],1) ?>%</td>
            <td>
              <form method="POST" onsubmit="return confirm('Reset all progress and scores for this player?');">
                <input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= $b['user_id'] ?>">
                <button class="btn btn-pd-danger btn-sm" type="submit"><i class="fa-solid fa-rotate-left"></i> Reset</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
