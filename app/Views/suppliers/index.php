<div class="split-layout split-md">
    <div class="card">
        <div class="card-header"><h3>All Suppliers</h3></div>
        <div class="table-responsive">
<table>
            <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>GSTIN</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($s['gstin'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= base_url("/suppliers/{$s['id']}/delete") ?>" onsubmit="return confirm('Delete this supplier?');" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($suppliers)): ?>
                <tr><td colspan="5">No suppliers yet — add your first one.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
</div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Add Supplier</h3></div>
        <form method="POST" action="<?= base_url('/suppliers') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person"></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
            <div class="form-group"><label>Address</label><textarea name="address" rows="2"></textarea></div>
            <div class="form-group"><label>GSTIN</label><input type="text" name="gstin"></div>
            <button type="submit" class="btn btn-primary btn-block">Add Supplier</button>
        </form>
    </div>
</div>
