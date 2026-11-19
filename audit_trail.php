<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Supervisor', 'Finance', 'CEO', 'Admin']);
$activeTab = 'audit';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT rh.*, r.ref_no, r.category, u.name AS requester_name
        FROM requisition_history rh
        JOIN requisitions r ON r.id = rh.requisition_id
        JOIN users u ON u.id = r.requester_id";
$params = [];
if ($q !== '') {
    $sql .= " WHERE r.ref_no LIKE ? OR rh.actor_name LIKE ? OR rh.action LIKE ? OR rh.comment LIKE ? OR r.category LIKE ?";
    $like = "%$q%";
    $params = [$like, $like, $like, $like, $like];
}
$sql .= " ORDER BY rh.created_at DESC LIMIT 300";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h2>Audit Trail</h2><p>Every submission, approval, rejection, and return across FPDA — who did it, when, and why.</p></div></div>

<form method="get" class="global-search">
  <input type="text" name="q" placeholder="Search audit trail by reference, name, action, or comment..." value="<?= h($q) ?>">
  <button class="btn-primary" type="submit">Search</button>
</form>

<div class="panel">
  <?php if (empty($entries)): ?>
    <div class="empty-state"><div class="big">🕓</div>No audit trail entries yet.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Timestamp</th><th>Ref</th><th>Requester</th><th>Action</th><th>Level</th><th>By</th><th>Comment</th></tr></thead>
      <tbody>
        <?php foreach ($entries as $e): ?>
          <tr>
            <td class="mono" style="white-space:nowrap;"><?= fmt_datetime($e['created_at']) ?></td>
            <td class="mono"><a href="view_requisition.php?id=<?= $e['requisition_id'] ?>" style="color:var(--link);"><?= h($e['ref_no']) ?></a></td>
            <td><?= h($e['requester_name']) ?></td>
            <td><strong><?= h($e['action']) ?></strong></td>
            <td><?= ($e['level'] && $e['level'] !== 'Requester') ? h($e['level']) : '—' ?></td>
            <td><?= h($e['actor_name']) ?></td>
            <td style="max-width:260px;color:var(--ink-soft);"><?= h($e['comment'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
