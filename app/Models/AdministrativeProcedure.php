<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;
use PDO;

class AdministrativeProcedure extends Model
{
    protected $table = 'administrative_procedures';

    public function findAllPaginated(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "ap.deleted_at IS NULL";

        if (!empty($filters['status'])) {
            $where .= " AND ap.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['client_id'])) {
            $where .= " AND ap.client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where .= " AND (ap.titulo LIKE ? OR ap.numero_processo LIKE ? OR ap.prefeitura LIKE ? OR ap.assunto LIKE ? OR cl.name LIKE ?)";
            array_push($params, $like, $like, $like, $like, $like);
        }

        $count = $this->queryOne("SELECT COUNT(*) AS total FROM administrative_procedures ap LEFT JOIN clients cl ON cl.id = ap.client_id WHERE {$where}", $params);
        $total = (int)($count['total'] ?? 0);

        $items = $this->query(
            "SELECT ap.*, cl.name AS client_name, u.name AS responsavel_name
             FROM administrative_procedures ap
             LEFT JOIN clients cl ON cl.id = ap.client_id
             LEFT JOIN users u ON u.id = ap.responsavel_id
             WHERE {$where}
             ORDER BY ap.created_at DESC
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
