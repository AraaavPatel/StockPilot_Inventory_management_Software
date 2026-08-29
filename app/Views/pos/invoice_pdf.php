<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    /* Dompdf has limited CSS support: no flexbox, no CSS variables, no grid. */
    body { font-family: DejaVu Sans, sans-serif; color: #1c1c1c; font-size: 12px; }
    h2, h3 { margin: 0 0 4px 0; }
    p { margin: 0 0 2px 0; color: #55575c; }
    table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e3e3e0; font-size: 11px; }
    th { text-transform: uppercase; font-size: 9px; color: #9a9a96; }
    .right { text-align: right; }
    .header-table td { border: none; vertical-align: top; padding: 0; }
    .summary-table td { border: none; padding: 3px 0; }
    .total-row td { font-weight: bold; font-size: 14px; border-top: 1px solid #1c1c1c; padding-top: 8px; }
    .muted { color: #9a9a96; }
</style>
</head>
<body>

<table class="header-table">
    <tr>
        <td style="width:60%;">
            <h2><?= htmlspecialchars($store['name']) ?></h2>
            <p><?= htmlspecialchars($store['address']) ?></p>
            <?php if ($store['gstin']): ?><p>GSTIN: <?= htmlspecialchars($store['gstin']) ?></p><?php endif; ?>
        </td>
        <td class="right">
            <h3>Invoice</h3>
            <p><?= htmlspecialchars($sale['invoice_no']) ?></p>
            <p><?= date('d M Y, h:i A', strtotime($sale['sale_date'])) ?></p>
        </td>
    </tr>
</table>

<table class="header-table" style="margin-top:14px;">
    <tr>
        <td style="width:60%;">
            <strong>Billed To</strong><br>
            <?= htmlspecialchars($sale['customer']['name'] ?? 'Walk-in Customer') ?>
            <?php if (!empty($sale['customer']['phone'])): ?><br><?= htmlspecialchars($sale['customer']['phone']) ?><?php endif; ?>
        </td>
        <td class="right">
            <strong>Cashier:</strong> <?= htmlspecialchars($sale['cashier_name']) ?><br>
            <strong>Payment:</strong> <?= strtoupper(htmlspecialchars($sale['payment_method'])) ?>
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr><th>Item</th><th>Qty</th><th>Rate</th><th>GST</th><th class="right">Amount</th></tr>
    </thead>
    <tbody>
    <?php foreach ($sale['items'] as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['product_name']) ?> <span class="muted">(<?= htmlspecialchars($item['sku']) ?>)</span></td>
            <td><?= (int) $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
            <td>Rs. <?= number_format($item['unit_price'], 2) ?></td>
            <td><?= number_format($item['gst_percent'], 1) ?>%</td>
            <td class="right">Rs. <?= number_format($item['line_total'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<table class="summary-table" style="margin-top:10px; width:220px; float:right;">
    <tr><td>Subtotal</td><td class="right">Rs. <?= number_format($sale['subtotal'], 2) ?></td></tr>
    <tr><td>GST</td><td class="right">Rs. <?= number_format($sale['gst_amount'], 2) ?></td></tr>
    <tr><td>Discount</td><td class="right">- Rs. <?= number_format($sale['discount_amount'], 2) ?></td></tr>
    <tr class="total-row"><td>Total</td><td class="right">Rs. <?= number_format($sale['total_amount'], 2) ?></td></tr>
</table>

<div style="clear:both;"></div>
<p style="text-align:center; margin-top:40px; font-size:10px;">Thank you for shopping with us!</p>

</body>
</html>
