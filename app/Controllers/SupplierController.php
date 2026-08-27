<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Supplier;

class SupplierController extends Controller
{
    private Supplier $model;

    public function __construct()
    {
        $this->model = new Supplier();
    }

    public function index(): void
    {
        $this->view('suppliers.index', [
            'pageTitle' => 'Suppliers',
            'suppliers' => $this->model->all('name ASC'),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $name = trim((string) $this->input('name'));
        if ($name === '') {
            $this->flash('error', 'Supplier name is required.');
            $this->redirect('/suppliers');
        }

        $this->model->create([
            'name' => $name,
            'contact_person' => trim((string) $this->input('contact_person')) ?: null,
            'phone' => trim((string) $this->input('phone')) ?: null,
            'email' => trim((string) $this->input('email')) ?: null,
            'address' => trim((string) $this->input('address')) ?: null,
            'gstin' => trim((string) $this->input('gstin')) ?: null,
        ]);

        $this->flash('success', 'Supplier added.');
        $this->redirect('/suppliers');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        try {
            $this->model->delete((int) $id);
            $this->flash('success', 'Supplier deleted.');
        } catch (\PDOException $e) {
            $this->flash('error', 'Cannot delete — this supplier has purchase records linked to it.');
        }
        $this->redirect('/suppliers');
    }

}
