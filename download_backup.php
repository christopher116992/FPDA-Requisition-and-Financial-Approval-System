<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM backups WHERE id = ?');
$stmt->execute([$id]);
$backup = $stmt->fetch();

if (!$backup) {
    flash_set('Backup not found.', 'err');
    header('Location: admin_backups.php');
    exit;
}

$path = __DIR__ . '/backups/' . $backup['filename'];
[$snapshot, $err] = load_encrypted_backup($path, $backup['checksum']);
if ($err) {
    flash_set($err, 'err');
    header('Location: admin_backups.php');
    exit;
}

log_activity($pdo, $user['id'], $user['name'], 'admin', "Downloaded backup {$backup['filename']} (integrity verified).");

$downloadName = str_replace('.enc', '.json', $backup['filename']);
$json = json_encode($snapshot, JSON_PRETTY_PRINT);
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . strlen($json));
echo $json;
exit;
