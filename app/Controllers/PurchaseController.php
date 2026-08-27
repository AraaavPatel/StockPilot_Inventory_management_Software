<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;

class PurchaseController extends Controller
{
    private Purchase $model;
    private Supplier $supplierModel;
    private Product $productModel;

    public function __construct()
    {
        $this->model = new Purchase();
        $this->supplierModel = new Supplier();
        $this->productModel = new Product();
    }

    public function index(): void
    {
        $this->view('purchases.index', [
            'pageTitle' => 'Purchases',
            'purchases' => $this->model->allWithSupplier(),
        ]);
    }

    public function create(): void
    {
        $this->view('purchases.form', [
            'pageTitle' => 'Record Purchase',
            'suppliers' => $this->supplierModel->all('name ASC'),
            'products' => $this->productModel->all('name ASC'),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $supplierId = (int) $this->input('supplier_id');
        $itemsJson = $this->input('items');
        $items = json_decode((string) $itemsJson, true);

        if ($supplierId <= 0 || !is_array($items) || empty($items)) {
            $this->flash('error', 'Select a supplier and add at least one product line.');
            $this->redirect('/purchases/create');
        }

        $cleanItems = [];
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $cost = (float) ($item['unit_cost'] ?? 0);
            if ($qty <= 0 || $cost < 0 || empty($item['product_id'])) {
                continue;
            }
            $cleanItems[] = [
                'product_id' => (int) $item['product_id'],
                'quantity' => $qty,
                'unit_cost' => $cost,
            ];
        }

        if (empty($cleanItems)) {
            $this->flash('error', 'No valid product lines found.');
            $this->redirect('/purchases/create');
        }

        $this->model->recordPurchase([
            'supplier_id' => $supplierId,
            'user_id' => Auth::id(),
            'invoice_no' => trim((string) $this->input('invoice_no')),
            'purchase_date' => $this->input('purchase_date', date('Y-m-d')),
            'notes' => trim((string) $this->input('notes')),
        ], $cleanItems);

        $this->flash('success', 'Purchase recorded and stock updated.');
        $this->redirect('/purchases');
    }

}
