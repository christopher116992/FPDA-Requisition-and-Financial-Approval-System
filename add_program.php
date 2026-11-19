<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role($pdo, ['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $allocated = (float)($_POST['allocated'] ?? 0);

    if ($name === '' || $code === '' || $allocated <= 0) {
        flash_set('Fill in all program fields correctly.', 'err');
    } else {
        try {
            $pdo->prepare('INSERT INTO programs (code, name, allocated, spent) VALUES (?,?,?,0)')->execute([$code, $name, $allocated]);
            flash_set("Program \"$name\" added.");
        } catch (PDOException $e) {
            flash_set('That program code already exists.', 'err');
        }
    }
}
header('Location: budget.php');
exit;
