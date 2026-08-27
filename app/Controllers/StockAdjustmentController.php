<?php

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Auth;
use App\Core\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use RuntimeException;

class StockAdjustmentController extends Controller
{
    private StockAdjustment $model;
    private Product $productModel;

    public function __construct()
    {
        $this->model = new StockAdjustment();
        $this->productModel = new Product();
    }

    public function index(): void
    {
        $this->view('stock_adjustments.index', [
            'pageTitle' => 'Stock Adjustments',
            'adjustments' => $this->model->recentWithDetails(),
            'products' => $this->productModel->all('name ASC'),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $productId = (int) $this->input('product_id');
        $type = $this->input('adjustment_type') === 'remove' ? 'remove' : 'add';
        $qty = (int) $this->input('quantity', 0);
        $reason = trim((string) $this->input('reason'));

        $product = $this->productModel->find($productId);

        if (!$product || $qty <= 0 || $reason === '') {
            $this->flash('error', 'Select a product, a positive quantity, and a reason.');
            $this->redirect('/stock-adjustments');
        }

        try {
            $before = $product['stock_qty'];
            $this->model->record($productId, (int) Auth::id(), $type, $qty, $reason);
            $after = $this->productModel->find($productId)['stock_qty'];

            AuditLogger::log(
                'STOCK_ADJUSTED',
                'inventory',
                'product',
                $productId,
                ['stock_qty' => $before],
                ['stock_qty' => $after, 'type' => $type, 'quantity' => $qty, 'reason' => $reason]
            );

            $this->flash('success', 'Stock adjusted.');
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('/stock-adjustments');
    }
}
