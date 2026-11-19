<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);
$activeTab = 'admin';
$adminActive = 'logs';

$loginLogs = $pdo->query("SELECT * FROM activity_log WHERE action IN ('login','login_failed','login_blocked','logout') ORDER BY created_at DESC LIMIT 100")->fetchAll();
$dataLogs = $pdo->query("SELECT * FROM activity_log WHERE action NOT IN ('login','login_failed','login_blocked','logout') ORDER BY created_at DESC LIMIT 150")->fetchAll();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/admin_nav.php';
?>
<div class="page-head"><div><h2>System Logs</h2><p>View login history and data access / change logs across the system.</p></div></div>

<div class="panel">
  <h3>Login history (<?= count($loginLogs) ?>)</h3>
  <?php if (empty($loginLogs)): ?>
    <div class="empty-row">No login activity recorded yet.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Time</th><th>User</th><th>Event</th><th>IP</th></tr></thead>
      <tbody>
        <?php foreach ($loginLogs as $l): ?>
          <tr>
            <td class="mono"><?= fmt_datetime($l['created_at']) ?></td>
            <td><?= h($l['actor_name'] ?: '—') ?></td>
            <td>
              <?php if ($l['action'] === 'login'): ?><span class="badge approved">Login success</span>
              <?php elseif ($l['action'] === 'logout'): ?><span class="badge returned">Logout</span>
              <?php elseif ($l['action'] === 'login_blocked'): ?><span class="badge returned">Blocked (inactive/locked)</span>
              <?php else: ?><span class="badge rejected">Login failed</span><?php endif; ?>
            </td>
            <td class="mono" style="color:var(--ink-soft);"><?= h($l['ip_address'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="panel">
  <h3>Data access &amp; change log (<?= count($dataLogs) ?>)</h3>
  <p class="hint" style="margin-bottom:10px;">Every submission, approval, rejection, return, and admin action — recorded for accountability.</p>
  <?php if (empty($dataLogs)): ?>
    <div class="empty-row">No activity recorded yet.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Detail</th></tr></thead>
      <tbody>
        <?php foreach ($dataLogs as $l): ?>
          <tr>
            <td class="mono"><?= fmt_datetime($l['created_at']) ?></td>
            <td><?= h($l['actor_name'] ?: '—') ?></td>
            <td><span class="cat-tag"><?= h(ucfirst($l['action'])) ?></span></td>
            <td style="color:var(--ink-soft);"><?= h($l['description']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
