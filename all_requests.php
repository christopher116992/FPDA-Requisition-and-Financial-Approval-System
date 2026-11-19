<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Supervisor', 'Finance', 'CEO', 'Admin']);
$activeTab = 'all-requests';

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'All';
$dept = $_GET['dept'] ?? 'All';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$departments = $pdo->query('SELECT DISTINCT department FROM users ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);
$requests = fetch_filtered_requisitions($pdo, $user, 'all', $q, $status, $dept, $from, $to);

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><h2>All Requests</h2><p>Every requisition submitted across FPDA.</p></div></div>
<div class="panel">
  <?= render_filter_form($q, $status, $dept, $from, $to, $departments) ?>
  <?= render_request_table($requests) ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
