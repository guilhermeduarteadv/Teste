<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class GenericModel extends Model
{
    public function __construct(string $table)
    {
        parent::__construct();
        $this->table = $table;
    }

    public function searchSimple(array $filters = [], string $order = 'created_at DESC', int $limit = 200): array
    {
        $where = "deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where .= " AND (title LIKE ? OR name LIKE ? OR description LIKE ? OR notes LIKE ?)";
            array_push($params, $like, $like, $like, $like);
        }

        return $this->query("SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$order} LIMIT {$limit}", $params);
    }
}
