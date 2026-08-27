<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    public function findByBarcode(string $barcode): ?array
    {
        return $this->findBy('barcode', $barcode);
    }

    public function findBySku(string $sku): ?array
    {
        return $this->findBy('sku', $sku);
    }

    public function allWithCategory(): array
    {
        $stmt = $this->db->query(
            "SELECT p.*, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             ORDER BY p.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function search(string $term): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products
             WHERE status = 'active' AND (name LIKE :t OR sku LIKE :t OR barcode LIKE :t)
             ORDER BY name ASC LIMIT 15"
        );
        $stmt->execute(['t' => "%{$term}%"]);
        return $stmt->fetchAll();
    }

    /**
     * Atomically decrement stock. Uses a WHERE guard so we never
     * oversell even under concurrent requests.
     */
    public function decrementStock(int $productId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock_qty = stock_qty - :qty
             WHERE id = :id AND stock_qty >= :qty2"
        );
        $stmt->execute(['qty' => $qty, 'id' => $productId, 'qty2' => $qty]);
        return $stmt->rowCount() === 1;
    }

    public function incrementStock(int $productId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET stock_qty = stock_qty + :qty WHERE id = :id"
        );
        return $stmt->execute(['qty' => $qty, 'id' => $productId]);
    }
}
