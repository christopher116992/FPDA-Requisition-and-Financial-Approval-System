<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/simple_pdf.php';
$user = require_role($pdo, ['Supervisor', 'Finance', 'CEO', 'Admin']);

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
$sql .= " ORDER BY rh.created_at DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

log_activity($pdo, $user['id'], $user['name'], 'admin', 'Exported the audit trail to PDF' . ($q !== '' ? " (filtered: \"$q\")" : '') . '.');

$pdf = new SimplePDF(__DIR__ . '/assets/images/fpda_logo.jpg');
$pdf->heading('Fresh Produce Development Agency');
$pdf->heading('Audit Trail Export', 13);
$pdf->subtext('FRFAS - Requisition (TA & Quotation) and Financial Approval System');
$pdf->subtext('Generated ' . date('d M Y, g:i A') . '  |  By ' . $user['name'] . ($q !== '' ? '  |  Filter: "' . $q . '"' : ''));
$pdf->spacer(12);

$widths = [95, 65, 90, 75, 65, 90, 220];
$pdf->row(['Timestamp', 'Ref', 'Requester', 'Action', 'Level', 'By', 'Comment'], $widths, 9, true);
$pdf->hLine();
$pdf->spacer(4);

foreach ($entries as $e) {
    $comment = $e['comment'] ?: '-';
    if (strlen($comment) > 70) $comment = substr($comment, 0, 67) . '...';
    $pdf->row([
        fmt_datetime($e['created_at']),
        $e['ref_no'],
        $e['requester_name'],
        $e['action'],
        ($e['level'] && $e['level'] !== 'Requester') ? $e['level'] : '-',
        $e['actor_name'],
        $comment,
    ], $widths, 8);
}

$pdf->spacer(10);
$pdf->leftText('Total records: ' . count($entries) . ' (most recent 500 shown)');

$pdf->output('frfas-audit-trail-' . date('Y-m-d') . '.pdf');
