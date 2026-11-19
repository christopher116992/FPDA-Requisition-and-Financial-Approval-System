<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);
$activeTab = 'admin';
$adminActive = 'danger';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/admin_nav.php';
?>
<div class="page-head"><div><h2>Danger Zone</h2><p>These actions affect the entire system. Use with caution.</p></div></div>

<div class="panel" style="border:1px solid var(--red-light);background:var(--red-light);">
  <h3 style="color:var(--red);">⚠️ Danger Zone</h3>
  <p style="font-size:13px;color:var(--ink-soft);margin-bottom:18px;">These actions affect <strong>all users</strong> of this system immediately. A backup is taken automatically before either action, but proceed carefully.</p>

  <div style="background:#fff;border-radius:8px;padding:14px 16px;margin-bottom:14px;">
    <strong>Clear cache &amp; logs</strong>
    <p style="font-size:12.5px;color:var(--ink-soft);margin:4px 0 10px;">Removes all notifications, login history, and activity logs. Users, requisitions, programs, and the audit trail are kept.</p>
    <form method="post" action="clear_cache.php" onsubmit="return confirm('Clear all notifications and system logs? This cannot be undone.');">
        <?= csrf_field() ?>
      <button class="btn-danger btn-sm" type="submit">Clear Notifications &amp; Logs</button>
    </form>
  </div>

  <div style="background:#fff;border-radius:8px;padding:14px 16px;">
    <strong>Reset entire system</strong>
    <p style="font-size:12.5px;color:var(--ink-soft);margin:4px 0 10px;">Wipes ALL data — every user (except a fresh Admin account), requisition, program, and log — back to a blank install.</p>
    <form method="post" action="reset_system.php" onsubmit="return confirm('This will wipe ALL data for every user of the system. Are you absolutely sure?');">
        <?= csrf_field() ?>
      <div class="field" style="max-width:320px;"><label>Type RESET to confirm</label><input name="confirm" placeholder="RESET" required></div>
      <button class="btn-danger btn-sm" type="submit">Reset Entire System</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
