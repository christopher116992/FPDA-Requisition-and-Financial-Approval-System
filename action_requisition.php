<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }
csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$comment = trim($_POST['comment'] ?? '');

$stmt = $pdo->prepare('SELECT * FROM requisitions WHERE id = ?');
$stmt->execute([$id]);
$req = $stmt->fetch();

if (!$req) { flash_set('Requisition not found.', 'err'); header('Location: dashboard.php'); exit; }

$flow = explode(',', $req['flow']);
$redirect = "view_requisition.php?id=$id";

if ($action === 'resubmit') {
    if ($req['status'] !== 'Returned' || (int)$req['requester_id'] !== (int)$user['id']) {
        flash_set('You cannot resubmit this requisition.', 'err');
        header('Location: ' . $redirect); exit;
    }
    $description = trim($_POST['description'] ?? $req['description']);
    $upd = $pdo->prepare('UPDATE requisitions SET description = ?, status = "Pending", current_level = ? WHERE id = ?');
    $upd->execute([$description, $flow[0], $id]);
    log_history($pdo, $id, $user['name'], 'Resubmitted', 'Requester', 'Updated and resubmitted after clarification.');
    log_activity($pdo, $user['id'], $user['name'], 'resubmit', "Resubmitted {$req['ref_no']} after clarification.");
    notify_role($pdo, $flow[0], "{$req['ref_no']} resubmitted by {$user['name']}.", $id, $user['id']);
    flash_set("You've resubmitted request {$req['ref_no']} for review.");
    header('Location: ' . $redirect); exit;
}

// Approve / reject / return — only the user whose role matches the current pending level may act
if ($req['status'] !== 'Pending' || $req['current_level'] !== $user['role']) {
    flash_set('This requisition is no longer awaiting your decision.', 'err');
    header('Location: ' . $redirect); exit;
}

if (in_array($action, ['reject', 'return'], true) && $comment === '') {
    flash_set('Please add a comment explaining your decision.', 'err');
    header('Location: ' . $redirect); exit;
}

if ($action === 'approve') {
    $levelIdx = array_search($req['current_level'], $flow, true);
    log_history($pdo, $id, $user['name'], 'Approved', $req['current_level'], $comment);
    log_activity($pdo, $user['id'], $user['name'], 'approve', "Approved {$req['ref_no']} as {$req['current_level']}.");

    $approvedAmount = $req['approved_amount'];
    if ($req['current_level'] === 'Finance' && isset($_POST['approved_amount']) && $_POST['approved_amount'] !== '') {
        $approvedAmount = (float)$_POST['approved_amount'];
        $pdo->prepare('UPDATE requisitions SET approved_amount = ? WHERE id = ?')->execute([$approvedAmount, $id]);
    }

    if ($levelIdx === count($flow) - 1) {
        // Final approval
        if ($approvedAmount === null) $approvedAmount = $req['estimated_cost'];
        $pdo->prepare('UPDATE requisitions SET status = "Approved", current_level = NULL, approved_amount = ? WHERE id = ?')
            ->execute([$approvedAmount, $id]);
        $pdo->prepare('UPDATE programs SET spent = spent + ? WHERE id = ?')->execute([$approvedAmount, $req['program_id']]);
        add_notification($pdo, $req['requester_id'], "Your requisition {$req['ref_no']} was fully approved (" . fmt_k($approvedAmount) . ').', $id);
        flash_set("You've approved request {$req['ref_no']}. It is now fully approved.");
    } else {
        $nextLevel = $flow[$levelIdx + 1];
        $pdo->prepare('UPDATE requisitions SET current_level = ? WHERE id = ?')->execute([$nextLevel, $id]);
        notify_role($pdo, $nextLevel, "{$req['ref_no']} approved by {$req['current_level']}, now awaiting $nextLevel.", $id, $user['id']);
        add_notification($pdo, $req['requester_id'], "Your requisition {$req['ref_no']} was approved by {$req['current_level']} and moved to $nextLevel.", $id);
        flash_set("You've approved request {$req['ref_no']}. It has moved on to $nextLevel.");
    }
} elseif ($action === 'reject') {
    $pdo->prepare('UPDATE requisitions SET status = "Rejected", current_level = NULL WHERE id = ?')->execute([$id]);
    log_history($pdo, $id, $user['name'], 'Rejected', $req['current_level'], $comment);
    log_activity($pdo, $user['id'], $user['name'], 'reject', "Rejected {$req['ref_no']} as {$req['current_level']}.");
    add_notification($pdo, $req['requester_id'], "Your requisition {$req['ref_no']} was rejected by {$user['role']}.", $id);
    flash_set("You've rejected request {$req['ref_no']}.", 'err');
} elseif ($action === 'return') {
    $pdo->prepare('UPDATE requisitions SET status = "Returned" WHERE id = ?')->execute([$id]);
    log_history($pdo, $id, $user['name'], 'Returned for clarification', $req['current_level'], $comment);
    log_activity($pdo, $user['id'], $user['name'], 'return', "Returned {$req['ref_no']} for clarification as {$req['current_level']}.");
    add_notification($pdo, $req['requester_id'], "Your requisition {$req['ref_no']} was returned by {$user['role']} — clarification needed.", $id);
    flash_set("You've requested changes (modify) on {$req['ref_no']} — it has been sent back to the requester.");
}

header('Location: ' . $redirect);
exit;
