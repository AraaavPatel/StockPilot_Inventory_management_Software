<?php

namespace App\Models;

use App\Core\Model;
use PDOException;

class Purchase extends Model
{
    protected string $table = 'purchases';

    /**
     * Records a purchase (stock-in) as one transaction: header + line
     * items + incrementing each product's stock_qty.
     *
     * @param array $items [['product_id'=>, 'quantity'=>, 'unit_cost'=>], ...]
     */
    public function recordPurchase(array $header, array $items): int
    {
        $productModel = new Product();

        $this->db->beginTransaction();
        try {
            $total = 0.0;
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['unit_cost'];
            }

            $purchaseId = $this->create([
                'supplier_id'   => $header['supplier_id'],
                'user_id'       => $header['user_id'],
                'invoice_no'    => $header['invoice_no'] ?: null,
                'total_amount'  => round($total, 2),
                'purchase_date' => $header['purchase_date'],
                'notes'         => $header['notes'] ?: null,
            ]);

            $itemStmt = $this->db->prepare(
                "INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, line_total)
                 VALUES (:purchase_id, :product_id, :quantity, :unit_cost, :line_total)"
            );

            foreach ($items as $item) {
                $lineTotal = round($item['quantity'] * $item['unit_cost'], 2);
                $itemStmt->execute([
                    'purchase_id' => $purchaseId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'line_total'  => $lineTotal,
                ]);
                $productModel->incrementStock($item['product_id'], $item['quantity']);
            }

            $this->db->commit();
            return $purchaseId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function allWithSupplier(): array
    {
        $stmt = $this->db->query(
            "SELECT p.*, s.name AS supplier_name, u.name AS recorded_by
             FROM purchases p
             JOIN suppliers s ON s.id = p.supplier_id
             JOIN users u ON u.id = p.user_id
             ORDER BY p.purchase_date DESC, p.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function getWithItems(int $id): ?array
    {
        $purchase = $this->find($id);
        if (!$purchase) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT pi.*, p.name AS product_name FROM purchase_items pi
             JOIN products p ON p.id = pi.product_id WHERE pi.purchase_id = :id"
        );
        $stmt->execute(['id' => $id]);
        $purchase['items'] = $stmt->fetchAll();
        return $purchase;
    }
}
