<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class TaskModel extends Model
{
    protected string $table = 'tasks';

    public function search(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "t.deleted_at IS NULL";

        if (!empty($filters['status'])) {
            $where .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['prioridade'])) {
            $where .= " AND t.prioridade = ?";
            $params[] = $filters['prioridade'];
        }
        if (!empty($filters['responsavel_id'])) {
            $where .= " AND t.responsavel_id = ?";
            $params[] = $filters['responsavel_id'];
        }
        if (!empty($filters['case_id'])) {
            $where .= " AND t.case_id = ?";
            $params[] = $filters['case_id'];
        }
        if (!empty($filters['term'])) {
            $like = "%{$filters['term']}%";
            $where .= " AND (t.title LIKE ? OR t.descricao LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['overdue'])) {
            $where .= " AND t.prazo < CURDATE() AND t.status NOT IN ('concluida','cancelada')";
        }

        $countRow = $this->queryOne("SELECT COUNT(*) AS cnt FROM tasks t WHERE {$where}", $params);
        $total = (int)($countRow['cnt'] ?? 0);

        $items = $this->query(
            "SELECT t.*, u.name AS responsavel_name, c.numero_cnj, cl.name AS client_name
             FROM tasks t
             LEFT JOIN users u ON t.responsavel_id = u.id
             LEFT JOIN cases c ON t.case_id = c.id
             LEFT JOIN clients cl ON t.client_id = cl.id
             WHERE {$where}
             ORDER BY FIELD(t.prioridade,'urgente','alta','media','baixa'), t.prazo ASC
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
            "SELECT t.*, u.name AS responsavel_name, c.numero_cnj, c.assunto, cl.name AS client_name, ub.name AS created_by_name
             FROM tasks t
             LEFT JOIN users u ON t.responsavel_id = u.id
             LEFT JOIN cases c ON t.case_id = c.id
             LEFT JOIN clients cl ON t.client_id = cl.id
             LEFT JOIN users ub ON t.created_by = ub.id
             WHERE t.id = ? AND t.deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }

    public function complete(int $id): bool
    {
        return $this->execute(
            "UPDATE tasks SET status = 'concluida', updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public function getUpcomingTasks(int $days = 7): array
    {
        return $this->query(
            "SELECT t.*, u.name AS responsavel_name
             FROM tasks t
             LEFT JOIN users u ON t.responsavel_id = u.id
             WHERE t.status IN ('pendente','em_andamento')
             AND t.prazo IS NOT NULL
             AND t.prazo BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND t.deleted_at IS NULL
             ORDER BY t.prazo ASC",
            [$days]
        );
    }

    public function getOverdueTasks(): array
    {
        return $this->query(
            "SELECT t.*, u.name AS responsavel_name
             FROM tasks t
             LEFT JOIN users u ON t.responsavel_id = u.id
             WHERE t.status IN ('pendente','em_andamento')
             AND t.prazo < CURDATE()
             AND t.deleted_at IS NULL
             ORDER BY t.prazo ASC"
        );
    }

    public function getStatusCounts(): array
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) AS cnt FROM tasks WHERE deleted_at IS NULL GROUP BY status"
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
        }
        return $counts;
    }

    public function getUserTasks(int $userId, string $status = ''): array
    {
        $sql = "SELECT t.*, c.numero_cnj, cl.name AS client_name
                FROM tasks t
                LEFT JOIN cases c ON t.case_id = c.id
                LEFT JOIN clients cl ON t.client_id = cl.id
                WHERE t.responsavel_id = ? AND t.deleted_at IS NULL";
        $params = [$userId];
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY t.prazo ASC";
        return $this->query($sql, $params);
    }
}
