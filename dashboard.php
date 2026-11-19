<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login($pdo);
$activeTab = 'dashboard';

$isStaff = $user['role'] === 'Staff';

// KPI counts (scoped to own requests for Staff, all requests otherwise)
$scopeSql = $isStaff ? 'WHERE requester_id = ?' : '';
$scopeParams = $isStaff ? [$user['id']] : [];

$counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'Returned' => 0, 'Total' => 0];
$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM requisitions $scopeSql GROUP BY status");
$stmt->execute($scopeParams);
foreach ($stmt->fetchAll() as $row) { $counts[$row['status']] = (int)$row['c']; $counts['Total'] += (int)$row['c']; }

// Requests per program
$programs = $pdo->query('SELECT * FROM programs ORDER BY name')->fetchAll();
$progCountStmt = $pdo->prepare("SELECT COUNT(*) FROM requisitions WHERE program_id = ?" . ($isStaff ? ' AND requester_id = ?' : ''));

// Search/filter
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'All';
$dept = $_GET['dept'] ?? 'All';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$sql = "SELECT r.*, u.name AS requester_name, p.code AS program_code FROM requisitions r
        JOIN users u ON u.id = r.requester_id
        JOIN programs p ON p.id = r.program_id WHERE 1=1";
$params = [];
if ($isStaff) { $sql .= ' AND r.requester_id = ?'; $params[] = $user['id']; }
if ($q !== '') { $sql .= ' AND (r.ref_no LIKE ? OR r.description LIKE ? OR r.category LIKE ? OR r.department LIKE ?)'; $like = "%$q%"; array_push($params, $like, $like, $like, $like); }
if ($status !== 'All') { $sql .= ' AND r.status = ?'; $params[] = $status; }
if ($dept !== 'All') { $sql .= ' AND r.department = ?'; $params[] = $dept; }
if ($from !== '') { $sql .= ' AND r.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $sql .= ' AND r.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
$sql .= ' ORDER BY r.created_at DESC LIMIT 50';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$departments = $pdo->query('SELECT DISTINCT department FROM users ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);

$isApprover = in_array($user['role'], ['Supervisor', 'Finance', 'CEO'], true);
$myQueue = [];
if ($isApprover) {
    $qStmt = $pdo->prepare("SELECT r.*, u.name AS requester_name, p.code AS program_code
        FROM requisitions r JOIN users u ON u.id = r.requester_id JOIN programs p ON p.id = r.program_id
        WHERE r.status = 'Pending' AND r.current_level = ? ORDER BY r.created_at ASC LIMIT 8");
    $qStmt->execute([$user['role']]);
    $myQueue = $qStmt->fetchAll();
}
$notifStmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6');
$notifStmt->execute([$user['id']]);
$myNotifs = $notifStmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
  <div><h2><?= $isStaff ? 'My Dashboard' : 'Organization Dashboard' ?></h2><p>Overview of requisitions <?= $isStaff ? 'you have submitted' : 'across FPDA' ?>.</p></div>
</div>

<form method="get" class="global-search">
  <input type="text" name="q" placeholder="Search requisitions by reference, description, category, or department..." value="<?= h($q) ?>">
  <button class="btn-primary" type="submit">Search</button>
</form>

<div class="kpi-grid">
  <div class="kpi gold"><div class="num"><?= $counts['Pending'] ?></div><div class="lbl">Pending</div></div>
  <div class="kpi"><div class="num"><?= $counts['Approved'] ?></div><div class="lbl">Approved</div></div>
  <div class="kpi red"><div class="num"><?= $counts['Rejected'] ?></div><div class="lbl">Rejected</div></div>
  <div class="kpi blue"><div class="num"><?= $counts['Total'] ?></div><div class="lbl">Total Requests</div></div>
</div>

<?php if ($isApprover): ?>
<div class="panel" style="border-left:4px solid var(--gold);">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <h3 style="margin:0;">⚡ Awaiting your approval (<?= h($user['role']) ?>)</h3>
    <?php if (!empty($myQueue)): ?><a class="btn btn-gold btn-sm" href="approvals.php">Open Approvals →</a><?php endif; ?>
  </div>
  <?= render_request_table($myQueue) ?>
</div>
<?php endif; ?>

<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
    <h3 style="margin:0;">🔔 Recent notifications</h3>
    <a class="btn btn-ghost btn-sm" href="notifications.php">View all</a>
  </div>
  <?php if (empty($myNotifs)): ?>
    <div class="empty-row">No notifications yet — you'll see updates here as requests move through approval.</div>
  <?php else: foreach ($myNotifs as $n): ?>
    <div style="padding:9px 0;border-bottom:1px solid var(--line);<?= $n['is_read'] ? 'opacity:.6;' : '' ?>display:flex;justify-content:space-between;gap:10px;align-items:baseline;">
      <span style="font-size:13.5px;"><?= h($n['message']) ?></span>
      <span style="font-size:11px;color:var(--ink-soft);white-space:nowrap;"><?= fmt_date($n['created_at']) ?></span>
    </div>
  <?php endforeach; endif; ?>
</div>

<div class="panel">
  <h3>Requests by program</h3>
  <?php foreach ($programs as $p):
      $progCountStmt->execute($isStaff ? [$p['id'], $user['id']] : [$p['id']]);
      $c = $progCountStmt->fetchColumn(); ?>
    <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:6px 0;border-bottom:1px solid var(--line);">
      <span><?= h($p['name']) ?> <span class="mono" style="color:var(--ink-soft);">(<?= h($p['code']) ?>)</span></span><strong><?= $c ?></strong>
    </div>
  <?php endforeach; ?>
</div>

<div class="panel">
  <h3>Recent requests</h3>
  <form method="get" class="filter-row">
    <input type="text" name="q" placeholder="Search..." value="<?= h($q) ?>">
    <select name="status" onchange="this.form.submit()">
      <?php foreach (['All','Pending','Approved','Rejected','Returned'] as $s): ?>
        <option <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <select name="dept" onchange="this.form.submit()">
      <option <?= $dept === 'All' ? 'selected' : '' ?>>All</option>
      <?php foreach ($departments as $d): ?><option <?= $dept === $d ? 'selected' : '' ?>><?= h($d) ?></option><?php endforeach; ?>
    </select>
    <input type="date" name="from" value="<?= h($from) ?>" onchange="this.form.submit()">
    <input type="date" name="to" value="<?= h($to) ?>" onchange="this.form.submit()">
    <button class="btn-ghost btn-sm" type="submit">Apply</button>
  </form>
  <?php if (empty($requests)): ?>
    <div class="empty-state"><div class="big">📄</div>No requisitions match yet.</div>
  <?php else: ?>
    <table>
      <thead><tr><th>Ref</th><th>Category</th><th>Requester</th><th>Program</th><th>Amount</th><th>Status</th><th>Stage</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
          <tr>
            <td class="mono"><?= h($r['ref_no']) ?></td>
            <td><span class="cat-tag"><?= h($r['category']) ?></span></td>
            <td><?= h($r['requester_name']) ?></td>
            <td><?= h($r['program_code']) ?></td>
            <td><?= fmt_k($r['estimated_cost']) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td><?= $r['current_level'] ? h($r['current_level']) : '—' ?></td>
            <td><?= fmt_date($r['created_at']) ?></td>
            <td><a class="btn-ghost btn-sm" href="view_requisition.php?id=<?= $r['id'] ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
