<?php
/**
 * ERP / Finance System Integration Endpoint
 * ------------------------------------------------------------------
 * Read-only, machine-to-machine export of APPROVED requisitions, for pulling
 * into an external ERP or finance/accounting system (SAP, Xero, MYOB, etc.).
 *
 * Auth: header  X-API-Key: <FINANCE_API_KEY from config.php>
 * Usage: GET api_export.php?since=2026-01-01   (optional date filter)
 *
 * This does not modify any FRFAS data — it's a one-way export. A real
 * deployment would rotate FINANCE_API_KEY regularly and serve this over
 * HTTPS only.
 */
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json');

$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');

if (!$providedKey || !hash_equals(FINANCE_API_KEY, $providedKey)) {
    http_response_code(401);
    log_activity($pdo, null, 'External system', 'api_access', 'Rejected ERP export request — invalid or missing API key from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.');
    echo json_encode(['error' => 'Unauthorized — missing or invalid X-API-Key header.']);
    exit;
}

$since = $_GET['since'] ?? null;
$sql = "SELECT r.ref_no, r.category, r.department, p.code AS program_code, p.name AS program_name,
               u.name AS requester_name, r.estimated_cost, r.approved_amount, r.status,
               r.created_at, r.current_level
        FROM requisitions r
        JOIN users u ON u.id = r.requester_id
        JOIN programs p ON p.id = r.program_id
        WHERE r.status = 'Approved'";
$params = [];
if ($since) {
    $sql .= ' AND r.created_at >= ?';
    $params[] = $since . ' 00:00:00';
}
$sql .= ' ORDER BY r.created_at DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

log_activity($pdo, null, 'External system', 'api_access', 'ERP/Finance export accessed (' . count($rows) . ' approved requisitions returned) from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '.');

echo json_encode([
    'system' => 'FRFAS',
    'exported_at' => date('c'),
    'count' => count($rows),
    'requisitions' => $rows,
]);
