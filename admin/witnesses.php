<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'Witness Management';
$activeNav = 'witnesses';
$flash = '';

$cases = $pdo->query('SELECT id, title FROM cases ORDER BY id')->fetchAll();
$caseId = (int)($_GET['case_id'] ?? ($cases[0]['id'] ?? 0));

function build_dialogue_json(array $questions, array $answers): string
{
    $pairs = [];
    foreach ($questions as $i => $q) {
        $q = trim($q); $a = trim($answers[$i] ?? '');
        if ($q !== '' && $a !== '') { $pairs[] = ['q' => $q, 'a' => $a]; }
    }
    return json_encode($pairs);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $dialogueJson = build_dialogue_json($_POST['q'] ?? [], $_POST['a'] ?? []);
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO witnesses (case_id, name, relationship, dialogue, important_clue, contradiction) VALUES (:c,:n,:r,:d,:ic,:ct)');
        $stmt->execute([
            'c'=>$_POST['case_id'], 'n'=>trim($_POST['name']), 'r'=>trim($_POST['relationship']), 'd'=>$dialogueJson,
            'ic'=>trim($_POST['important_clue']), 'ct'=>trim($_POST['contradiction']) ?: null,
        ]);
        $flash = 'Witness added.'; $caseId = (int)$_POST['case_id'];
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare('UPDATE witnesses SET name=:n, relationship=:r, dialogue=:d, important_clue=:ic, contradiction=:ct WHERE id=:id');
        $stmt->execute(['n'=>trim($_POST['name']),'r'=>trim($_POST['relationship']),'d'=>$dialogueJson,'ic'=>trim($_POST['important_clue']),'ct'=>trim($_POST['contradiction']) ?: null,'id'=>$_POST['id']]);
        $flash = 'Witness updated.'; $caseId = (int)$_POST['case_id_ref'];
    } elseif ($action === 'delete') {
        $caseId = (int)$_POST['case_id_ref'];
        $pdo->prepare('DELETE FROM witnesses WHERE id=:id')->execute(['id'=>$_POST['id']]);
        $flash = 'Witness deleted.';
    }
}

$witnesses = $pdo->prepare('SELECT * FROM witnesses WHERE case_id=:c ORDER BY id');
$witnesses->execute(['c'=>$caseId]);
$witnesses = $witnesses->fetchAll();

include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h3 class="mb-0">Witness Management</h3>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <select class="form-select" name="case_id" onchange="this.form.submit()">
        <?php foreach ($cases as $c): ?><option value="<?= $c['id'] ?>" <?= $caseId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?>
      </select>
    </form>
    <button class="btn btn-pd-solid text-nowrap" data-bs-toggle="modal" data-bs-target="#createW"><i class="fa-solid fa-plus"></i> Add Witness</button>
  </div>
</div>
<?php if ($flash): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<div class="row g-3">
  <?php foreach ($witnesses as $w):
    $dlg = json_decode($w['dialogue'], true) ?: []; ?>
    <div class="col-md-6">
      <div class="pd-panel p-4 h-100">
        <strong><?= htmlspecialchars($w['name']) ?></strong>
        <div class="small text-muted mb-2"><?= htmlspecialchars($w['relationship']) ?></div>
        <?php foreach ($dlg as $qa): ?>
          <div class="small mb-1"><strong style="color:var(--pd-accent)">Q:</strong> <?= htmlspecialchars($qa['q']) ?><br><strong>A:</strong> <span class="text-muted"><?= htmlspecialchars($qa['a']) ?></span></div>
        <?php endforeach; ?>
        <div class="small mt-2"><strong>Clue:</strong> <?= htmlspecialchars($w['important_clue']) ?></div>
        <?php if ($w['contradiction']): ?><div class="small text-danger mt-1"><strong>Contradiction:</strong> <?= htmlspecialchars($w['contradiction']) ?></div><?php endif; ?>
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-pd btn-sm" data-bs-toggle="modal" data-bs-target="#editW<?= $w['id'] ?>"><i class="fa-solid fa-pen"></i></button>
          <form method="POST" onsubmit="return confirm('Delete this witness?');">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $w['id'] ?>"><input type="hidden" name="case_id_ref" value="<?= $caseId ?>">
            <button class="btn btn-pd-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editW<?= $w['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-lg"><div class="modal-content pd-panel">
        <form method="POST">
          <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $w['id'] ?>"><input type="hidden" name="case_id_ref" value="<?= $caseId ?>">
          <div class="modal-header"><h5>Edit Witness</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="mb-2"><label>Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($w['name']) ?>" required></div>
            <div class="mb-2"><label>Relationship</label><input class="form-control" name="relationship" value="<?= htmlspecialchars($w['relationship']) ?>" required></div>
            <label class="d-block mt-2">Dialogue (Q&amp;A pairs)</label>
            <?php for ($i=0;$i<3;$i++): $qa = $dlg[$i] ?? ['q'=>'','a'=>'']; ?>
              <div class="row g-2 mb-1">
                <div class="col-6"><input class="form-control form-control-sm" name="q[]" value="<?= htmlspecialchars($qa['q']) ?>" placeholder="Question"></div>
                <div class="col-6"><input class="form-control form-control-sm" name="a[]" value="<?= htmlspecialchars($qa['a']) ?>" placeholder="Answer"></div>
              </div>
            <?php endfor; ?>
            <div class="mb-2 mt-2"><label>Important Clue</label><textarea class="form-control" name="important_clue" rows="2" required><?= htmlspecialchars($w['important_clue']) ?></textarea></div>
            <div class="mb-2"><label>Contradiction (optional)</label><textarea class="form-control" name="contradiction" rows="2"><?= htmlspecialchars($w['contradiction']) ?></textarea></div>
          </div>
          <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Save</button></div>
        </form>
      </div></div>
    </div>
  <?php endforeach; ?>
  <?php if (!$witnesses): ?><p class="text-muted">No witnesses yet for this case.</p><?php endif; ?>
</div>

<div class="modal fade" id="createW" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content pd-panel">
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-header"><h5>Add Witness</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label>Case</label>
          <select class="form-select" name="case_id"><?php foreach ($cases as $c): ?><option value="<?= $c['id'] ?>" <?= $caseId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['title']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="mb-2"><label>Name</label><input class="form-control" name="name" required></div>
        <div class="mb-2"><label>Relationship</label><input class="form-control" name="relationship" required></div>
        <label class="d-block mt-2">Dialogue (Q&amp;A pairs)</label>
        <?php for ($i=0;$i<3;$i++): ?>
          <div class="row g-2 mb-1">
            <div class="col-6"><input class="form-control form-control-sm" name="q[]" placeholder="Question"></div>
            <div class="col-6"><input class="form-control form-control-sm" name="a[]" placeholder="Answer"></div>
          </div>
        <?php endfor; ?>
        <div class="mb-2 mt-2"><label>Important Clue</label><textarea class="form-control" name="important_clue" rows="2" required></textarea></div>
        <div class="mb-2"><label>Contradiction (optional)</label><textarea class="form-control" name="contradiction" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Add Witness</button></div>
    </form>
  </div></div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
