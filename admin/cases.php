<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Case Management';
$activeNav = 'cases';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO cases (title, description, crime_type, difficulty, victim_name, crime_scene_desc, solution_explanation, estimated_time, status)
                                VALUES (:t,:d,:ct,:df,:v,:sd,:se,:et,:s)');
        $stmt->execute([
            't'=>trim($_POST['title']), 'd'=>trim($_POST['description']), 'ct'=>trim($_POST['crime_type']),
            'df'=>$_POST['difficulty'], 'v'=>trim($_POST['victim_name']), 'sd'=>trim($_POST['crime_scene_desc']),
            'se'=>trim($_POST['solution_explanation']), 'et'=>(int)$_POST['estimated_time'], 's'=>$_POST['status'],
        ]);
        $flash = 'Case created. Now add evidence, witnesses, and suspects to it.';
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE cases SET title=:t, description=:d, crime_type=:ct, difficulty=:df, victim_name=:v,
                                crime_scene_desc=:sd, solution_explanation=:se, estimated_time=:et, status=:s WHERE id=:id');
        $stmt->execute([
            't'=>trim($_POST['title']), 'd'=>trim($_POST['description']), 'ct'=>trim($_POST['crime_type']),
            'df'=>$_POST['difficulty'], 'v'=>trim($_POST['victim_name']), 'sd'=>trim($_POST['crime_scene_desc']),
            'se'=>trim($_POST['solution_explanation']), 'et'=>(int)$_POST['estimated_time'], 's'=>$_POST['status'],
            'id'=>$_POST['id'],
        ]);
        $flash = 'Case updated.';
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM cases WHERE id=:id')->execute(['id'=>$_POST['id']]);
        $flash = 'Case deleted.';
    } elseif ($action === 'toggle_status') {
        $pdo->prepare('UPDATE cases SET status = IF(status="published","archived","published") WHERE id=:id')->execute(['id'=>$_POST['id']]);
        $flash = 'Case status updated.';
    }
}

$cases = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM evidence WHERE case_id=c.id) ev,
                       (SELECT COUNT(*) FROM witnesses WHERE case_id=c.id) wt,
                       (SELECT COUNT(*) FROM suspects WHERE case_id=c.id) sp
                       FROM cases c ORDER BY c.id')->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">Case Management</h3>
  <button class="btn btn-pd-solid" data-bs-toggle="modal" data-bs-target="#createCase"><i class="fa-solid fa-plus"></i> New Case</button>
</div>
<?php if ($flash): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<div class="row g-3">
  <?php foreach ($cases as $c): ?>
    <div class="col-md-6">
      <div class="pd-panel p-4 h-100">
        <div class="d-flex justify-content-between">
          <span class="pd-badge diff-<?= htmlspecialchars($c['difficulty']) ?>"><?= htmlspecialchars($c['difficulty']) ?></span>
          <form method="POST"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="badge border-0 <?= $c['status']==='published'?'bg-success':'bg-secondary' ?>" type="submit"><?= $c['status'] ?></button>
          </form>
        </div>
        <h5 class="mt-2">#<?= $c['id'] ?> &middot; <?= htmlspecialchars($c['title']) ?></h5>
        <p class="small text-muted"><?= htmlspecialchars(strlen($c['description']) > 110 ? substr($c['description'],0,110) . '...' : $c['description']) ?></p>
        <div class="small text-muted mb-3"><?= $c['ev'] ?> evidence &middot; <?= $c['wt'] ?> witnesses &middot; <?= $c['sp'] ?> suspects</div>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-pd btn-sm" data-bs-toggle="modal" data-bs-target="#editCase<?= $c['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</button>
          <a href="evidence.php?case_id=<?= $c['id'] ?>" class="btn btn-pd btn-sm">Evidence</a>
          <a href="witnesses.php?case_id=<?= $c['id'] ?>" class="btn btn-pd btn-sm">Witnesses</a>
          <a href="suspects.php?case_id=<?= $c['id'] ?>" class="btn btn-pd btn-sm">Suspects</a>
          <form method="POST" onsubmit="return confirm('Delete this case and all its related data?');">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="btn btn-pd-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editCase<?= $c['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content pd-panel">
          <form method="POST">
            <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <div class="modal-header"><h5>Edit Case #<?= $c['id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <div class="row g-2">
                <div class="col-md-8"><label>Title</label><input class="form-control" name="title" value="<?= htmlspecialchars($c['title']) ?>" required></div>
                <div class="col-md-4"><label>Difficulty</label>
                  <select class="form-select" name="difficulty">
                    <?php foreach (['Easy','Medium','Hard'] as $d): ?><option <?= $c['difficulty']===$d?'selected':'' ?>><?= $d ?></option><?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6"><label>Crime Type</label><input class="form-control" name="crime_type" value="<?= htmlspecialchars($c['crime_type']) ?>" required></div>
                <div class="col-md-6"><label>Victim Name</label><input class="form-control" name="victim_name" value="<?= htmlspecialchars($c['victim_name']) ?>" required></div>
                <div class="col-12"><label>Description</label><textarea class="form-control" name="description" rows="2" required><?= htmlspecialchars($c['description']) ?></textarea></div>
                <div class="col-12"><label>Crime Scene Description</label><textarea class="form-control" name="crime_scene_desc" rows="2" required><?= htmlspecialchars($c['crime_scene_desc']) ?></textarea></div>
                <div class="col-12"><label>Solution Explanation (kept secret from players)</label><textarea class="form-control" name="solution_explanation" rows="2" required><?= htmlspecialchars($c['solution_explanation']) ?></textarea></div>
                <div class="col-md-6"><label>Estimated Time (minutes)</label><input class="form-control" type="number" name="estimated_time" value="<?= (int)$c['estimated_time'] ?>" required></div>
                <div class="col-md-6"><label>Status</label>
                  <select class="form-select" name="status">
                    <?php foreach (['published','draft','archived'] as $s): ?><option <?= $c['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Save</button></div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="modal fade" id="createCase" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content pd-panel">
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="modal-header"><h5>New Case</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-8"><label>Title</label><input class="form-control" name="title" required></div>
            <div class="col-md-4"><label>Difficulty</label><select class="form-select" name="difficulty"><option>Easy</option><option>Medium</option><option>Hard</option></select></div>
            <div class="col-md-6"><label>Crime Type</label><input class="form-control" name="crime_type" required placeholder="Murder, Theft, Fraud..."></div>
            <div class="col-md-6"><label>Victim Name</label><input class="form-control" name="victim_name" required></div>
            <div class="col-12"><label>Description</label><textarea class="form-control" name="description" rows="2" required></textarea></div>
            <div class="col-12"><label>Crime Scene Description</label><textarea class="form-control" name="crime_scene_desc" rows="2" required></textarea></div>
            <div class="col-12"><label>Solution Explanation</label><textarea class="form-control" name="solution_explanation" rows="2" required></textarea></div>
            <div class="col-md-6"><label>Estimated Time (minutes)</label><input class="form-control" type="number" name="estimated_time" value="20" required></div>
            <div class="col-md-6"><label>Status</label><select class="form-select" name="status"><option value="draft">Draft</option><option value="published">Published</option></select></div>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Create Case</button></div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
