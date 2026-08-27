<?php

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Controller;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    private Product $model;
    private Category $categoryModel;

    public function __construct()
    {
        $this->model = new Product();
        $this->categoryModel = new Category();
    }

    public function index(): void
    {
        $this->view('products.index', [
            'pageTitle' => 'Products',
            'products' => $this->model->allWithCategory(),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function create(): void
    {
        $this->view('products.form', [
            'pageTitle' => 'Add Product',
            'categories' => $this->categoryModel->all('name ASC'),
            'product' => null,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->collectInput();

        if ($error = $this->validate($data)) {
            $this->flash('error', $error);
            $this->redirect('/products/create');
        }

        try {
            $newId = $this->model->create($data);
        } catch (\PDOException $e) {
            $this->flash('error', $this->friendlyDbError($e));
            $this->redirect('/products/create');
        }

        AuditLogger::log('PRODUCT_CREATED', 'products', 'product', $newId, null, $data);
        $this->flash('success', 'Product added.');
        $this->redirect('/products');
    }

    public function edit(string $id): void
    {
        $product = $this->model->find((int) $id);
        if (!$product) {
            http_response_code(404);
            die('Product not found.');
        }

        $this->view('products.form', [
            'pageTitle' => 'Edit Product',
            'categories' => $this->categoryModel->all('name ASC'),
            'product' => $product,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $data = $this->collectInput();
        $before = $this->model->find((int) $id);

        if ($error = $this->validate($data)) {
            $this->flash('error', $error);
            $this->redirect("/products/{$id}/edit");
        }

        try {
            $this->model->update((int) $id, $data);
        } catch (\PDOException $e) {
            $this->flash('error', $this->friendlyDbError($e));
            $this->redirect("/products/{$id}/edit");
        }

        AuditLogger::log('PRODUCT_UPDATED', 'products', 'product', (int) $id, $before, $data);
        $this->flash('success', 'Product updated.');
        $this->redirect('/products');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $before = $this->model->find((int) $id);
        try {
            $this->model->delete((int) $id);
            AuditLogger::log('PRODUCT_DELETED', 'products', 'product', (int) $id, $before, null);
            $this->flash('success', 'Product deleted.');
        } catch (\PDOException $e) {
            $this->flash('error', 'Cannot delete — this product has sales or purchase history. Mark it inactive instead.');
        }
        $this->redirect('/products');
    }

    private function collectInput(): array
    {
        return [
            'category_id'         => (int) $this->input('category_id'),
            'name'                => trim((string) $this->input('name')),
            'sku'                 => trim((string) $this->input('sku')),
            'barcode'             => trim((string) $this->input('barcode')),
            'unit'                => trim((string) $this->input('unit', 'pcs')),
            'cost_price'          => (float) $this->input('cost_price', 0),
            'selling_price'       => (float) $this->input('selling_price', 0),
            'gst_percent'         => (float) $this->input('gst_percent', 0),
            'stock_qty'           => (int) $this->input('stock_qty', 0),
            'low_stock_threshold' => (int) $this->input('low_stock_threshold', 5),
            'status'              => $this->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    private function validate(array $data): ?string
    {
        if ($data['name'] === '' || $data['sku'] === '' || $data['barcode'] === '') {
            return 'Name, SKU, and barcode are all required.';
        }
        if ($data['category_id'] <= 0) {
            return 'Please select a category.';
        }
        if ($data['cost_price'] < 0 || $data['selling_price'] < 0) {
            return 'Prices cannot be negative.';
        }
        return null;
    }

    private function friendlyDbError(\PDOException $e): string
    {
        if (str_contains($e->getMessage(), 'uq_products_sku')) {
            return 'That SKU is already in use by another product.';
        }
        if (str_contains($e->getMessage(), 'uq_products_barcode')) {
            return 'That barcode is already in use by another product.';
        }
        return 'Could not save product. Please check the values and try again.';
    }

}
