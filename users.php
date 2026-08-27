<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_admin();
$pageTitle = 'User Management';
$activeNav = 'users';
$flash = '';

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, status) VALUES (:u,:e,:p,:f,:s)');
    $stmt->execute([
        'u' => trim($_POST['username']), 'e' => trim($_POST['email']),
        'p' => password_hash($_POST['password'], PASSWORD_BCRYPT),
        'f' => trim($_POST['full_name']), 's' => $_POST['status'] ?? 'active',
    ]);
    $newId = $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO leaderboard (user_id) VALUES (:u)')->execute(['u' => $newId]);
    $flash = 'User created successfully.';
}
// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    if (!empty($_POST['password'])) {
        $stmt = $pdo->prepare('UPDATE users SET username=:u, email=:e, full_name=:f, status=:s, password=:p WHERE id=:id');
        $stmt->execute(['u'=>trim($_POST['username']),'e'=>trim($_POST['email']),'f'=>trim($_POST['full_name']),'s'=>$_POST['status'],'p'=>password_hash($_POST['password'],PASSWORD_BCRYPT),'id'=>$_POST['id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET username=:u, email=:e, full_name=:f, status=:s WHERE id=:id');
        $stmt->execute(['u'=>trim($_POST['username']),'e'=>trim($_POST['email']),'f'=>trim($_POST['full_name']),'s'=>$_POST['status'],'id'=>$_POST['id']]);
    }
    $flash = 'User updated successfully.';
}
// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $_POST['id']]);
    $flash = 'User deleted.';
}
// TOGGLE ACTIVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $pdo->prepare('UPDATE users SET status = IF(status="active","inactive","active") WHERE id=:id')->execute(['id' => $_POST['id']]);
    $flash = 'User status updated.';
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
include __DIR__ . '/_layout_top.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h3 class="mb-0">User Management</h3>
  <button class="btn btn-pd-solid" data-bs-toggle="modal" data-bs-target="#createUserModal"><i class="fa-solid fa-plus"></i> Add User</button>
</div>
<?php if ($flash): ?><div class="alert alert-success" data-autohide><?= htmlspecialchars($flash) ?></div><?php endif; ?>

<div class="pd-panel p-3 p-md-4">
  <div class="pd-scroll-x">
    <table class="table table-borderless pd-table align-middle">
      <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Score</th><th>Cases</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>#<?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td>@<?= htmlspecialchars($u['username']) ?></td>
            <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
            <td><?= (int)$u['total_score'] ?></td>
            <td><?= (int)$u['cases_completed'] ?></td>
            <td>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="badge border-0 <?= $u['status']==='active'?'bg-success':'bg-secondary' ?>" type="submit"><?= $u['status'] ?></button>
              </form>
            </td>
            <td class="text-nowrap">
              <button class="btn btn-pd btn-sm" data-bs-toggle="modal" data-bs-target="#editUser<?= $u['id'] ?>"><i class="fa-solid fa-pen"></i></button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Delete this user permanently?');">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="btn btn-pd-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>

          <!-- Edit modal -->
          <div class="modal fade" id="editUser<?= $u['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content pd-panel">
                <form method="POST">
                  <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <div class="modal-header"><h5 class="modal-title">Edit User #<?= $u['id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body">
                    <div class="mb-2"><label>Full Name</label><input class="form-control" name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required></div>
                    <div class="mb-2"><label>Username</label><input class="form-control" name="username" value="<?= htmlspecialchars($u['username']) ?>" required></div>
                    <div class="mb-2"><label>Email</label><input class="form-control" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></div>
                    <div class="mb-2"><label>New Password (leave blank to keep current)</label><input class="form-control" type="password" name="password"></div>
                    <div class="mb-2"><label>Status</label>
                      <select class="form-select" name="status">
                        <option value="active" <?= $u['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $u['status']==='inactive'?'selected':'' ?>>Inactive</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Save Changes</button></div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content pd-panel">
      <form method="POST">
        <input type="hidden" name="action" value="create">
        <div class="modal-header"><h5 class="modal-title">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label>Full Name</label><input class="form-control" name="full_name" required></div>
          <div class="mb-2"><label>Username</label><input class="form-control" name="username" required></div>
          <div class="mb-2"><label>Email</label><input class="form-control" type="email" name="email" required></div>
          <div class="mb-2"><label>Password</label><input class="form-control" type="password" name="password" required minlength="6"></div>
          <div class="mb-2"><label>Status</label>
            <select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-pd-solid" type="submit">Create</button></div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
