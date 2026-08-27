<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminOnly;
use App\Middleware\AdminOrManager;
use App\Middleware\AnyRole;

/** @var Router $router */

// ---- Guest routes ----
$router->get('/', 'AuthController@showLogin');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// ---- Authenticated: any role ----
$router->get('/dashboard', 'DashboardController@index', [AuthMiddleware::class]);

// POS billing — flagship module (Phase 2)
$router->get('/pos', 'PosController@index', [AnyRole::class]);
$router->post('/pos/lookup', 'PosController@lookup', [AnyRole::class]);
$router->post('/pos/checkout', 'PosController@checkout', [AnyRole::class]);
$router->get('/pos/invoice/{id}', 'PosController@invoice', [AnyRole::class]);
$router->get('/pos/invoice/{id}/pdf', 'PosController@invoicePdf', [AnyRole::class]);

// Sales history / customers — any role can view, cashier restricted in controller if needed
$router->get('/sales', 'SaleController@index', [AnyRole::class]);
$router->get('/customers', 'CustomerController@index', [AnyRole::class]);
$router->post('/customers', 'CustomerController@store', [AnyRole::class]);

// ---- Catalog: admin + manager manage, everyone can view ----
$router->get('/products', 'ProductController@index', [AnyRole::class]);
$router->get('/categories', 'CategoryController@index', [AnyRole::class]);

$router->get('/products/create', 'ProductController@create', [AdminOrManager::class]);
$router->post('/products', 'ProductController@store', [AdminOrManager::class]);
$router->get('/products/{id}/edit', 'ProductController@edit', [AdminOrManager::class]);
$router->post('/products/{id}', 'ProductController@update', [AdminOrManager::class]);
$router->post('/products/{id}/delete', 'ProductController@destroy', [AdminOrManager::class]);

$router->post('/categories', 'CategoryController@store', [AdminOrManager::class]);
$router->post('/categories/{id}/delete', 'CategoryController@destroy', [AdminOrManager::class]);

// ---- Procurement: admin + manager only ----
$router->get('/suppliers', 'SupplierController@index', [AdminOrManager::class]);
$router->post('/suppliers', 'SupplierController@store', [AdminOrManager::class]);
$router->post('/suppliers/{id}/delete', 'SupplierController@destroy', [AdminOrManager::class]);

$router->get('/purchases', 'PurchaseController@index', [AdminOrManager::class]);
$router->get('/purchases/create', 'PurchaseController@create', [AdminOrManager::class]);
$router->post('/purchases', 'PurchaseController@store', [AdminOrManager::class]);

// ---- Reports & stock adjustments: admin + manager ----
$router->get('/reports', 'ReportController@index', [AdminOrManager::class]);
$router->get('/reports/export', 'ReportController@export', [AdminOrManager::class]);
$router->get('/stock-adjustments', 'StockAdjustmentController@index', [AdminOrManager::class]);
$router->post('/stock-adjustments', 'StockAdjustmentController@store', [AdminOrManager::class]);

// ---- User management: admin only ----
$router->get('/users', 'UserController@index', [AdminOnly::class]);
$router->post('/users', 'UserController@store', [AdminOnly::class]);
$router->post('/users/{id}/update', 'UserController@update', [AdminOnly::class]);

// ---- Security & audit: admin only, read-only ----
$router->get('/audit-logs', 'AuditController@index', [AdminOnly::class]);
