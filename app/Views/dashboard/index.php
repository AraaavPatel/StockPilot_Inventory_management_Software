<div class="stat-grid">
    <div class="card stat-card accent" data-mark="01">
        <div class="stat-label">Today's Sales</div>
        <div class="stat-value">₹<?= number_format($todaySales['total'], 2) ?></div>
        <div class="stat-sub"><?= (int) $todaySales['cnt'] ?> bills today</div>
    </div>
    <div class="card stat-card" data-mark="02">
        <div class="stat-label">This Month</div>
        <div class="stat-value">₹<?= number_format($monthSales['total'], 2) ?></div>
        <div class="stat-sub">Total revenue, month-to-date</div>
    </div>
    <div class="card stat-card" data-mark="03">
        <div class="stat-label">Active Products</div>
        <div class="stat-value"><?= (int) $productCount ?></div>
        <div class="stat-sub">in catalog</div>
    </div>
    <div class="card stat-card" data-mark="!" style="<?= $lowStockCount > 0 ? 'border-color: var(--red);' : '' ?>">
        <div class="stat-label">Low Stock Alerts</div>
        <div class="stat-value" style="color: var(--danger);"><?= (int) $lowStockCount ?></div>
        <div class="stat-sub">items at or below threshold</div>
    </div>
</div>

<div class="split-layout split-dashboard">
    <div class="card">
        <div class="card-header"><h3>Sales — Last 7 Days</h3></div>
        <canvas id="salesTrendChart" height="90"></canvas>
    </div>

    <div class="card">
        <div class="card-header"><h3>Low Stock</h3></div>
        <?php if (empty($lowStock)): ?>
            <p>All products are above their reorder threshold.</p>
        <?php else: ?>
            <div class="table-responsive">
<table>
                <thead><tr><th>Product</th><th>Qty</th><th>Threshold</th></tr></thead>
                <tbody>
                <?php foreach ($lowStock as $p): ?>
                    <tr class="low-stock-row">
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= (int) $p['stock_qty'] ?></td>
                        <td><?= (int) $p['low_stock_threshold'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
</div>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header"><h3>Recent Sales</h3></div>
    <div class="table-responsive">
<table>
        <thead>
        <tr><th>Invoice</th><th>Customer</th><th>Cashier</th><th>Payment</th><th>Amount</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recentSales as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['invoice_no']) ?></td>
                <td><?= htmlspecialchars($s['customer_name']) ?></td>
                <td><?= htmlspecialchars($s['cashier_name']) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($s['payment_method']) ?></span></td>
                <td>₹<?= number_format($s['total_amount'], 2) ?></td>
                <td><?= date('d M, H:i', strtotime($s['sale_date'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recentSales)): ?>
            <tr><td colspan="6">No sales recorded yet. Head to POS Billing to record your first sale.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const trendLabels = <?= json_encode(array_map(fn($t) => date('D', strtotime($t['d'])), $trend)) ?>;
const trendData = <?= json_encode(array_map(fn($t) => (float) $t['total'], $trend)) ?>;

new Chart(document.getElementById('salesTrendChart'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: 'Sales (₹)',
            data: trendData,
            borderColor: '#1c1c1c',
            backgroundColor: 'rgba(28,28,28,0.06)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: '#e3e3e0' }, beginAtZero: true }
        }
    }
});
</script>
