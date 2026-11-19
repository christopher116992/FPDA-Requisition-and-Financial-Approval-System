<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);
$activeTab = 'admin';
$adminActive = 'users';
$users = $pdo->query('SELECT * FROM users ORDER BY FIELD(role,"Admin","CEO","Finance","Supervisor","Staff"), name')->fetchAll();
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/admin_nav.php';
?>
<div class="page-head"><div><h2>System Administration</h2><p>Manage staff accounts, system backups, and other system functions.</p></div></div>

<div class="panel">
  <h3>Users (<?= count($users) ?>)</h3>
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Position</th><th>Department</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= h($u['name']) ?></td>
          <td class="mono"><?= h($u['email']) ?></td>
          <td>
            <?php if ((int)$u['id'] === (int)$user['id']): ?>
              <?= h($u['role']) ?> <span class="hint">(you)</span>
            <?php else: ?>
              <form method="post" action="update_user_role.php" style="margin:0;">
        <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <select name="role" onchange="this.form.submit()" style="padding:5px 7px;font-size:12.5px;width:auto;">
                  <?php foreach (['Staff','Supervisor','Finance','CEO','Admin'] as $r): ?><option <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?>
                </select>
              </form>
            <?php endif; ?>
          </td>
          <td><?= h($u['position_title'] ?: '—') ?></td>
          <td><?= h($u['department']) ?></td>
          <td><?= $u['is_active'] ? '<span class="badge approved">Active</span>' : '<span class="badge rejected">Inactive</span>' ?></td>
          <td>
            <?php if ((int)$u['id'] !== (int)$user['id']): ?>
              <form method="post" action="toggle_user_active.php" style="margin:0;">
        <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button class="btn-ghost btn-sm" type="submit"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h3>Add user</h3>
  <p class="hint" style="margin-bottom:12px;">New workers log in with their FPDA email (username@fpda.com.pg) or the username below.</p>
  <form method="post" action="add_user.php" class="form-grid">
        <?= csrf_field() ?>
    <div class="field"><label>Full name</label><input name="name" required></div>
    <div class="field"><label>Username</label><input name="username" required placeholder="e.g. jkaupa"></div>
    <div class="field"><label>Password</label><input name="password" required></div>
    <div class="field"><label>Role</label>
      <select name="role">
        <?php foreach (['Staff','Supervisor','Finance','CEO','Admin'] as $r): ?><option><?= $r ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field"><label>Position at FPDA</label><input name="position" placeholder="e.g. Extension Officer"></div>
    <div class="field"><label>Department</label><input name="department" required></div>
    <div class="field full"><button class="btn-primary" type="submit">Add User</button></div>
  </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
