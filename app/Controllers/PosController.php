<?php

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\WhatsAppService;
use RuntimeException;

class PosController extends Controller
{
    private Product $productModel;
    private Sale $saleModel;
    private Customer $customerModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->saleModel = new Sale();
        $this->customerModel = new Customer();
    }

    public function index(): void
    {
        $customers = $this->customerModel->all('name ASC');

        $this->view('pos.index', [
            'pageTitle' => 'POS Billing',
            'customers' => $customers,
            'csrf' => $this->csrfToken(),
        ]);
    }

    /**
     * AJAX: look up a product by scanned barcode or typed SKU/name.
     * Returns JSON so the POS screen can add it to the cart instantly.
     */
    public function lookup(): void
    {
        $code = trim((string) $this->input('code'));

        if ($code === '') {
            $this->json(['found' => false, 'message' => 'Empty code.'], 422);
        }

        $product = $this->productModel->findByBarcode($code) ?? $this->productModel->findBySku($code);

        if (!$product) {
            // fall back to a fuzzy search so partial typing still helps
            $matches = $this->productModel->search($code);
            $this->json(['found' => false, 'suggestions' => $matches]);
        }

        if ($product['status'] !== 'active') {
            $this->json(['found' => false, 'message' => 'This product is inactive.'], 422);
        }

        if ($product['stock_qty'] <= 0) {
            $this->json(['found' => false, 'message' => "{$product['name']} is out of stock."], 422);
        }

        $this->json(['found' => true, 'product' => $product]);
    }

    /**
     * Finalize the sale: validate stock, persist sale + items,
     * decrement inventory — all inside one DB transaction (Sale::checkout).
     */
    public function checkout(): void
    {
        $this->verifyCsrf();

        $cartJson = $this->input('cart');
        $cart = json_decode((string) $cartJson, true);

        if (!is_array($cart) || empty($cart)) {
            $this->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }

        // Re-fetch authoritative price/stock/gst from DB — never trust client-sent prices.
        $verifiedCart = [];
        foreach ($cart as $line) {
            $product = $this->productModel->find((int) $line['product_id']);
            $qty = max(1, (int) $line['quantity']);

            if (!$product || $product['status'] !== 'active') {
                $this->json(['success' => false, 'message' => 'A product in your cart is no longer available.'], 422);
            }
            if ($product['stock_qty'] < $qty) {
                $this->json(['success' => false, 'message' => "Insufficient stock for {$product['name']} (only {$product['stock_qty']} left)."], 422);
            }

            $verifiedCart[] = [
                'product_id'  => $product['id'],
                'quantity'    => $qty,
                'unit_price'  => (float) $product['selling_price'],
                'gst_percent' => (float) $product['gst_percent'],
            ];
        }

        try {
            $saleId = $this->saleModel->checkout($verifiedCart, [
                'customer_id'     => (int) $this->input('customer_id', 0) ?: null,
                'user_id'         => Auth::id(),
                'discount_amount' => (float) $this->input('discount_amount', 0),
                'payment_method'  => $this->input('payment_method', 'cash'),
            ]);
        } catch (RuntimeException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 409);
        }

        AuditLogger::log('SALE_CREATED', 'pos', 'sale', $saleId, null, ['total_lines' => count($verifiedCart)]);

        // Auto-send the invoice on WhatsApp the moment payment is confirmed —
        // no manual "share" step. Never blocks checkout if it fails/isn't configured.
        $whatsappNumber = trim((string) $this->input('whatsapp_number', ''));
        $whatsappResult = null;

        if ($whatsappNumber !== '') {
            $sale = $this->saleModel->find($saleId);
            $customerName = $this->input('customer_id')
                ? ($this->customerModel->find((int) $this->input('customer_id'))['name'] ?? 'Customer')
                : 'Customer';

            $whatsappResult = (new WhatsAppService())->sendInvoiceNotification(
                $whatsappNumber,
                $customerName,
                (float) $sale['total_amount'],
                config('STORE_NAME', 'StockPilot Store'),
                base_url("/pos/invoice/{$saleId}/pdf")
            );
        }

        $this->json([
            'success' => true,
            'sale_id' => $saleId,
            'redirect' => base_url("/pos/invoice/{$saleId}"),
            'whatsapp' => $whatsappResult,
        ]);
    }

    public function invoice(string $id): void
    {
        $sale = $this->saleModel->getWithItems((int) $id);
        if (!$sale) {
            http_response_code(404);
            die('Invoice not found.');
        }

        $this->viewOnly('pos.invoice', [
            'sale' => $sale,
            'store' => $this->storeDetails(),
            'downloadUrl' => base_url("/pos/invoice/{$id}/pdf"),
        ]);
    }

    public function invoicePdf(string $id): void
    {
        $sale = $this->saleModel->getWithItems((int) $id);
        if (!$sale) {
            http_response_code(404);
            die('Invoice not found.');
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        ob_start();
        $this->viewOnly('pos.invoice_pdf', ['sale' => $sale, 'store' => $this->storeDetails()]);
        $html = ob_get_clean();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();
        $dompdf->stream("invoice-{$sale['invoice_no']}.pdf", ['Attachment' => true]);
        exit;
    }

    private function storeDetails(): array
    {
        return [
            'name'    => config('STORE_NAME', 'StockPilot Store'),
            'address' => config('STORE_ADDRESS', ''),
            'gstin'   => config('STORE_GSTIN', ''),
        ];
    }

}
