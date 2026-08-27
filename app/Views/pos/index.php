<div class="pos-grid">
    <!-- LEFT: Scan + Cart -->
    <div class="card">
        <div class="card-header">
            <h3>Scan &amp; Bill</h3>
            <span id="cameraStatus" style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--ink-soft);">Starting camera…</span>
        </div>

        <div class="pos-scan-mode" style="display:flex; gap:8px; margin-bottom:12px;">
            <button type="button" class="btn btn-outline btn-sm scan-mode-btn active" id="modeCameraBtn" data-mode="camera">📷 Camera</button>
            <button type="button" class="btn btn-outline btn-sm scan-mode-btn" id="modeHardwareBtn" data-mode="hardware">🔌 USB / Hardware Scanner</button>
            <button type="button" class="btn btn-outline btn-sm scan-mode-btn" id="modeManualBtn" data-mode="manual">⌨ Manual</button>
        </div>

        <div id="qr-reader" style="margin-bottom:16px;"></div>
        <p id="scanHint" style="font-size:12px; margin-bottom:12px;">Hold the barcode flat, fill the box, and keep it steady for a second — a 720p webcam needs good light and a close, still shot to read 1D barcodes reliably.</p>

        <div class="pos-scan-box">
            <input type="text" id="scanInput" placeholder="Scan barcode or type SKU / product name, then Enter" autofocus>
            <button type="button" class="btn btn-primary" id="scanBtn">Add</button>
        </div>
        <div id="suggestionsBox" style="margin-bottom:12px;"></div>

        <table class="pos-cart-table">
            <thead>
                <tr><th>Product</th><th>Price</th><th>Qty</th><th>GST</th><th>Total</th><th></th></tr>
            </thead>
            <tbody id="cartBody">
                <tr id="emptyCartRow"><td colspan="6">Cart is empty — scan a product to begin.</td></tr>
            </tbody>
        </table>
    </div>

    <!-- RIGHT: Summary + Checkout -->
    <div class="card pos-summary-card">
        <div class="card-header"><h3>Bill Summary</h3></div>

        <div class="form-group">
            <label for="customerSelect">Customer</label>
            <select id="customerSelect">
                <option value="">Walk-in Customer</option>
                <?php foreach ($customers as $c): ?>
                    <?php if ($c['name'] !== 'Walk-in Customer'): ?>
                        <option value="<?= $c['id'] ?>" data-phone="<?= htmlspecialchars($c['phone'] ?? '') ?>"><?= htmlspecialchars($c['name']) ?><?= $c['phone'] ? ' — ' . htmlspecialchars($c['phone']) : '' ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <button type="button" id="newCustomerToggle" class="btn btn-outline btn-sm" style="margin-top:8px;">+ New Customer</button>

            <div id="newCustomerBox" style="display:none; margin-top:10px; padding:12px; border:2px solid var(--line); border-radius:4px;">
                <input type="text" id="newCustomerName" placeholder="Name (optional)" style="margin-bottom:8px;">
                <input type="tel" id="newCustomerPhone" placeholder="Phone number" inputmode="numeric" style="margin-bottom:8px;">
                <button type="button" id="saveNewCustomerBtn" class="btn btn-primary btn-sm btn-block">Save &amp; Select</button>
                <div id="newCustomerError" style="color:var(--danger); font-size:12px; margin-top:6px;"></div>
            </div>
        </div>

        <div class="form-group">
            <label for="paymentMethod">Payment Method</label>
            <select id="paymentMethod">
                <option value="cash">Cash</option>
                <option value="upi">UPI</option>
                <option value="card">Card</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="form-group">
            <label for="discountInput">Discount (₹)</label>
            <input type="number" id="discountInput" min="0" step="0.01" value="0">
        </div>

        <div class="form-group">
            <label for="whatsappInput">Send invoice to WhatsApp (optional)</label>
            <input type="tel" id="whatsappInput" placeholder="e.g. 9812345678" inputmode="numeric">
        </div>

        <div class="pos-summary-row"><span>Subtotal</span><span id="sumSubtotal">₹0.00</span></div>
        <div class="pos-summary-row"><span>GST</span><span id="sumGst">₹0.00</span></div>
        <div class="pos-summary-row"><span>Discount</span><span id="sumDiscount">− ₹0.00</span></div>
        <div class="pos-summary-row total"><span>Total</span><span id="sumTotal">₹0.00</span></div>

        <button type="button" class="btn btn-accent btn-block" id="checkoutBtn" style="margin-top:16px;">Checkout &amp; Print Invoice</button>
        <p id="checkoutError" style="color:var(--danger); margin-top:10px; font-size:13px;"></p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
