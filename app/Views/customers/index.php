<div style="display:grid; grid-template-columns: 1fr 320px; gap:20px; align-items:start;">
    <div class="card">
        <div class="card-header"><h3>All Customers</h3></div>
        <table>
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Loyalty Points</th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
                <?php if ($c['name'] === 'Walk-in Customer') continue; ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                    <td><?= (int) $c['loyalty_points'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header"><h3>Add Customer</h3></div>
        <form method="POST" action="<?= base_url('/customers') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Phone</label><input type="tel" name="phone"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
            <div class="form-group"><label>Address</label><textarea name="address" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Add Customer</button>
        </form>
    </div>
</div>
