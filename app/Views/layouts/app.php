<?php
/** @var array|null $authUser */
$role = $authUser['role'] ?? 'cashier';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

function navActive(string $path, string $current): string {
    return str_starts_with($current, $path) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockPilot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">SP</div>
            <div class="brand-name">StockPilot</div>
        </div>

        <button type="button" class="mobile-topbar-toggle" id="mobileMenuToggle" aria-label="Open menu">☰</button>

        <nav class="nav-group" id="desktopNav">
            <a href="<?= base_url('/dashboard') ?>" class="nav-link <?= navActive('/dashboard', $currentPath) ?>">Dashboard</a>
            <a href="<?= base_url('/pos') ?>" class="nav-link <?= navActive('/pos', $currentPath) ?>">POS Billing</a>

            <div class="nav-label">Catalog</div>
            <a href="<?= base_url('/products') ?>" class="nav-link <?= navActive('/products', $currentPath) ?>">Products</a>
            <a href="<?= base_url('/categories') ?>" class="nav-link <?= navActive('/categories', $currentPath) ?>">Categories</a>

            <?php if (in_array($role, ['admin', 'manager'], true)): ?>
            <div class="nav-label">Procurement</div>
            <a href="<?= base_url('/suppliers') ?>" class="nav-link <?= navActive('/suppliers', $currentPath) ?>">Suppliers</a>
            <a href="<?= base_url('/purchases') ?>" class="nav-link <?= navActive('/purchases', $currentPath) ?>">Purchases</a>
            <?php endif; ?>

            <div class="nav-label">Sales</div>
            <a href="<?= base_url('/sales') ?>" class="nav-link <?= navActive('/sales', $currentPath) ?>">Sales History</a>
            <a href="<?= base_url('/customers') ?>" class="nav-link <?= navActive('/customers', $currentPath) ?>">Customers</a>

            <?php if (in_array($role, ['admin', 'manager'], true)): ?>
            <div class="nav-label">Reports</div>
            <a href="<?= base_url('/reports') ?>" class="nav-link <?= navActive('/reports', $currentPath) ?>">Sales &amp; Stock Reports</a>
            <a href="<?= base_url('/stock-adjustments') ?>" class="nav-link <?= navActive('/stock-adjustments', $currentPath) ?>">Stock Adjustments</a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
            <div class="nav-label">Admin</div>
            <a href="<?= base_url('/users') ?>" class="nav-link <?= navActive('/users', $currentPath) ?>">User Management</a>
            <a href="<?= base_url('/audit-logs') ?>" class="nav-link <?= navActive('/audit-logs', $currentPath) ?>">Security &amp; Audit Logs</a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Full-screen slide-in menu for mobile: reuses the same links, hidden by default -->
    <nav id="mobileMenu" style="display:none; position:fixed; inset:0; z-index:50; background:#111; padding:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <div class="brand-name" style="color:#fff;">Menu</div>
            <button type="button" id="mobileMenuClose" style="background:none;border:2px solid #fff;color:#fff;width:36px;height:36px;border-radius:4px;font-size:16px;">✕</button>
        </div>
        <div style="display:flex; flex-direction:column; gap:4px;">
            <a href="<?= base_url('/dashboard') ?>" class="nav-link" style="color:#fff;">Dashboard</a>
            <a href="<?= base_url('/pos') ?>" class="nav-link" style="color:#fff;">POS Billing</a>
            <a href="<?= base_url('/products') ?>" class="nav-link" style="color:#fff;">Products</a>
            <a href="<?= base_url('/categories') ?>" class="nav-link" style="color:#fff;">Categories</a>
            <?php if (in_array($role, ['admin', 'manager'], true)): ?>
            <a href="<?= base_url('/suppliers') ?>" class="nav-link" style="color:#fff;">Suppliers</a>
            <a href="<?= base_url('/purchases') ?>" class="nav-link" style="color:#fff;">Purchases</a>
            <?php endif; ?>
            <a href="<?= base_url('/sales') ?>" class="nav-link" style="color:#fff;">Sales History</a>
            <a href="<?= base_url('/customers') ?>" class="nav-link" style="color:#fff;">Customers</a>
            <?php if (in_array($role, ['admin', 'manager'], true)): ?>
            <a href="<?= base_url('/reports') ?>" class="nav-link" style="color:#fff;">Reports</a>
            <a href="<?= base_url('/stock-adjustments') ?>" class="nav-link" style="color:#fff;">Stock Adjustments</a>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?>
            <a href="<?= base_url('/users') ?>" class="nav-link" style="color:#fff;">User Management</a>
            <a href="<?= base_url('/audit-logs') ?>" class="nav-link" style="color:#fff;">Security &amp; Audit Logs</a>
            <?php endif; ?>
            <a href="<?= base_url('/logout') ?>" class="nav-link" style="color:#fff; margin-top:16px; border-top:1px solid #333; padding-top:16px;">Logout</a>
        </div>
    </nav>

    <!-- Bottom tab bar for mobile: the highest-frequency actions only -->
    <nav class="bottom-nav">
        <a href="<?= base_url('/dashboard') ?>" class="<?= navActive('/dashboard', $currentPath) ?>"><span class="icon">▦</span>Home</a>
        <a href="<?= base_url('/pos') ?>" class="<?= navActive('/pos', $currentPath) ?>"><span class="icon">▣</span>Bill</a>
        <a href="<?= base_url('/products') ?>" class="<?= navActive('/products', $currentPath) ?>"><span class="icon">▤</span>Stock</a>
        <a href="<?= base_url('/sales') ?>" class="<?= navActive('/sales', $currentPath) ?>"><span class="icon">▥</span>Sales</a>
        <a href="<?= base_url('/customers') ?>" class="<?= navActive('/customers', $currentPath) ?>"><span class="icon">◍</span>Customers</a>
    </nav>

    <script>
        (function() {
            var toggle = document.getElementById('mobileMenuToggle');
            var menu = document.getElementById('mobileMenu');
            var close = document.getElementById('mobileMenuClose');
            if (toggle && menu && close) {
                toggle.addEventListener('click', function() { menu.style.display = 'block'; });
                close.addEventListener('click', function() { menu.style.display = 'none'; });
            }
        })();
    </script>

    <div class="main">
        <header class="topbar">
            <div class="topbar-title"><?= e($pageTitle ?? 'StockPilot') ?></div>
            <div class="user-chip">
                <span class="role-badge"><?= htmlspecialchars($role) ?></span>
                <div class="user-avatar"><?= strtoupper(substr($authUser['name'] ?? 'U', 0, 1)) ?></div>
                <span><?= htmlspecialchars($authUser['name'] ?? '') ?></span>
                <a href="<?= base_url('/logout') ?>" class="btn btn-outline btn-sm">Logout</a>
            </div>
        </header>

        <main class="content">
            <?php if (!empty($_SESSION['flash'])): ?>
                <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                    <div class="flash flash-<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($message) ?></div>
                <?php endforeach; ?>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
