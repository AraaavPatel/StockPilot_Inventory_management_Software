<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — StockPilot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="card auth-card">
        <div class="brand" style="margin-bottom:22px;">
            <div class="brand-mark">SP</div>
            <div class="brand-name">StockPilot</div>
        </div>
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-sub">Sign in to continue to your dashboard.</p>

        <?php if (!empty($_SESSION['flash']['error'])): ?>
            <div class="flash flash-error"><?= htmlspecialchars($_SESSION['flash']['error']) ?></div>
            <?php unset($_SESSION['flash']['error']); ?>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('/login') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus placeholder="admin@stockpilot.test">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <?php if (($_ENV['APP_DEBUG'] ?? 'false') === 'true'): ?>
        <p style="margin-top:18px; font-size:12px; color:#9a9a96;">
            Dev seed accounts — never shown when APP_DEBUG=false: admin@stockpilot.test / manager@stockpilot.test / cashier@stockpilot.test — password: password123
        </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
