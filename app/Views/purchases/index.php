<div class="card">
    <div class="card-header">
        <h3>Purchase History</h3>
        <a href="<?= base_url('/purchases/create') ?>" class="btn btn-accent btn-sm">+ Record Purchase</a>
    </div>
    <table>
        <thead><tr><th>Date</th><th>Supplier</th><th>Invoice #</th><th>Recorded By</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($purchases as $p): ?>
            <tr>
                <td><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
                <td><?= htmlspecialchars($p['supplier_name']) ?></td>
                <td><?= htmlspecialchars($p['invoice_no'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['recorded_by']) ?></td>
                <td>₹<?= number_format($p['total_amount'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($purchases)): ?>
            <tr><td colspan="5">No purchases recorded yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
