<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($sale['invoice_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
    <style>
        body { background: var(--canvas); padding: 40px; }
        .invoice-wrap { max-width: 620px; margin: 0 auto; }
        .invoice-actions { display:flex; gap:10px; margin-bottom:16px; justify-content:flex-end; flex-wrap:wrap; }
        .invoice-header-row { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        table.items th, table.items td { font-size: 13px; }
        @media print { .invoice-actions { display:none; } body { padding:0; background:#fff; } }

        @media (max-width: 640px) {
            body { padding: 14px; }
            .invoice-actions { justify-content:stretch; }
            .invoice-actions a, .invoice-actions button { flex:1; text-align:center; }
            .invoice-header-row > div:last-child,
            .invoice-header-row > div:nth-child(2) { text-align:left; }
            .card[style*="padding:32px"] { padding: 18px !important; }
        }
    </style>
</head>
<body>
<div class="invoice-wrap">
    <div class="invoice-actions">
        <a href="<?= base_url('/pos') ?>" class="btn btn-outline">Back to POS</a>
        <button onclick="window.print()" class="btn btn-outline">Print</button>
        <a href="<?= e($downloadUrl) ?>" class="btn btn-primary">Download PDF</a>
    </div>

    <div id="whatsappNotice" style="display:none; margin-bottom:16px;" class="flash flash-success"></div>
    <script>
        const notice = sessionStorage.getItem('whatsappNotice');
        if (notice) {
            const box = document.getElementById('whatsappNotice');
            box.textContent = notice;
            box.style.display = 'block';
            sessionStorage.removeItem('whatsappNotice');
        }
    </script>

    <div class="card" style="padding:32px;">
        <div class="invoice-header-row" style="margin-bottom:24px;">
            <div>
                <h2><?= htmlspecialchars($store['name']) ?></h2>
                <p><?= htmlspecialchars($store['address']) ?></p>
                <?php if ($store['gstin']): ?><p>GSTIN: <?= htmlspecialchars($store['gstin']) ?></p><?php endif; ?>
            </div>
            <div style="text-align:right;">
                <h3>Invoice</h3>
                <p><?= htmlspecialchars($sale['invoice_no']) ?></p>
                <p><?= date('d M Y, h:i A', strtotime($sale['sale_date'])) ?></p>
            </div>
        </div>

        <div class="invoice-header-row" style="margin-bottom:20px; font-size:14px;">
            <div>
                <strong>Billed To</strong><br>
                <?= htmlspecialchars($sale['customer']['name'] ?? 'Walk-in Customer') ?>
                <?php if (!empty($sale['customer']['phone'])): ?><br><?= htmlspecialchars($sale['customer']['phone']) ?><?php endif; ?>
            </div>
            <div style="text-align:right;">
                <strong>Cashier</strong><br>
                <?= htmlspecialchars($sale['cashier_name']) ?><br>
                Payment: <?= strtoupper(htmlspecialchars($sale['payment_method'])) ?>
            </div>
        </div>

        <div class="table-responsive table-narrow">
        <table class="items">
            <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>GST</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($sale['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?> <span style="color:#9a9a96;">(<?= htmlspecialchars($item['sku']) ?>)</span></td>
                    <td><?= (int) $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                    <td>₹<?= number_format($item['unit_price'], 2) ?></td>
                    <td><?= number_format($item['gst_percent'], 1) ?>%</td>
                    <td>₹<?= number_format($item['line_total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <div style="margin-top:20px; padding-top:16px; border-top:1px solid var(--line);">
            <div class="pos-summary-row"><span>Subtotal</span><span>₹<?= number_format($sale['subtotal'], 2) ?></span></div>
            <div class="pos-summary-row"><span>GST</span><span>₹<?= number_format($sale['gst_amount'], 2) ?></span></div>
            <div class="pos-summary-row"><span>Discount</span><span>− ₹<?= number_format($sale['discount_amount'], 2) ?></span></div>
            <div class="pos-summary-row total"><span>Total</span><span>₹<?= number_format($sale['total_amount'], 2) ?></span></div>
        </div>

        <p style="text-align:center; margin-top:28px; font-size:12px;">Thank you for shopping with us!</p>
    </div>
</div>
</body>
</html>
