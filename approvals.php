<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Supervisor', 'Finance', 'CEO']);
$activeTab = 'approvals';

$stmt = $pdo->prepare("SELECT r.*, u.name AS requester_name, p.name AS program_name, p.code AS program_code
    FROM requisitions r JOIN users u ON u.id = r.requester_id JOIN programs p ON p.id = r.program_id
    WHERE r.status = 'Pending' AND r.current_level = ? ORDER BY r.created_at ASC");
$stmt->execute([$user['role']]);
$queue = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h2>Approvals — <?= h($user['role']) ?></h2><p>Requests awaiting your decision.</p></div></div>
<div class="panel" style="background:var(--cream);box-shadow:none;border:1px dashed var(--line);">
  <div style="font-size:12px;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">Approval hierarchy</div>
  <div class="flow-steps" style="margin:0;">
    <?php $levels = ['Requester','Supervisor','Finance','CEO']; foreach ($levels as $i => $lvl): ?>
      <span class="flow-step <?= $lvl === $user['role'] ? 'current' : '' ?>"><?= h($lvl) ?><?= $lvl === 'CEO' ? ' (above K' . number_format(CEO_THRESHOLD) . ')' : '' ?></span>
      <?php if ($i < count($levels) - 1): ?><span class="flow-arrow">→</span><?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
<div class="panel">
  <?php if (empty($queue)): ?>
    <div class="empty-state"><div class="big">✅</div>Nothing waiting on you right now.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Ref</th><th>Category</th><th>Requester</th><th>Program</th><th>Amount</th><th>Submitted</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($queue as $r): ?>
          <tr>
            <td class="mono"><?= h($r['ref_no']) ?></td>
            <td><span class="cat-tag"><?= h($r['category']) ?></span></td>
            <td><?= h($r['requester_name']) ?></td>
            <td><?= h($r['program_code']) ?></td>
            <td><?= fmt_k($r['estimated_cost']) ?></td>
            <td><?= fmt_date($r['created_at']) ?></td>
            <td><a class="btn-primary btn-sm" href="view_requisition.php?id=<?= $r['id'] ?>">Review</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
