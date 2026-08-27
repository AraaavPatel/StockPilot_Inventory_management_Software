<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();

        $todaySales = $db->query(
            "SELECT COALESCE(SUM(total_amount),0) AS total, COUNT(*) AS cnt
             FROM sales WHERE DATE(sale_date) = CURDATE()"
        )->fetch();

        $monthSales = $db->query(
            "SELECT COALESCE(SUM(total_amount),0) AS total
             FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())"
        )->fetch();

        $lowStock = $db->query(
            "SELECT id, name, stock_qty, low_stock_threshold FROM products
             WHERE stock_qty <= low_stock_threshold AND status = 'active'
             ORDER BY stock_qty ASC LIMIT 8"
        )->fetchAll();

        $lowStockCount = $db->query(
            "SELECT COUNT(*) AS cnt FROM products WHERE stock_qty <= low_stock_threshold AND status = 'active'"
        )->fetch()['cnt'];

        $productCount = $db->query("SELECT COUNT(*) AS cnt FROM products WHERE status='active'")->fetch()['cnt'];

        $recentSales = $db->query(
            "SELECT s.invoice_no, s.total_amount, s.sale_date, s.payment_method,
                    COALESCE(c.name, 'Walk-in Customer') AS customer_name, u.name AS cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             JOIN users u ON u.id = s.user_id
             ORDER BY s.sale_date DESC LIMIT 8"
        )->fetchAll();

        // Last 7 days sales trend for the chart
        $trend = $db->query(
            "SELECT DATE(sale_date) AS d, COALESCE(SUM(total_amount),0) AS total
             FROM sales
             WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(sale_date)
             ORDER BY d ASC"
        )->fetchAll();

        $this->view('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'todaySales' => $todaySales,
            'monthSales' => $monthSales,
            'lowStock' => $lowStock,
            'lowStockCount' => $lowStockCount,
            'productCount' => $productCount,
            'recentSales' => $recentSales,
            'trend' => $trend,
        ]);
    }
}
