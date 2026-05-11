<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Hearing extends Model
{
    protected $table = 'case_hearings';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 'h.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['status'])) {
            $where .= ' AND h.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND h.tipo = ?';
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['data_inicio'])) {
            $where .= ' AND h.data >= ?';
            $params[] = $filters['data_inicio'];
        }
        if (!empty($filters['data_fim'])) {
            $where .= ' AND h.data <= ?';
            $params[] = $filters['data_fim'];
        }

        $countSql = "SELECT COUNT(*) FROM case_hearings h WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT h.*, c.numero_cnj, cl.name as client_name FROM case_hearings h LEFT JOIN cases c ON h.case_id = c.id LEFT JOIN clients cl ON h.client_id = cl.id WHERE {$where} ORDER BY h.data ASC, h.hora ASC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return ['data' => $items, 'total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => (int)ceil($total / $perPage)];
    }

    public function getCalendarEvents(string $startDate, string $endDate): array
    {
        return $this->query(
            "SELECT h.*, c.numero_cnj FROM case_hearings h LEFT JOIN cases c ON h.case_id = c.id WHERE h.data BETWEEN ? AND ? AND h.deleted_at IS NULL ORDER BY h.data ASC, h.hora ASC",
            [$startDate, $endDate]
        );
    }

    public function getTodayHearings(): array
    {
        return $this->query(
            "SELECT h.*, c.numero_cnj, cl.name as client_name FROM case_hearings h LEFT JOIN cases c ON h.case_id = c.id LEFT JOIN clients cl ON h.client_id = cl.id WHERE h.data = CURDATE() AND h.status = 'agendado' AND h.deleted_at IS NULL ORDER BY h.hora ASC"
        );
    }
}
