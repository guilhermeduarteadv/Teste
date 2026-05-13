<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class LegalConsultancy extends Model
{
    protected $table = 'legal_consultancies';

    public function findAllPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "lc.deleted_at IS NULL";

        if (!empty($filters['status'])) {
            $where .= " AND lc.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['client_id'])) {
            $where .= " AND lc.client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where .= " AND (lc.titulo LIKE ? OR lc.area LIKE ? OR lc.tipo_consultoria LIKE ? OR lc.descricao LIKE ? OR cl.name LIKE ?)";
            array_push($params, $like, $like, $like, $like, $like);
        }

        $count = $this->queryOne("SELECT COUNT(*) AS total FROM legal_consultancies lc LEFT JOIN clients cl ON cl.id = lc.client_id WHERE {$where}", $params);
        $total = (int)($count['total'] ?? 0);

        $items = $this->query(
            "SELECT lc.*, cl.name AS client_name, u.name AS responsavel_name
             FROM legal_consultancies lc
             LEFT JOIN clients cl ON cl.id = lc.client_id
             LEFT JOIN users u ON u.id = lc.responsavel_id
             WHERE {$where}
             ORDER BY lc.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }
}
