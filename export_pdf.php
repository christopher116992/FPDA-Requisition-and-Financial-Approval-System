<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/simple_pdf.php';
$user = require_role($pdo, ['Finance', 'CEO', 'Admin']);

$stmt = $pdo->query("SELECT r.ref_no, r.category, r.division, p.code AS program_code, u.name AS requester_name,
        r.estimated_cost, r.approved_amount, r.status, r.created_at
        FROM requisitions r JOIN users u ON u.id = r.requester_id JOIN programs p ON p.id = r.program_id
        WHERE r.status = 'Approved' ORDER BY r.created_at DESC");
$rows = $stmt->fetchAll();

log_activity($pdo, $user['id'], $user['name'], 'admin', 'Exported approved requisitions to PDF for finance/ERP import.');

$pdf = new SimplePDF(__DIR__ . '/assets/images/fpda_logo.jpg');
$pdf->heading('Fresh Produce Development Agency');
$pdf->heading('Approved Requisitions Export', 13);
$pdf->subtext('FRFAS - Requisition (TA & Quotation) and Financial Approval System');
$pdf->subtext('Generated ' . date('d M Y, g:i A') . '  |  By ' . $user['name']);
$pdf->spacer(12);

$widths = [70, 95, 90, 65, 110, 90, 90, 70];
$pdf->row(['Reference', 'Category', 'Division', 'Program', 'Requester', 'Estimated (K)', 'Approved (K)', 'Status'], $widths, 9, true);
$pdf->hLine();
$pdf->spacer(4);

foreach ($rows as $r) {
    $pdf->row([
        $r['ref_no'],
        $r['category'],
        $r['division'],
        $r['program_code'],
        $r['requester_name'],
        number_format($r['estimated_cost'], 2),
        $r['approved_amount'] !== null ? number_format($r['approved_amount'], 2) : '-',
        $r['status'],
    ], $widths, 8.5);
}

$pdf->spacer(10);
$pdf->leftText('Total records: ' . count($rows));

$pdf->output('frfas-approved-requisitions-' . date('Y-m-d') . '.pdf');
