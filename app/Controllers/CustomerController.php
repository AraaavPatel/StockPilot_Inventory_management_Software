<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    private Customer $model;

    public function __construct()
    {
        $this->model = new Customer();
    }

    public function index(): void
    {
        $this->view('customers.index', [
            'pageTitle' => 'Customers',
            'customers' => $this->model->all('name ASC'),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $name = trim((string) $this->input('name')) ?: 'Walk-in Customer';
        $phone = trim((string) $this->input('phone')) ?: null;

        // AJAX quick-add from the POS screen: return JSON, don't redirect.
        if ($this->input('ajax') === '1') {
            if ($phone) {
                $existing = $this->model->findByPhone($phone);
                if ($existing) {
                    $this->json(['success' => true, 'customer' => $existing, 'existed' => true]);
                }
            }
            try {
                $id = $this->model->create(['name' => $name, 'phone' => $phone]);
                $this->json(['success' => true, 'customer' => $this->model->find($id), 'existed' => false]);
            } catch (\PDOException $e) {
                $this->json(['success' => false, 'message' => 'That phone number is already saved to another customer.'], 422);
            }
        }

        $this->model->create([
            'name' => $name,
            'phone' => $phone,
            'email' => trim((string) $this->input('email')) ?: null,
            'address' => trim((string) $this->input('address')) ?: null,
        ]);

        $this->flash('success', 'Customer added.');
        $this->redirect('/customers');
    }

}
