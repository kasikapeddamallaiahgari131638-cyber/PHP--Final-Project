<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Suspect Management';
$activeNav = 'suspects';
$flash = '';

$cases = $pdo->query('SELECT id, title FROM cases ORDER BY id')->fetchAll();
$caseId = (int)($_GET['case_id'] ?? ($cases[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isCulprit = !empty($_POST['is_culprit']) ? 1 : 0;

    if ($action === 'create') {
        $cid = (int)$_POST['case_id'];
        if ($isCulprit) { $pdo->prepare('UPDATE suspects SET is_culprit=0 WHERE case_id=:c')->execute(['c'=>$cid]); }
        $stmt = $pdo->prepare('INSERT INTO suspects (case_id, name, age, occupation, relationship_to_victim, motive, alibi, evidence_against, evidence_supporting, suspicion_level, is_culprit)
                                VALUES (:c,:n,:age,:occ,:rel,:mot,:alibi,:ea,:es,:sl,:ic)');
        $stmt->execute([
            'c'=>$cid,'n'=>trim($_POST['name']),'age'=>(int)$_POST['age'],'occ'=>trim($_POST['occupation']),
            'rel'=>trim($_POST['relationship_to_victim']),'mot'=>trim($_POST['motive']),'alibi'=>trim($_POST['alibi']),
            'ea'=>trim($_POST['evidence_against']),'es'=>trim($_POST['evidence_supporting']),'sl'=>$_POST['suspicion_level'],'ic'=>$isCulprit,
        ]);
        $flash = 'Suspect added.'; $caseId = $cid;
    } elseif ($action === 'update') {
        $cid = (int)$_POST['case_id_ref'];
        if ($isCulprit) { $pdo->prepare('UPDATE suspects SET is_culprit=0 WHERE case_id=:c')->execute(['c'=>$cid]); }
        $stmt = $pdo->prepare('UPDATE suspects SET name=:n, age=:age, occupation=:occ, relationship_to_victim=:rel, motive=:mot,
                                alibi=:alibi, evidence_against=:ea, evidence_supporting=:es, suspicion_level=:sl, is_culprit=:ic WHERE id=:id');
        $stmt->execute([
            'n'=>trim($_POST['name']),'age'=>(int)$_POST['age'],'occ'=>trim($_POST['occupation']),'rel'=>trim($_POST['relationship_to_victim']),
            'mot'=>trim($_POST['motive']),'alibi'=>trim($_POST['alibi']),'ea'=>trim($_POST['evidence_against']),'es'=>trim($_POST['evidence_supporting']),
            'sl'=>$_POST['suspicion_level'],'ic'=>$isCulprit,'id'=>$_POST['id'],
        ]);
        $flash = 'Suspect updated.'; $caseId = $cid;
    } elseif ($action === 'delete') {
        $caseId = (int)$_POST['case_id_ref'];
        $pdo->prepare('DELETE FROM suspects WHERE id=:id')->execute(['id'=>$_POST['id']]);
        $flash = 'Suspect deleted.';
    }
}

$suspects = $pdo->prepare('SELECT * FROM suspects WHERE case_id=:c ORDER BY id');
$suspects->execute(['c'=>$caseId]);
$suspects = $suspects->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h3 class="mb-0">Suspect Management</h3>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <select class="form-select" name="case_id" onchange="this.form.submit()">
        <?php foreach ($cases as $c): ?><option value="<?= $c['id'] ?>" <?= $caseId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <button class="btn btn-pd-solid text-nowrap" data-bs-toggle="modal" data-bs-target="#createS"><i class="fa-solid fa-plus"></i> Add Suspect</button>
  </div>
</div>
<?php if ($flash): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<div class="row g-3">
  <?php foreach ($suspects as $s): ?>
    <div class="col-md-6">
      <div class="suspect-card h-100">
        <div class="d-flex justify-content-between">
          <strong><?= htmlspecialchars($s['name']) ?></strong>
          <?php if ($s['is_culprit']): ?><span class="badge bg-danger">TRUE CULPRIT</span><?php endif; ?>
        </div>
        <div class="small text-muted"><?= htmlspecialchars($s['occupation']) ?>, <?= (int)$s['age'] ?> &middot; <?= htmlspecialchars($s['relationship_to_victim']) ?></div>
        <div class="small mt-2"><strong>Motive:</strong> <?= htmlspecialchars($s['motive']) ?></div>
        <div class="small"><strong>Alibi:</strong> <?= htmlspecialchars($s['alibi']) ?></div>
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-pd btn-sm" data-bs-toggle="modal" data-bs-target="#editS<?= $s['id'] ?>"><i class="fa-solid fa-pen"></i></button>
          <form method="POST" onsubmit="return confirm('Delete this suspect?');">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="case_id_ref" value="<?= $caseId ?>">
            <button class="btn btn-pd-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editS<?= $s['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-lg"><div class="modal-content pd-panel">
        <form method="POST">
          <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="case_id_ref" value="<?= $caseId ?>">
          <div class="modal-header"><h5>Edit Suspect</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-6"><label>Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($s['name']) ?>" required></div>
              <div class="col-md-3"><label>Age</label><input class="form-control" type="number" name="age" value="<?= (int)$s['age'] ?>"></div>
              <div class="col-md-3"><label>Suspicion</label>
                <select class="form-select" name="suspicion_level"><?php foreach(['Low','Medium','High'] as $lv): ?><option <?= $s['suspicion_level']===$lv?'selected':'' ?>><?= $lv ?></option><?php endforeach; ?></select>
              </div>
              <div class="col-md-6"><label>Occupation</label><input class="form-control" name="occupation" value="<?= htmlspecialchars($s['occupation']) ?>"></div>
              <div class="col-md-6"><label>Relationship to Victim</label><input class="form-control" name="relationship_to_victim" value="<?= htmlspecialchars($s['relationship_to_victim']) ?>"></div>
              <div class="col-12"><label>Motive</label><textarea class="form-control" name="motive" rows="2"><?= htmlspecialchars($s['motive']) ?></textarea></div>
              <div class="col-12"><label>Alibi</label><textarea class="form-control" name="alibi" rows="2"><?= htmlspecialchars($s['alibi']) ?></textarea></div>
              <div class="col-12"><label>Evidence Against</label><textarea class="form-control" name="evidence_against" rows="2"><?= htmlspecialchars($s['evidence_against']) ?></textarea></div>
              <div class="col-12"><label>Evidence Supporting Innocence</label><textarea class="form-control" name="evidence_supporting" rows="2"><?= htmlspecialchars($s['evidence_supporting']) ?></textarea></div>
              <div class="col-12 form-check ms-1 mt-2">
                <input class="form-check-input" type="checkbox" name="is_culprit" value="1" id="ic<?= $s['id'] ?>" <?= $s['is_culprit']?'checked':'' ?>>
                <label class="form-check-label" for="ic<?= $s['id'] ?>">This is the TRUE culprit for this case</label>
              </div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Save</button></div>
        </form>
      </div></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$suspects): ?><p class="text-muted">No suspects yet for this case.</p><?php endif; ?>
</div>

<div class="modal fade" id="createS" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content pd-panel">
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-header"><h5>Add Suspect</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label>Case</label>
          <select class="form-select" name="case_id"><?php foreach ($cases as $c): ?><option value="<?= $c['id'] ?>" <?= $caseId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="row g-2">
          <div class="col-md-6"><label>Name</label><input class="form-control" name="name" required></div>
          <div class="col-md-3"><label>Age</label><input class="form-control" type="number" name="age"></div>
          <div class="col-md-3"><label>Suspicion</label><select class="form-select" name="suspicion_level"><option>Low</option><option selected>Medium</option><option>High</option></select></div>
          <div class="col-md-6"><label>Occupation</label><input class="form-control" name="occupation"></div>
          <div class="col-md-6"><label>Relationship to Victim</label><input class="form-control" name="relationship_to_victim"></div>
          <div class="col-12"><label>Motive</label><textarea class="form-control" name="motive" rows="2"></textarea></div>
          <div class="col-12"><label>Alibi</label><textarea class="form-control" name="alibi" rows="2"></textarea></div>
          <div class="col-12"><label>Evidence Against</label><textarea class="form-control" name="evidence_against" rows="2"></textarea></div>
          <div class="col-12"><label>Evidence Supporting Innocence</label><textarea class="form-control" name="evidence_supporting" rows="2"></textarea></div>
          <div class="col-12 form-check ms-1 mt-2">
            <input class="form-check-input" type="checkbox" name="is_culprit" value="1" id="icNew">
            <label class="form-check-label" for="icNew">This is the TRUE culprit for this case</label>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Add Suspect</button></div>
    </form>
  </div></div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
