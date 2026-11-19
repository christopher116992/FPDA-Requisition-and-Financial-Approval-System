<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_login($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password'])) {
        flash_set('Current password is incorrect.', 'err');
    } elseif (strlen($new) < 6) {
        flash_set('New password must be at least 6 characters.', 'err');
    } elseif ($new !== $confirm) {
        flash_set('New password and confirmation do not match.', 'err');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);
        log_activity($pdo, $user['id'], $user['name'], 'profile', 'Changed their own password.');
        flash_set('Password updated.');
    }
}
header('Location: profile.php');
exit;
