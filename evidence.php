<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Evidence Management';
$activeNav = 'evidence';
$flash = '';

$cases = $pdo->query('SELECT id, title FROM cases ORDER BY id')->fetchAll();
$caseId = (int)($_GET['case_id'] ?? ($cases[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO evidence (case_id, name, type, description, location_found, relevance, hotspot_x, hotspot_y)
                                VALUES (:c,:n,:t,:d,:l,:r,:hx,:hy)');
        $stmt->execute([
            'c'=>$_POST['case_id'], 'n'=>trim($_POST['name']), 't'=>trim($_POST['type']), 'd'=>trim($_POST['description']),
            'l'=>trim($_POST['location_found']), 'r'=>$_POST['relevance'], 'hx'=>(int)$_POST['hotspot_x'], 'hy'=>(int)$_POST['hotspot_y'],
        ]);
        $newEv = $pdo->lastInsertId();
        if (!empty(trim($_POST['clue_text'] ?? ''))) {
            $pdo->prepare('INSERT INTO clues (evidence_id, clue_text) VALUES (:e,:c)')->execute(['e'=>$newEv,'c'=>trim($_POST['clue_text'])]);
        }
        $flash = 'Evidence added.'; $caseId = (int)$_POST['case_id'];
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE evidence SET name=:n, type=:t, description=:d, location_found=:l, relevance=:r, hotspot_x=:hx, hotspot_y=:hy WHERE id=:id');
        $stmt->execute(['n'=>trim($_POST['name']),'t'=>trim($_POST['type']),'d'=>trim($_POST['description']),'l'=>trim($_POST['location_found']),'r'=>$_POST['relevance'],'hx'=>(int)$_POST['hotspot_x'],'hy'=>(int)$_POST['hotspot_y'],'id'=>$_POST['id']]);
        $flash = 'Evidence updated.'; $caseId = (int)$_POST['case_id_ref'];
    } elseif ($action === 'delete') {
        $caseId = (int)$_POST['case_id_ref'];
        $pdo->prepare('DELETE FROM evidence WHERE id=:id')->execute(['id'=>$_POST['id']]);
        $flash = 'Evidence deleted.';
    }
}

$evidence = $pdo->prepare('SELECT * FROM evidence WHERE case_id=:c ORDER BY id');
$evidence->execute(['c'=>$caseId]);
$evidence = $evidence->fetchAll();
$clueStmt = $pdo->prepare('SELECT * FROM clues WHERE evidence_id=:e');

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h3 class="mb-0">Evidence Management</h3>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <select class="form-select" name="case_id" onchange="this.form.submit()">
        <?php foreach ($cases as $c): ?><option value="<?= $c['id'] ?>" <?= $caseId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <button class="btn btn-pd-solid text-nowrap" data-bs-toggle="modal" data-bs-target="#createEv"><i class="fa-solid fa-plus"></i> Add Evidence</button>
  </div>
</div>
<?php if ($flash): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<div class="row g-3">
  <?php foreach ($evidence as $ev):
    $clueStmt->execute(['e'=>$ev['id']]); $clues = $clueStmt->fetchAll(); ?>
    <div class="col-md-6">
      <div class="evidence-card h-100">
        <div class="d-flex justify-content-between">
          <strong><?= htmlspecialchars($ev['name']) ?></strong>
          <span class="rel-<?= htmlspecialchars($ev['relevance']) ?> small"><?= htmlspecialchars($ev['relevance']) ?></span>
        </div>
        <div class="small text-muted"><?= htmlspecialchars($ev['type']) ?> &middot; <?= htmlspecialchars($ev['location_found']) ?></div>
        <p class="small mt-2"><?= htmlspecialchars($ev['description']) ?></p>
        <?php foreach ($clues as $cl): ?><div class="small text-muted"><i class="fa-solid fa-lightbulb"></i> <?= htmlspecialchars($cl['clue_text']) ?></div><?php endforeach; ?>
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-pd btn-sm" data-bs-toggle="modal" data-bs-target="#editEv<?= $ev['id'] ?>"><i class="fa-solid fa-pen"></i></button>
          <form method="POST" onsubmit="return confirm('Delete this evidence?');">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $ev['id'] ?>"><input type="hidden" name="case_id_ref" value="<?= $caseId ?>">
            <button class="btn btn-pd-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editEv<?= $ev['id'] ?>" tabindex="-1">
      <div class="modal-dialog"><div class="modal-content pd-panel">
        <form method="POST">
          <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $ev['id'] ?>"><input type="hidden" name="case_id_ref" value="<?= $caseId ?>">
          <div class="modal-header"><h5>Edit Evidence</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="mb-2"><label>Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($ev['name']) ?>" required></div>
            <div class="mb-2"><label>Type</label><input class="form-control" name="type" value="<?= htmlspecialchars($ev['type']) ?>" required></div>
            <div class="mb-2"><label>Description</label><textarea class="form-control" name="description" rows="2" required><?= htmlspecialchars($ev['description']) ?></textarea></div>
            <div class="mb-2"><label>Location Found</label><input class="form-control" name="location_found" value="<?= htmlspecialchars($ev['location_found']) ?>" required></div>
            <div class="mb-2"><label>Relevance</label>
              <select class="form-select" name="relevance"><?php foreach(['Low','Medium','High','Critical'] as $r): ?><option <?= $ev['relevance']===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?></select>
            </div>
            <div class="row g-2">
              <div class="col-6"><label>Hotspot X (%)</label><input class="form-control" type="number" name="hotspot_x" value="<?= (int)$ev['hotspot_x'] ?>" min="0" max="100"></div>
              <div class="col-6"><label>Hotspot Y (%)</label><input class="form-control" type="number" name="hotspot_y" value="<?= (int)$ev['hotspot_y'] ?>" min="0" max="100"></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Save</button></div>
        </form>
      </div></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$evidence): ?><p class="text-muted">No evidence yet for this case.</p><?php endif; ?>
</div>

<div class="modal fade" id="createEv" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content pd-panel">
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-header"><h5>Add Evidence</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label>Case</label>
          <select class="form-select" name="case_id"><?php foreach ($cases as $c): ?><option value="<?= $c['id'] ?>" <?= $caseId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="mb-2"><label>Name</label><input class="form-control" name="name" required></div>
        <div class="mb-2"><label>Type</label><input class="form-control" name="type" required placeholder="Physical, Document..."></div>
        <div class="mb-2"><label>Description</label><textarea class="form-control" name="description" rows="2" required></textarea></div>
        <div class="mb-2"><label>Location Found</label><input class="form-control" name="location_found" required></div>
        <div class="mb-2"><label>Relevance</label><select class="form-select" name="relevance"><option>Low</option><option>Medium</option><option selected>High</option><option>Critical</option></select></div>
        <div class="row g-2">
          <div class="col-6"><label>Hotspot X (%)</label><input class="form-control" type="number" name="hotspot_x" value="50" min="0" max="100"></div>
          <div class="col-6"><label>Hotspot Y (%)</label><input class="form-control" type="number" name="hotspot_y" value="50" min="0" max="100"></div>
        </div>
        <div class="mb-2 mt-2"><label>Analysis Clue (optional)</label><textarea class="form-control" name="clue_text" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Add Evidence</button></div>
    </form>
  </div></div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