const CSRF = <?= json_encode($csrf) ?>;
const LOOKUP_URL = <?= json_encode(base_url('/pos/lookup')) ?>;
const CHECKOUT_URL = <?= json_encode(base_url('/pos/checkout')) ?>;

let cart = []; // { product_id, name, unit_price, gst_percent, quantity, stock_qty }

const cartBody = document.getElementById('cartBody');
const scanInput = document.getElementById('scanInput');
const suggestionsBox = document.getElementById('suggestionsBox');

function money(n) { return '₹' + Number(n).toFixed(2); }

function renderCart() {
    if (cart.length === 0) {
        cartBody.innerHTML = '<tr id="emptyCartRow"><td colspan="6">Cart is empty — scan a product to begin.</td></tr>';
    } else {
        cartBody.innerHTML = cart.map((item, idx) => {
            const lineTotal = item.unit_price * item.quantity * (1 + item.gst_percent / 100);
            return `<tr>
                <td>${item.name}</td>
                <td>${money(item.unit_price)}</td>
                <td>
                    <button class="pos-qty-btn" onclick="changeQty(${idx}, -1)">−</button>
                    <span style="padding:0 8px;">${item.quantity}</span>
                    <button class="pos-qty-btn" onclick="changeQty(${idx}, 1)">+</button>
                </td>
                <td>${item.gst_percent}%</td>
                <td>${money(lineTotal)}</td>
                <td><button class="btn btn-outline btn-sm" onclick="removeItem(${idx})">✕</button></td>
            </tr>`;
        }).join('');
    }
    renderSummary();
}

function renderSummary() {
    let subtotal = 0, gst = 0;
    cart.forEach(item => {
        const base = item.unit_price * item.quantity;
        subtotal += base;
        gst += base * (item.gst_percent / 100);
    });
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const total = Math.max(0, subtotal + gst - discount);

    document.getElementById('sumSubtotal').textContent = money(subtotal);
    document.getElementById('sumGst').textContent = money(gst);
    document.getElementById('sumDiscount').textContent = '− ' + money(discount);
    document.getElementById('sumTotal').textContent = money(total);
}

function changeQty(idx, delta) {
    const item = cart[idx];
    const newQty = item.quantity + delta;
    if (newQty < 1) { removeItem(idx); return; }
    if (newQty > item.stock_qty) { alert(`Only ${item.stock_qty} in stock.`); return; }
    item.quantity = newQty;
    renderCart();
}

function removeItem(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function addProductToCart(product) {
    const existing = cart.find(i => i.product_id === product.id);
    if (existing) {
        if (existing.quantity + 1 > product.stock_qty) { alert(`Only ${product.stock_qty} in stock.`); return; }
        existing.quantity += 1;
    } else {
        cart.push({
            product_id: product.id,
            name: product.name,
            unit_price: parseFloat(product.selling_price),
            gst_percent: parseFloat(product.gst_percent),
            quantity: 1,
            stock_qty: product.stock_qty,
        });
    }
    renderCart();
    scanInput.value = '';
    suggestionsBox.innerHTML = '';
    scanInput.focus();
}

async function lookupCode(code) {
    if (!code.trim()) return;
    const res = await fetch(LOOKUP_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `code=${encodeURIComponent(code)}&_csrf=${CSRF}`
    });
    const data = await res.json();

    if (data.found) {
        addProductToCart(data.product);
    } else if (data.suggestions && data.suggestions.length) {
        suggestionsBox.innerHTML = '<div class="card" style="padding:10px;">' +
            data.suggestions.map(p => `<div style="padding:6px 4px; cursor:pointer;" onclick='addProductToCart(${JSON.stringify(p)})'>${p.name} — ₹${p.selling_price} (${p.stock_qty} in stock)</div>`).join('') +
            '</div>';
    } else {
        document.getElementById('checkoutError').textContent = data.message || 'Product not found.';
        setTimeout(() => document.getElementById('checkoutError').textContent = '', 2500);
    }
}

scanInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); lookupCode(scanInput.value); }
});
document.getElementById('scanBtn').addEventListener('click', () => lookupCode(scanInput.value));
document.getElementById('discountInput').addEventListener('input', renderSummary);

const CUSTOMER_STORE_URL = <?= json_encode(base_url('/customers')) ?>;

// ---- Quick-add customer inline (phone-first — name is optional, matches
// how kirana billing actually works: owner rarely has time to ask for a name) ----
const newCustomerToggle = document.getElementById('newCustomerToggle');
const newCustomerBox = document.getElementById('newCustomerBox');
const customerSelect = document.getElementById('customerSelect');
const whatsappInput = document.getElementById('whatsappInput');

newCustomerToggle.addEventListener('click', () => {
    newCustomerBox.style.display = newCustomerBox.style.display === 'none' ? 'block' : 'none';
    if (newCustomerBox.style.display === 'block') document.getElementById('newCustomerPhone').focus();
});

document.getElementById('saveNewCustomerBtn').addEventListener('click', async () => {
    const phone = document.getElementById('newCustomerPhone').value.trim();
    const name = document.getElementById('newCustomerName').value.trim();
    const errBox = document.getElementById('newCustomerError');

    if (!phone) { errBox.textContent = 'Phone number is required.'; return; }

    const body = new URLSearchParams({ name: name || 'Walk-in Customer', phone, ajax: '1', _csrf: CSRF });
    const res = await fetch(CUSTOMER_STORE_URL, { method: 'POST', body });
    const data = await res.json();

    if (!data.success) { errBox.textContent = data.message || 'Could not save customer.'; return; }

    const c = data.customer;
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.dataset.phone = c.phone || '';
    opt.textContent = c.name + (c.phone ? ' — ' + c.phone : '');
    if (!data.existed) customerSelect.appendChild(opt);

    customerSelect.value = c.id;
    // Phone typed for the customer record doubles as the WhatsApp send-to number —
    // one entry, not two, since the owner won't type it twice per sale.
    whatsappInput.value = c.phone || phone;

    newCustomerBox.style.display = 'none';
    document.getElementById('newCustomerName').value = '';
    document.getElementById('newCustomerPhone').value = '';
    errBox.textContent = '';
});

// Selecting an existing customer with a saved phone auto-fills WhatsApp too.
customerSelect.addEventListener('change', () => {
    const opt = customerSelect.options[customerSelect.selectedIndex];
    const phone = opt ? opt.dataset.phone : '';
    if (phone) whatsappInput.value = phone;
});
// A cheap laptop camera struggles most with 1D barcodes (EAN/UPC/Code128) because
// they need more horizontal resolution than a QR code does at the same distance.
// Fixes applied: restrict decode formats (faster, fewer false reads), use a wide
// short scan box matching a barcode's shape, and debounce repeat scans of the
// same code since a barcode held in frame gets decoded many times per second.

let html5QrCode = null;
let lastScan = { code: null, at: 0 };
const SCAN_COOLDOWN_MS = 1800; // block the same code from re-adding for this long

function beep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        osc.frequency.value = 880;
        osc.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.08);
    } catch (e) { /* audio not critical */ }
}

function handleDecodedCode(decodedText) {
    const now = Date.now();
    if (decodedText === lastScan.code && (now - lastScan.at) < SCAN_COOLDOWN_MS) {
        return; // same barcode still in frame — ignore repeat fires
    }
    lastScan = { code: decodedText, at: now };
    beep();
    lookupCode(decodedText);
}

