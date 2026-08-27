<?php

namespace App\Core;

use PDO;

/**
 * Base Model
 *
 * Every model extends this and sets $table. Provides generic
 * CRUD helpers so simple models need almost no boilerplate,
 * while still allowing custom queries via $this->db.
 */
abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBy(string $column, $value): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1");
        $stmt->execute(['value' => $value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function where(string $column, $value, string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($c) => ":{$c}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $assignments = implode(', ', array_map(fn($c) => "{$c} = :{$c}", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$assignments} WHERE {$this->primaryKey} = :id";

        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(string $whereSql = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->table}";
        if ($whereSql) {
            $sql .= " WHERE {$whereSql}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'];
    }
}
