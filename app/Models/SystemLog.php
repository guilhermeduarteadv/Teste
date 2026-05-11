<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    public function findPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $where .= ' AND sl.user_id = ?';
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where .= ' AND sl.action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['module'])) {
            $where .= ' AND sl.module = ?';
            $params[] = $filters['module'];
        }
        if (!empty($filters['data_inicio'])) {
            $where .= ' AND DATE(sl.created_at) >= ?';
            $params[] = $filters['data_inicio'];
        }
        if (!empty($filters['data_fim'])) {
            $where .= ' AND DATE(sl.created_at) <= ?';
            $params[] = $filters['data_fim'];
        }

        $countSql = "SELECT COUNT(*) FROM system_logs sl WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT sl.*, u.name as user_name FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id WHERE {$where} ORDER BY sl.created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return ['data' => $items, 'total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => (int)ceil($total / $perPage)];
    }
}
