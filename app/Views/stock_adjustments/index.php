<div style="display:grid; grid-template-columns: 1fr 360px; gap:20px; align-items:start;">
    <div class="card">
        <div class="card-header"><h3>Recent Adjustments</h3></div>
        <table>
            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Reason</th><th>By</th></tr></thead>
            <tbody>
            <?php foreach ($adjustments as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['created_at']) ?></td>
                    <td><?= htmlspecialchars($a['product_name']) ?> <span style="color:var(--ink-soft); font-size:12px;">(<?= htmlspecialchars($a['sku']) ?>)</span></td>
                    <td><span class="badge <?= $a['adjustment_type'] === 'add' ? 'badge-success' : 'badge-neutral' ?>"><?= htmlspecialchars(ucfirst($a['adjustment_type'])) ?></span></td>
                    <td><?= (int) $a['quantity'] ?></td>
                    <td><?= htmlspecialchars($a['reason']) ?></td>
                    <td><?= htmlspecialchars($a['user_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($adjustments)): ?>
                <tr><td colspan="6">No stock adjustments recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header"><h3>Record Adjustment</h3></div>
        <form method="POST" action="<?= base_url('/stock-adjustments') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label for="product_id">Product</label>
                <select id="product_id" name="product_id" required>
                    <option value="">Select…</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (stock: <?= (int) $p['stock_qty'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="adjustment_type">Type</label>
                <select id="adjustment_type" name="adjustment_type" required>
                    <option value="add">Add (found stock, new recount)</option>
                    <option value="remove">Remove (damage, theft, expiry)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" min="1" required>
            </div>
            <div class="form-group">
                <label for="reason">Reason</label>
                <input type="text" id="reason" name="reason" required placeholder="e.g. Monthly recount, breakage">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Save Adjustment</button>
        </form>
        <p style="font-size:12px; color:var(--ink-soft); margin-top:12px;">
            Every adjustment is written to the immutable audit log with your name, IP, and server timestamp — see
            <a href="<?= base_url('/audit-logs') ?>">Security &amp; Audit Logs</a>.
        </p>
    </div>
</div>
