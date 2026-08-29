<?php $isEdit = $product !== null; ?>
<div class="card" style="max-width:640px;">
    <div class="card-header"><h3><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h3></div>
    <form method="POST" action="<?= $isEdit ? base_url("/products/{$product['id']}") : base_url('/products') ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>">
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select…</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= ($product['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" value="<?= htmlspecialchars($product['unit'] ?? 'pcs') ?>" placeholder="pcs, kg, ltr...">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku" required value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Barcode</label>
                <input type="text" name="barcode" required value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Cost Price (₹)</label>
                <input type="number" step="0.01" min="0" name="cost_price" value="<?= htmlspecialchars($product['cost_price'] ?? '0') ?>">
            </div>
            <div class="form-group">
                <label>Selling Price (₹)</label>
                <input type="number" step="0.01" min="0" name="selling_price" required value="<?= htmlspecialchars($product['selling_price'] ?? '0') ?>">
            </div>
            <div class="form-group">
                <label>GST %</label>
                <input type="number" step="0.01" min="0" name="gst_percent" value="<?= htmlspecialchars($product['gst_percent'] ?? '0') ?>">
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label>Stock Qty</label>
                <input type="number" min="0" name="stock_qty" value="<?= htmlspecialchars($product['stock_qty'] ?? '0') ?>">
            </div>
            <div class="form-group">
                <label>Low Stock Threshold</label>
                <input type="number" min="0" name="low_stock_threshold" value="<?= htmlspecialchars($product['low_stock_threshold'] ?? '5') ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>

        <div class="inline-actions" style="margin-top:8px;">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Add Product' ?></button>
            <a href="<?= base_url('/products') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
