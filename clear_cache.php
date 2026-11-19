<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    // Auto-backup first (encrypted, checksummed)
    save_encrypted_backup($pdo, $user['name'] . ' (auto, before clear)');

    $pdo->exec('DELETE FROM notifications');
    // Keep the very action we're about to log — clear everything before it, then log the clear itself
    $pdo->exec('DELETE FROM activity_log');
    log_activity($pdo, $user['id'], $user['name'], 'admin', 'Cleared all notifications and system logs.');
    flash_set('Notifications and logs cleared.');
}
header('Location: admin_danger.php');
exit;
