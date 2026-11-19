<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login($pdo);
$activeTab = 'budget';
$programs = $pdo->query('SELECT * FROM programs ORDER BY name')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h2>Budget &amp; Programs</h2><p>Allocated budget vs. spending per program code.</p></div></div>
<div class="panel">
  <?php foreach ($programs as $p):
      $pct = $p['allocated'] > 0 ? min(100, round(($p['spent'] / $p['allocated']) * 100)) : 0;
      $over = $p['spent'] > $p['allocated']; ?>
    <div style="padding:12px 0;border-bottom:1px solid var(--line);">
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
        <div><strong><?= h($p['name']) ?></strong> <span class="mono" style="color:var(--ink-soft);">(<?= h($p['code']) ?>)</span></div>
        <div style="font-size:13px;"><?= fmt_k($p['spent']) ?> / <?= fmt_k($p['allocated']) ?><?= $over ? ' <span style="color:var(--red);font-weight:600;"> · over budget</span>' : '' ?></div>
      </div>
      <div class="progress-bar"><div class="fill <?= $over ? 'over' : '' ?>" style="width:<?= $pct ?>%;"></div></div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($user['role'] === 'Admin'): ?>
<div class="panel">
  <h3>Add / adjust program</h3>
  <form method="post" action="add_program.php" class="form-grid">
        <?= csrf_field() ?>
    <div class="field"><label>Program name</label><input name="name" required></div>
    <div class="field"><label>Code</label><input name="code" required></div>
    <div class="field"><label>Allocated budget (PGK)</label><input type="number" step="0.01" min="0" name="allocated" required></div>
    <div class="field full"><button class="btn-primary" type="submit">Add Program</button></div>
  </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
