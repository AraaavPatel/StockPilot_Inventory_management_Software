<?php
/**
 * ONE-TIME PRODUCTION SETUP — creates the first admin account.
 *
 * Do NOT deploy the dev seed data (database/seeds/002_seed_data.sql,
 * password "password123" for every account) to production. Import
 * only database/deploy/infinityfree.sql, then run this script once
 * to create a real admin with a password only you know.
 *
 * SAFETY:
 *   - Requires SETUP_TOKEN to be set in .env and passed as ?token=...
 *     in the URL. Without a matching token, this refuses to run.
 *   - Writes storage/setup-complete.lock after successful use and
 *     refuses to run again once that file exists — so even if you
 *     forget to delete this file, it can't be used a second time.
 *   - DELETE THIS FILE (public/setup-admin.php) after use. It is
 *     listed as a required step in DEPLOYMENT_INFINITYFREE.md.
 */

require_once __DIR__ . '/../config/config.php';

use App\Core\Database;

$lockFile = __DIR__ . '/../storage/setup-complete.lock';
$configuredToken = config('SETUP_TOKEN', '');

function fail(string $message): void
{
    http_response_code(403);
    echo '<p style="font-family:sans-serif;color:#900;">' . htmlspecialchars($message) . '</p>';
    exit;
}

if (file_exists($lockFile)) {
    fail('Setup has already been completed. Delete public/setup-admin.php and storage/setup-complete.lock if you need to run it again intentionally.');
}

if ($configuredToken === '') {
    fail('Set SETUP_TOKEN in your .env before using this page (any long random string). This prevents strangers from creating an admin account on your live site.');
}

if (($_GET['token'] ?? '') !== $configuredToken) {
    fail('Invalid or missing setup token.');
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if ($name === '' || $email === '' || strlen($password) < 12) {
        $error = 'Name, email, and a password of at least 12 characters are all required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'That email is already registered.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO users (name, email, password_hash, role, status)
                 VALUES (:name, :email, :hash, "admin", "active")'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
            file_put_contents($lockFile, 'Admin created ' . date('c') . " for {$email}\n");
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>StockPilot — First Admin Setup</title>
<style>body{font-family:sans-serif;max-width:420px;margin:60px auto;padding:0 16px;} input{width:100%;padding:8px;margin:6px 0 14px;box-sizing:border-box;} button{padding:10px 18px;background:#111;color:#fff;border:none;cursor:pointer;} .err{color:#900;} .ok{color:#080;}</style>
</head>
<body>
<h2>Create your first admin account</h2>
<?php if ($success): ?>
    <p class="ok">Admin account created. <strong>Now delete this file (public/setup-admin.php) from your hosting file manager immediately.</strong> You can log in at <a href="<?= htmlspecialchars(base_url('/login')) ?>">/login</a>.</p>
<?php else: ?>
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
        <label>Full name</label>
        <input type="text" name="name" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password (12+ characters)</label>
        <input type="password" name="password" required minlength="12">
        <label>Confirm password</label>
        <input type="password" name="password_confirm" required minlength="12">
        <button type="submit">Create Admin</button>
    </form>
<?php endif; ?>
</body>
</html>
