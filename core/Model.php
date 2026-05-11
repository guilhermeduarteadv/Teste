<?php
declare(strict_types=1);

namespace Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAll(array $conditions = [], string $order = 'id DESC', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        $params = [];
        foreach ($conditions as $col => $val) {
            $sql .= " AND {$col} = ?";
            $params[] = $val;
        }
        $sql .= " ORDER BY {$order}";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NULL";
        $params = [];
        foreach ($conditions as $col => $val) {
            $sql .= " AND {$col} = ?";
            $params[] = $val;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $cols = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $cols = array_keys($data);
        $sets = implode(', ', array_map(function($col) { return "{$col} = ?"; }, $cols));
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?"
        );
        $params = array_values($data);
        $params[] = $id;
        return $stmt->execute($params);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET deleted_at = ?, updated_at = ? WHERE {$this->primaryKey} = ?"
        );
        return $stmt->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function paginate(int $page, int $perPage, array $conditions = [], string $order = 'id DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $items = $this->findAll($conditions, $order, $perPage, $offset);
        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
