<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Finance', 'CEO', 'Admin']);

$stmt = $pdo->query("SELECT r.ref_no, r.category, r.department, p.code AS program_code, u.name AS requester_name,
        r.estimated_cost, r.approved_amount, r.status, r.created_at
        FROM requisitions r JOIN users u ON u.id = r.requester_id JOIN programs p ON p.id = r.program_id
        WHERE r.status = 'Approved' ORDER BY r.created_at DESC");
$rows = $stmt->fetchAll();

log_activity($pdo, $user['id'], $user['name'], 'admin', 'Exported approved requisitions to CSV for finance/ERP import.');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="frfas-approved-requisitions-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Reference', 'Category', 'Department', 'Program Code', 'Requester', 'Estimated Cost (PGK)', 'Approved Amount (PGK)', 'Status', 'Date']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['ref_no'], $r['category'], $r['department'], $r['program_code'], $r['requester_name'],
        number_format($r['estimated_cost'], 2, '.', ''),
        $r['approved_amount'] !== null ? number_format($r['approved_amount'], 2, '.', '') : '',
        $r['status'], $r['created_at'],
    ]);
}
fclose($out);
exit;
