<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class SystemLogModel extends Model
{
    protected $table = 'system_logs';

    public function log(
        ?int $userId,
        string $action,
        string $module,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $oldData = null,
        ?array $newData = null
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO system_logs (user_id, action, module, entity_type, entity_id, description, old_data, new_data, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $userId,
            $action,
            $module,
            $entityType,
            $entityId,
            $description,
            $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
            $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getRecent(int $limit = 50, array $filters = []): array
    {
        $where = "1=1";
        $params = [];

        if (!empty($filters['module'])) {
            $where .= " AND sl.module = ?";
            $params[] = $filters['module'];
        }
        if (!empty($filters['user_id'])) {
            $where .= " AND sl.user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where .= " AND sl.action LIKE ?";
            $params[] = "%{$filters['action']}%";
        }

        return $this->query(
            "SELECT sl.*, u.name AS user_name, u.email AS user_email
             FROM system_logs sl
             LEFT JOIN users u ON sl.user_id = u.id
             WHERE {$where}
             ORDER BY sl.created_at DESC
             LIMIT {$limit}",
            $params
        );
    }

    public function getPaginated(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];

        if (!empty($filters['module'])) {
            $where .= " AND sl.module = ?";
            $params[] = $filters['module'];
        }
        if (!empty($filters['user_id'])) {
            $where .= " AND sl.user_id = ?";
            $params[] = $filters['user_id'];
        }

        $countRow = $this->queryOne(
            "SELECT COUNT(*) AS cnt FROM system_logs sl WHERE {$where}",
            $params
        );
        $total = (int)($countRow['cnt'] ?? 0);

        $items = $this->query(
            "SELECT sl.*, u.name AS user_name
             FROM system_logs sl
             LEFT JOIN users u ON sl.user_id = u.id
             WHERE {$where}
             ORDER BY sl.created_at DESC
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
}
