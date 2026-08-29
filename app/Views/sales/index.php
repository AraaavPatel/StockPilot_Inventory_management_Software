<div class="card">
    <div class="card-header"><h3>Sales History</h3></div>
    <table>
        <thead><tr><th>Invoice</th><th>Customer</th><th>Cashier</th><th>Payment</th><th>Total</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['invoice_no']) ?></td>
                <td><?= htmlspecialchars($s['customer_name']) ?></td>
                <td><?= htmlspecialchars($s['cashier_name']) ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($s['payment_method']) ?></span></td>
                <td>₹<?= number_format($s['total_amount'], 2) ?></td>
                <td><?= date('d M Y, H:i', strtotime($s['sale_date'])) ?></td>
                <td><a href="<?= base_url("/pos/invoice/{$s['id']}") ?>" class="btn btn-outline btn-sm">View</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($sales)): ?>
            <tr><td colspan="7">No sales recorded yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
