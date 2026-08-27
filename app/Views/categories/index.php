<div style="display:grid; grid-template-columns: 1fr 320px; gap:20px; align-items:start;">
    <div class="card">
        <div class="card-header"><h3>All Categories</h3></div>
        <table>
            <thead><tr><th>Name</th><th>Description</th><th>Products</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['description'] ?? '—') ?></td>
                    <td><?= (int) $c['product_count'] ?></td>
                    <td><span class="badge <?= $c['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                    <td>
                        <form method="POST" action="<?= base_url("/categories/{$c['id']}/delete") ?>" onsubmit="return confirm('Delete this category?');" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <tr><td colspan="5">No categories yet — add your first one.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header"><h3>Add Category</h3></div>
        <form method="POST" action="<?= base_url('/categories') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required placeholder="e.g. Dairy">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Optional"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Add Category</button>
        </form>
    </div>
</div>
