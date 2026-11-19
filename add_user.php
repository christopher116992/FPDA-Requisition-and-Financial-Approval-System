<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Staff';
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $validRoles = ['Staff','Supervisor','Finance','CEO','Admin'];

    if ($name === '' || $username === '' || $password === '' || $department === '' || !in_array($role, $validRoles, true)) {
        flash_set('Fill in all user fields.', 'err');
    } elseif (!preg_match('/^[a-z0-9._-]+$/', $username)) {
        flash_set('Username may only contain letters, numbers, dots, dashes, and underscores.', 'err');
    } else {
        $email = $username . FPDA_DOMAIN;
        $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $exists->execute([$username, $email]);
        if ($exists->fetchColumn() > 0) {
            flash_set('That username already exists.', 'err');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (name, username, email, password, role, department, position_title) VALUES (?,?,?,?,?,?,?)')
                ->execute([$name, $username, $email, $hash, $role, $department, $position ?: null]);
            log_activity($pdo, $user['id'], $user['name'], 'admin', "Added new user \"$name\" ($role, $email).");
            flash_set("User \"$name\" added — logs in as $email.");
        }
    }
}
header('Location: admin_users.php');
exit;
