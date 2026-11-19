<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);
$activeTab = 'admin';
$adminActive = 'backups';
$backups = $pdo->query('SELECT * FROM backups ORDER BY created_at DESC LIMIT 50')->fetchAll();
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/admin_nav.php';
?>
<div class="page-head"><div><h2>Database Backups</h2><p>Perform a manual backup or review backup history. Use with a healthy dose of caution around restores.</p></div></div>

<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div>
      <h3 style="margin-bottom:4px;">Create a backup</h3>
      <p style="color:var(--ink-soft);font-size:13px;margin:0;">Snapshots every user, requisition, program, and log; encrypted at rest (AES-256) with a SHA-256 integrity checksum, stored in <code>/backups</code> on this server.</p>
    </div>
    <form method="post" action="create_backup.php">
        <?= csrf_field() ?><button class="btn-primary btn-sm" type="submit">Create Backup Now</button></form>
  </div>
</div>

<div class="panel">
  <h3>Backup history (<?= count($backups) ?>)</h3>
  <?php if (empty($backups)): ?>
    <div class="empty-row">No backups yet — create one above.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Created</th><th>By</th><th>Size</th><th>Security</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($backups as $b): ?>
          <tr>
            <td class="mono"><?= fmt_datetime($b['created_at']) ?></td>
            <td><?= h($b['created_by'] ?: '—') ?></td>
            <td><?= number_format($b['size_bytes'] / 1024, 1) ?> KB</td>
            <td>
              <?php if ($b['encrypted']): ?><span class="badge approved">🔒 Encrypted</span><?php endif; ?>
              <?php if ($b['checksum']): ?><span class="cat-tag" title="SHA-256: <?= h($b['checksum']) ?>">✓ Checksummed</span><?php endif; ?>
            </td>
            <td style="display:flex;gap:6px;">
              <a class="btn-ghost btn-sm" href="download_backup.php?id=<?= $b['id'] ?>">Download</a>
              <form method="post" action="restore_backup.php" style="margin:0;" onsubmit="return confirm('Restore this backup? This will overwrite ALL current data for every user of the system.');">
        <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button class="btn-danger btn-sm" type="submit">Restore</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
