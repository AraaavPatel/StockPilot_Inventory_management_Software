<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sale;

class SaleController extends Controller
{
    private Sale $model;

    public function __construct()
    {
        $this->model = new Sale();
    }

    public function index(): void
    {
        $this->view('sales.index', [
            'pageTitle' => 'Sales History',
            'sales' => $this->model->recent(100),
        ]);
    }
}
