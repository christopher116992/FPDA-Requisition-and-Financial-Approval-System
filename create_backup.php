<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$filename, $err] = save_encrypted_backup($pdo, $user['name']);
    if ($err) {
        flash_set($err, 'err');
    } else {
        log_activity($pdo, $user['id'], $user['name'], 'admin', "Created manual encrypted backup ($filename).");
        flash_set('Backup created successfully — encrypted at rest with an integrity checksum.');
    }
}
header('Location: admin_backups.php');
exit;
