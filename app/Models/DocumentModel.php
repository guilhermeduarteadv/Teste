<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';

    public function getByEntity(string $entityType, int $entityId, bool $visibleOnly = false): array
    {
        $sql = "SELECT d.*, u.name AS uploaded_by_name
                FROM documents d
                LEFT JOIN users u ON d.uploaded_by = u.id
                WHERE d.entity_type = ? AND d.entity_id = ? AND d.deleted_at IS NULL";
        $params = [$entityType, $entityId];
        if ($visibleOnly) {
            $sql .= " AND d.visivel_cliente = 1";
        }
        $sql .= " ORDER BY d.created_at DESC";
        return $this->query($sql, $params);
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "d.deleted_at IS NULL";

        if (!empty($filters['entity_type'])) {
            $where .= " AND d.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        if (!empty($filters['categoria'])) {
            $where .= " AND d.categoria = ?";
            $params[] = $filters['categoria'];
        }
        if (!empty($filters['term'])) {
            $like = "%{$filters['term']}%";
            $where .= " AND (d.descricao LIKE ? OR d.original_name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }

        $countRow = $this->queryOne("SELECT COUNT(*) AS cnt FROM documents d WHERE {$where}", $params);
        $total = (int)($countRow['cnt'] ?? 0);

        $items = $this->query(
            "SELECT d.*, u.name AS uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE {$where}
             ORDER BY d.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->queryOne(
            "SELECT d.*, u.name AS uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.id = ? AND d.deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }

    public function getTotalSize(): int
    {
        $row = $this->queryOne("SELECT SUM(size) AS total FROM documents WHERE deleted_at IS NULL");
        return (int)($row['total'] ?? 0);
    }

    public function getCountByCategory(): array
    {
        return $this->query(
            "SELECT categoria, COUNT(*) AS cnt FROM documents WHERE deleted_at IS NULL GROUP BY categoria ORDER BY cnt DESC"
        );
    }
}
