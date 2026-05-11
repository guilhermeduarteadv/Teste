<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Document extends Model
{
    protected $table = 'documents';

    public function findForEntity(string $entityType, int $entityId, bool $visibleOnly = false): array
    {
        $sql = "SELECT d.*, u.name as uploaded_by_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.entity_type = ? AND d.entity_id = ? AND d.deleted_at IS NULL";
        if ($visibleOnly) $sql .= " AND d.visivel_cliente = 1";
        $sql .= " ORDER BY d.created_at DESC";
        return $this->query($sql, [$entityType, $entityId]);
    }

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 'd.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['entity_type'])) {
            $where .= ' AND d.entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['categoria'])) {
            $where .= ' AND d.categoria = ?';
            $params[] = $filters['categoria'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (d.descricao LIKE ? OR d.original_name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }

        $countSql = "SELECT COUNT(*) FROM documents d WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT d.*, u.name as uploaded_by_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE {$where} ORDER BY d.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return ['data' => $items, 'total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => (int)ceil($total / $perPage)];
    }

    public function getTotalSize(): int
    {
        return (int)($this->db->query("SELECT SUM(size) FROM documents WHERE deleted_at IS NULL")->fetchColumn() ?? 0);
    }
}
