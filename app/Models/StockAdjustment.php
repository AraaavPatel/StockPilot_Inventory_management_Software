<?php

namespace App\Models;

use App\Core\Model;
use PDOException;
use RuntimeException;

class StockAdjustment extends Model
{
    protected string $table = 'stock_adjustments';

    /**
     * Applies a manual stock correction as one transaction: record the
     * adjustment row, then move stock_qty. 'remove' is guarded the same
     * way checkout is — it can never take stock below zero.
     */
    public function record(int $productId, int $userId, string $type, int $qty, string $reason): int
    {
        if (!in_array($type, ['add', 'remove'], true) || $qty <= 0) {
            throw new RuntimeException('Invalid adjustment.');
        }

        $productModel = new Product();
        $this->db->beginTransaction();
        try {
            $id = $this->create([
                'product_id'      => $productId,
                'user_id'         => $userId,
                'adjustment_type' => $type,
                'quantity'        => $qty,
                'reason'          => $reason,
            ]);

            $ok = $type === 'add'
                ? $productModel->incrementStock($productId, $qty)
                : $productModel->decrementStock($productId, $qty);

            if (!$ok) {
                throw new RuntimeException('Cannot remove more stock than is currently on hand.');
            }

            $this->db->commit();
            return $id;
        } catch (PDOException|RuntimeException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function recentWithDetails(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT sa.*, p.name AS product_name, p.sku, u.name AS user_name
             FROM stock_adjustments sa
             JOIN products p ON p.id = sa.product_id
             JOIN users u ON u.id = sa.user_id
             ORDER BY sa.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
