<?php

namespace App\Models;

use App\Core\Model;
use PDOException;
use RuntimeException;

class Sale extends Model
{
    protected string $table = 'sales';

    /**
     * Runs the entire checkout as one DB transaction:
     *   1. lock/validate stock per line
     *   2. insert sales header
     *   3. insert sale_items
     *   4. decrement product stock
     * Rolls back completely on any failure (e.g. concurrent oversell).
     *
     * @param array $cart  [['product_id'=>, 'quantity'=>, 'unit_price'=>, 'gst_percent'=>], ...]
     * @return int  new sale id
     */
    public function checkout(array $cart, array $header): int
    {
        $productModel = new Product();

        $this->db->beginTransaction();
        try {
            $subtotal = 0.0;
            $gstTotal = 0.0;

            foreach ($cart as $line) {
                $lineBase = $line['unit_price'] * $line['quantity'];
                $lineGst = $lineBase * ($line['gst_percent'] / 100);
                $subtotal += $lineBase;
                $gstTotal += $lineGst;
            }

            $discount = $header['discount_amount'] ?? 0.0;
            $total = round($subtotal + $gstTotal - $discount, 2);

            $invoiceNo = $this->generateInvoiceNumber();

            $saleId = $this->create([
                'invoice_no'      => $invoiceNo,
                'customer_id'     => $header['customer_id'] ?: null,
                'user_id'         => $header['user_id'],
                'subtotal'        => round($subtotal, 2),
                'gst_amount'      => round($gstTotal, 2),
                'discount_amount' => round((float) $discount, 2),
                'total_amount'    => $total,
                'payment_method'  => $header['payment_method'],
                'payment_status'  => 'paid',
            ]);

            $itemStmt = $this->db->prepare(
                "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, gst_percent, line_total)
                 VALUES (:sale_id, :product_id, :quantity, :unit_price, :gst_percent, :line_total)"
            );

            foreach ($cart as $line) {
                $lineBase = $line['unit_price'] * $line['quantity'];
                $lineGst = $lineBase * ($line['gst_percent'] / 100);
                $lineTotal = round($lineBase + $lineGst, 2);

                $itemStmt->execute([
                    'sale_id'     => $saleId,
                    'product_id'  => $line['product_id'],
                    'quantity'    => $line['quantity'],
                    'unit_price'  => $line['unit_price'],
                    'gst_percent' => $line['gst_percent'],
                    'line_total'  => $lineTotal,
                ]);

                $ok = $productModel->decrementStock($line['product_id'], $line['quantity']);
                if (!$ok) {
                    throw new RuntimeException(
                        "Insufficient stock for product ID {$line['product_id']}."
                    );
                }
            }

            $this->db->commit();
            return $saleId;
        } catch (PDOException|RuntimeException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getWithItems(int $saleId): ?array
    {
        $sale = $this->find($saleId);
        if (!$sale) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT si.*, p.name AS product_name, p.sku, p.unit
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id"
        );
        $stmt->execute(['sale_id' => $saleId]);
        $sale['items'] = $stmt->fetchAll();

        $customerStmt = $this->db->prepare("SELECT * FROM customers WHERE id = :id");
        $customerStmt->execute(['id' => $sale['customer_id']]);
        $sale['customer'] = $customerStmt->fetch() ?: null;

        $userStmt = $this->db->prepare("SELECT name FROM users WHERE id = :id");
        $userStmt->execute(['id' => $sale['user_id']]);
        $sale['cashier_name'] = $userStmt->fetch()['name'] ?? '—';

        return $sale;
    }

    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, COALESCE(c.name, 'Walk-in Customer') AS customer_name, u.name AS cashier_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             JOIN users u ON u.id = s.user_id
             ORDER BY s.sale_date DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = config('INVOICE_PREFIX', 'SP');
        $year = date('Y');

        // Count existing sales this year to build a sequential, zero-padded number.
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS cnt FROM sales WHERE YEAR(sale_date) = :year"
        );
        $stmt->execute(['year' => $year]);
        $seq = ((int) $stmt->fetch()['cnt']) + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $seq);
    }
}
