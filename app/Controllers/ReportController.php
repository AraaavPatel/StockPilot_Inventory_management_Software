<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ReportController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();

        $from = $this->input('from') ?: date('Y-m-d', strtotime('-29 days'));
        $to = $this->input('to') ?: date('Y-m-d');

        $stmt = $db->prepare(
            "SELECT DATE(sale_date) AS d, COUNT(*) AS bills, COALESCE(SUM(total_amount),0) AS total
             FROM sales
             WHERE DATE(sale_date) BETWEEN :from AND :to
             GROUP BY DATE(sale_date) ORDER BY d ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        $daily = $stmt->fetchAll();

        $topProducts = $db->prepare(
            "SELECT p.name, SUM(si.quantity) AS qty_sold, SUM(si.line_total) AS revenue
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN products p ON p.id = si.product_id
             WHERE DATE(s.sale_date) BETWEEN :from AND :to
             GROUP BY si.product_id ORDER BY qty_sold DESC LIMIT 10"
        );
        $topProducts->execute(['from' => $from, 'to' => $to]);

        $lowStock = $db->query(
            "SELECT name, sku, stock_qty, low_stock_threshold FROM products
             WHERE stock_qty <= low_stock_threshold AND status = 'active'
             ORDER BY stock_qty ASC"
        )->fetchAll();

        $this->view('reports.index', [
            'pageTitle' => 'Reports',
            'from' => $from,
            'to' => $to,
            'daily' => $daily,
            'topProducts' => $topProducts->fetchAll(),
            'lowStock' => $lowStock,
        ]);
    }

    /**
     * CSV export (not XLSX) — avoids pulling PhpSpreadsheet into a
     * simple tabular dump and keeps memory flat on shared hosting for
     * a large date range. Every value is written through fputcsv,
     * which handles quoting/escaping — no manual string concatenation.
     */
    public function export(): void
    {
        $db = Database::connection();
        $from = $this->input('from') ?: date('Y-m-d', strtotime('-29 days'));
        $to = $this->input('to') ?: date('Y-m-d');

        $stmt = $db->prepare(
            "SELECT s.invoice_no, s.sale_date, u.name AS cashier,
                    COALESCE(c.name,'Walk-in') AS customer,
                    s.subtotal, s.gst_amount, s.discount_amount, s.total_amount, s.payment_method
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE DATE(s.sale_date) BETWEEN :from AND :to
             ORDER BY s.sale_date ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="sales-' . $from . '-to-' . $to . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Invoice No', 'Date', 'Cashier', 'Customer', 'Subtotal', 'GST', 'Discount', 'Total', 'Payment Method']);
        while ($row = $stmt->fetch()) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
