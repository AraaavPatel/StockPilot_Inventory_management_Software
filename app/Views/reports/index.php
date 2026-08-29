<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Date Range</h3></div>
<<<<<<< HEAD
    <form method="GET" action="<?= base_url('/reports') ?>" class="filter-bar">
=======
    <form method="GET" action="<?= base_url('/reports') ?>" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
        <div class="form-group" style="margin:0;">
            <label for="from">From</label>
            <input type="date" id="from" name="from" value="<?= htmlspecialchars($from) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label for="to">To</label>
            <input type="date" id="to" name="to" value="<?= htmlspecialchars($to) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Apply</button>
        <a class="btn btn-outline" href="<?= base_url("/reports/export?from={$from}&to={$to}") ?>">Export CSV</a>
    </form>
</div>

<<<<<<< HEAD
<div class="split-layout split-even">
    <div class="card">
        <div class="card-header"><h3>Daily Sales</h3></div>
        <div class="table-responsive">
<table>
=======
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
    <div class="card">
        <div class="card-header"><h3>Daily Sales</h3></div>
        <table>
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
            <thead><tr><th>Date</th><th>Bills</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($daily as $d): ?>
                <tr><td><?= htmlspecialchars($d['d']) ?></td><td><?= (int) $d['bills'] ?></td><td>₹<?= number_format($d['total'], 2) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($daily)): ?><tr><td colspan="3">No sales in this range.</td></tr><?php endif; ?>
            </tbody>
        </table>
<<<<<<< HEAD
</div>
=======
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
    </div>

    <div class="card">
        <div class="card-header"><h3>Top Products</h3></div>
<<<<<<< HEAD
        <div class="table-responsive">
<table>
=======
        <table>
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
            <thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php foreach ($topProducts as $p): ?>
                <tr><td><?= htmlspecialchars($p['name']) ?></td><td><?= (int) $p['qty_sold'] ?></td><td>₹<?= number_format($p['revenue'], 2) ?></td></tr>
            <?php endforeach; ?>
            <?php if (empty($topProducts)): ?><tr><td colspan="3">No sales in this range.</td></tr><?php endif; ?>
            </tbody>
        </table>
<<<<<<< HEAD
</div>
=======
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header"><h3>Low Stock</h3></div>
<<<<<<< HEAD
    <div class="table-responsive">
<table>
=======
    <table>
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
        <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Threshold</th></tr></thead>
        <tbody>
        <?php foreach ($lowStock as $p): ?>
            <tr><td><?= htmlspecialchars($p['name']) ?></td><td><?= htmlspecialchars($p['sku']) ?></td><td><?= (int) $p['stock_qty'] ?></td><td><?= (int) $p['low_stock_threshold'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($lowStock)): ?><tr><td colspan="4">Nothing below threshold. 🎉</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<<<<<<< HEAD
</div>
=======
>>>>>>> e54dec794bc2b19873f7a02489d8cadfa02fddeb
