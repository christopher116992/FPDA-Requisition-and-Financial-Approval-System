<?php
/**
 * fix_admin.php — a deliberately minimal, standalone "break glass" repair tool.
 *
 * Unlike install.php (which also creates tables, seeds programs, etc. and could
 * itself fail partway through on an unusual database state), this script does
 * exactly ONE thing: guarantee a fully working Admin login exists. It has no
 * dependencies on the rest of the app's include chain, so it keeps working even
 * if something else in the codebase is broken.
 *
 * Visit this file directly in your browser:  http://localhost/frfas/fix_admin.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$dbHost = 'localhost';
$dbName = 'frfas_db';
$dbUser = 'root';
$dbPass = '';
$domain = '@fpda.com.pg';
$log = [];
$fatal = null;

try {
    // Connect to the server first (no DB selected) in case frfas_db itself is missing.
    $root = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $root->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4");
    $log[] = "Connected to MySQL and confirmed database \"$dbName\" exists.";

    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create the users table if it does not exist at all yet (bare minimum columns).
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        username VARCHAR(60) NOT NULL UNIQUE,
        email VARCHAR(120) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('Staff','Supervisor','Finance','CEO','Admin') NOT NULL DEFAULT 'Staff',
        department VARCHAR(120) NOT NULL DEFAULT 'ICT',
        position_title VARCHAR(120) DEFAULT NULL,
        avatar VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $log[] = "Confirmed the users table exists.";

    // Make sure every security/status column this app relies on actually exists.
    // Each ALTER is wrapped individually so one already-existing column never blocks the rest.
    $columns = [
        'is_active'      => "TINYINT(1) NOT NULL DEFAULT 1",
        'failed_attempts'=> "INT NOT NULL DEFAULT 0",
        'locked_until'   => "DATETIME NULL DEFAULT NULL",
        'last_login_at'  => "DATETIME NULL DEFAULT NULL",
        'theme'          => "VARCHAR(20) NOT NULL DEFAULT 'light'",
        'font_pref'      => "VARCHAR(20) NOT NULL DEFAULT 'default'",
    ];
    foreach ($columns as $col => $def) {
        $exists = $pdo->query("SHOW COLUMNS FROM users LIKE " . $pdo->quote($col))->fetch();
        if (!$exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN `$col` $def");
            $log[] = "Added missing column \"$col\" to users table.";
        }
    }

    // Does an Admin account already exist?
    $stmt = $pdo->query("SELECT id, username, email FROM users WHERE role = 'Admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $newHash = password_hash('admin123', PASSWORD_DEFAULT);

    if ($admin) {
        // Force this Admin account into a guaranteed-working state.
        $pdo->prepare("UPDATE users SET
                password = ?,
                is_active = 1,
                failed_attempts = 0,
                locked_until = NULL
            WHERE id = ?")
            ->execute([$newHash, $admin['id']]);
        $log[] = "Found existing Admin account (username: \"{$admin['username']}\"). "
               . "Reset its password to admin123, reactivated it, and cleared any lockout.";
        $finalUsername = $admin['username'];
        $finalEmail = $admin['email'];
    } else {
        // Is there a username "admin" that just isn't Admin-role for some reason? Promote it.
        $existing = $pdo->prepare("SELECT id, username, email FROM users WHERE username = 'admin' OR email = ?");
        $existing->execute(['admin' . $domain]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $pdo->prepare("UPDATE users SET
                    role = 'Admin',
                    password = ?,
                    is_active = 1,
                    failed_attempts = 0,
                    locked_until = NULL
                WHERE id = ?")
                ->execute([$newHash, $row['id']]);
            $log[] = "Found an \"admin\" account that wasn't set to the Admin role — fixed its role, "
                   . "reset its password to admin123, and reactivated it.";
            $finalUsername = $row['username'];
            $finalEmail = $row['email'];
        } else {
            $pdo->prepare("INSERT INTO users (name, username, email, password, role, department, position_title, is_active)
                VALUES (?,?,?,?,?,?,?,1)")
                ->execute(['ICT Admin', 'admin', 'admin' . $domain, $newHash, 'Admin', 'ICT', 'ICT Systems Administrator']);
            $log[] = "No Admin account existed at all — created a brand new one.";
            $finalUsername = 'admin';
            $finalEmail = 'admin' . $domain;
        }
    }

} catch (Exception $e) {
    $fatal = $e->getMessage();
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>FRFAS Admin Repair</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#F7F4EC;color:#1C2321;max-width:680px;margin:50px auto;padding:0 20px;}
h1{color:#152C1F;}
.card{background:#fff;border-radius:10px;padding:22px 26px;box-shadow:0 2px 10px rgba(0,0,0,.08);margin-bottom:16px;}
.ok{color:#1F3D2B;} .err{color:#A8402F;}
table{width:100%;border-collapse:collapse;font-size:15px;margin-top:10px;}
td{padding:8px;border-bottom:1px solid #eee;}
a.btn{display:inline-block;background:#1F3D2B;color:#fff;padding:10px 18px;border-radius:7px;text-decoration:none;margin-top:6px;}
code{background:#F3E4C2;padding:2px 6px;border-radius:4px;}
</style></head><body>
<h1>FRFAS — Admin Account Repair</h1>

<?php if ($fatal): ?>
<div class="card">
  <p class="err"><strong>Could not complete the repair:</strong> <?= htmlspecialchars($fatal) ?></p>
  <p>This usually means MySQL isn't running in XAMPP, or the credentials in this script (host/user/password) don't match your setup. Open the XAMPP Control Panel and confirm MySQL shows "Running", then reload this page.</p>
</div>
<?php else: ?>
<div class="card">
  <ul>
    <?php foreach ($log as $line): ?><li class="ok"><?= htmlspecialchars($line) ?></li><?php endforeach; ?>
  </ul>
</div>
<div class="card">
  <h3>Your working Admin login</h3>
  <table>
    <tr><td><strong>Username</strong></td><td><code><?= htmlspecialchars($finalUsername) ?></code></td></tr>
    <tr><td><strong>Email</strong></td><td><code><?= htmlspecialchars($finalEmail) ?></code></td></tr>
    <tr><td><strong>Password</strong></td><td><code>admin123</code></td></tr>
  </table>
  <a class="btn" href="login.php">Go to Login →</a>
</div>
<p style="color:#5B655D;font-size:13px;">You can run this page again any time — it always resets to the same known-working state. Change the password from My Profile once you're logged in.</p>
<?php endif; ?>
</body></html>
