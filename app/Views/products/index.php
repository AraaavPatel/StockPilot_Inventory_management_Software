<?php $canManage = in_array($authUser['role'] ?? '', ['admin', 'manager'], true); ?>
<div class="card">
    <div class="card-header">
        <h3>All Products</h3>
        <?php if ($canManage): ?>
            <a href="<?= base_url('/products/create') ?>" class="btn btn-accent btn-sm">+ Add Product</a>
        <?php endif; ?>
    </div>
    <table>
        <thead>
            <tr><th>Name</th><th>Category</th><th>SKU / Barcode</th><th>Price</th><th>GST</th><th>Stock</th><th>Status</th><?php if ($canManage): ?><th></th><?php endif; ?></tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr class="<?= $p['stock_qty'] <= $p['low_stock_threshold'] ? 'low-stock-row' : '' ?>">
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category_name']) ?></td>
                <td style="font-size:12px; color:var(--ink-soft);"><?= htmlspecialchars($p['sku']) ?><br><?= htmlspecialchars($p['barcode']) ?></td>
                <td>₹<?= number_format($p['selling_price'], 2) ?></td>
                <td><?= number_format($p['gst_percent'], 1) ?>%</td>
                <td><?= (int) $p['stock_qty'] ?> <?= htmlspecialchars($p['unit']) ?></td>
                <td><span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                <?php if ($canManage): ?>
                <td style="white-space:nowrap;">
                    <a href="<?= base_url("/products/{$p['id']}/edit") ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="POST" action="<?= base_url("/products/{$p['id']}/delete") ?>" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <tr><td colspan="8">No products yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