async function startCamera() {
    const statusEl = document.getElementById('cameraStatus');
    html5QrCode = new Html5Qrcode('qr-reader', {
        formatsToSupport: [
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.UPC_A,
            Html5QrcodeSupportedFormats.UPC_E,
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.QR_CODE,
        ],
        verbose: false,
    });

    try {
        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 15,
                // Wide, short box: matches a barcode's aspect ratio far better
                // than a square box, which is what most low-res misreads come from.
                qrbox: (viewfinderWidth, viewfinderHeight) => {
                    const width = Math.min(viewfinderWidth * 0.85, 380);
                    return { width, height: Math.round(width * 0.4) };
                },
                aspectRatio: 1.777,
            },
            handleDecodedCode
        );
        statusEl.textContent = '● Camera live';
        statusEl.style.color = 'var(--success)';
    } catch (err) {
        statusEl.textContent = 'Camera unavailable — use manual entry below';
        statusEl.style.color = 'var(--danger)';
    }
}

// Auto-start on page load — no external scanner assumed.
startCamera();

// ---- Scanner mode switch: Camera / Hardware / Manual ----
// The USB & Bluetooth hardware-scanner case and the manual-typing case
// share the exact same #scanInput + Enter-to-submit flow deliberately —
// a USB/Bluetooth barcode scanner IS a keyboard as far as the browser
// is concerned (it types the code, then sends Enter). There is nothing
// scanner-specific to "wire up" beyond what already exists: keep the
// field focused, wait for Enter, send ONE request for the completed
// code. What differs between the three modes is only whether the
// camera is running, since that's the one thing that should never run
// unless the operator explicitly chose Camera mode.
const qrReaderBox = document.getElementById('qr-reader');
const scanHint = document.getElementById('scanHint');
const cameraStatusEl = document.getElementById('cameraStatus');
const modeButtons = document.querySelectorAll('.scan-mode-btn');

const HINTS = {
    camera: 'Hold the barcode flat, fill the box, and keep it steady for a second — a 720p webcam needs good light and a close, still shot to read 1D barcodes reliably.',
    hardware: 'Ready for scan — the field below is focused and waiting. Most USB and Bluetooth scanners work exactly like a keyboard: they type the code, then send Enter automatically.',
    manual: 'Type or paste the barcode/SKU and press Enter, or use the Add button.',
};

async function stopCamera() {
    if (html5QrCode) {
        try { await html5QrCode.stop(); } catch (e) { /* not currently running */ }
    }
    cameraStatusEl.textContent = '';
}

function setMode(mode) {
    modeButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.mode === mode));
    scanHint.textContent = HINTS[mode];

    if (mode === 'camera') {
        qrReaderBox.style.display = 'block';
        cameraStatusEl.textContent = 'Starting camera…';
        startCamera();
    } else {
        qrReaderBox.style.display = 'none';
        stopCamera();
        scanInput.focus();
    }
}

modeButtons.forEach(btn => btn.addEventListener('click', () => setMode(btn.dataset.mode)));

// Stop the camera the moment the tab is hidden/backgrounded too — never
// leave it running when the operator isn't looking at the POS screen.
document.addEventListener('visibilitychange', () => {
    if (document.hidden) stopCamera();
    else if (document.querySelector('.scan-mode-btn[data-mode="camera"]').classList.contains('active')) startCamera();
});

// ---- Checkout ----
document.getElementById('checkoutBtn').addEventListener('click', async () => {
    if (cart.length === 0) {
        document.getElementById('checkoutError').textContent = 'Add at least one product before checking out.';
        return;
    }
    const payload = cart.map(i => ({ product_id: i.product_id, quantity: i.quantity }));
    const body = new URLSearchParams({
        cart: JSON.stringify(payload),
        customer_id: document.getElementById('customerSelect').value,
        payment_method: document.getElementById('paymentMethod').value,
        discount_amount: document.getElementById('discountInput').value || 0,
        whatsapp_number: document.getElementById('whatsappInput').value.trim(),
        _csrf: CSRF,
    });

    const res = await fetch(CHECKOUT_URL, { method: 'POST', body });
    const data = await res.json();

    if (data.success) {
        if (data.whatsapp) {
            const note = data.whatsapp.success
                ? '✅ Invoice sent to WhatsApp.'
                : `⚠️ ${data.whatsapp.message}`;
            sessionStorage.setItem('whatsappNotice', note);
        }
        window.location.href = data.redirect;
    } else {
        document.getElementById('checkoutError').textContent = data.message || 'Checkout failed.';
    }
});
</script>
