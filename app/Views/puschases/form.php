<div class="card" style="max-width:760px;">
    <div class="card-header"><h3>Record Purchase (Stock In)</h3></div>

    <form method="POST" action="<?= base_url('/purchases') ?>" id="purchaseForm">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="items" id="itemsInput">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div class="form-group">
                <label>Supplier</label>
                <select name="supplier_id" required>
                    <option value="">Select…</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Supplier Invoice #</label>
                <input type="text" name="invoice_no" placeholder="Optional">
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div class="form-group">
                <label>Purchase Date</label>
                <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Optional">
            </div>
        </div>

        <div class="form-group">
            <label>Products Received</label>
            <table>
                <thead><tr><th>Product</th><th style="width:100px;">Qty</th><th style="width:120px;">Unit Cost</th><th style="width:100px;">Line Total</th><th></th></tr></thead>
                <tbody id="lineItemsBody"></tbody>
            </table>
            <button type="button" class="btn btn-outline btn-sm" id="addLineBtn" style="margin-top:10px;">+ Add Line</button>
        </div>

        <div style="text-align:right; font-size:18px; font-weight:800; margin:14px 0;">
            Total: <span id="grandTotal">₹0.00</span>
        </div>

        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary">Save Purchase &amp; Update Stock</button>
            <a href="<?= base_url('/purchases') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'cost_price' => $p['cost_price']], $products)) ?>;
let lineCount = 0;

function productOptions(selectedId = '') {
    return '<option value="">Select…</option>' + PRODUCTS.map(p =>
        `<option value="${p.id}" data-cost="${p.cost_price}" ${p.id == selectedId ? 'selected' : ''}>${p.name}</option>`
    ).join('');
}

function addLine() {
    const idx = lineCount++;
    const row = document.createElement('tr');
    row.dataset.idx = idx;
    row.innerHTML = `
        <td><select class="line-product" onchange="onProductChange(this)">${productOptions()}</select></td>
        <td><input type="number" class="line-qty" min="1" value="1" oninput="recalcLine(this)"></td>
        <td><input type="number" class="line-cost" min="0" step="0.01" value="0" oninput="recalcLine(this)"></td>
        <td class="line-total">₹0.00</td>
        <td><button type="button" class="btn btn-outline btn-sm" onclick="removeLine(this)">✕</button></td>
    `;
    document.getElementById('lineItemsBody').appendChild(row);
}

function onProductChange(select) {
    const opt = select.options[select.selectedIndex];
    const cost = opt ? opt.dataset.cost : 0;
    const row = select.closest('tr');
    row.querySelector('.line-cost').value = cost || 0;
    recalcLine(select);
}

function recalcLine(el) {
    const row = el.closest('tr');
    const qty = parseFloat(row.querySelector('.line-qty').value) || 0;
    const cost = parseFloat(row.querySelector('.line-cost').value) || 0;
    row.querySelector('.line-total').textContent = '₹' + (qty * cost).toFixed(2);
    recalcGrandTotal();
}

function removeLine(btn) {
    btn.closest('tr').remove();
    recalcGrandTotal();
}

function recalcGrandTotal() {
    let total = 0;
    document.querySelectorAll('#lineItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.line-qty').value) || 0;
        const cost = parseFloat(row.querySelector('.line-cost').value) || 0;
        total += qty * cost;
    });
    document.getElementById('grandTotal').textContent = '₹' + total.toFixed(2);
}

document.getElementById('addLineBtn').addEventListener('click', addLine);
addLine(); // start with one empty line

document.getElementById('purchaseForm').addEventListener('submit', (e) => {
    const items = [];
    document.querySelectorAll('#lineItemsBody tr').forEach(row => {
        const productId = row.querySelector('.line-product').value;
        const qty = row.querySelector('.line-qty').value;
        const cost = row.querySelector('.line-cost').value;
        if (productId && qty > 0) {
            items.push({ product_id: productId, quantity: qty, unit_cost: cost });
        }
    });
    if (items.length === 0) {
        alert('Add at least one product line.');
        e.preventDefault();
        return;
    }
    document.getElementById('itemsInput').value = JSON.stringify(items);
});
</script>
