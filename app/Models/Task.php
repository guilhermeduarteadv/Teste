<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Task extends Model
{
    protected $table = 'tasks';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 't.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['status'])) {
            $where .= ' AND t.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['prioridade'])) {
            $where .= ' AND t.prioridade = ?';
            $params[] = $filters['prioridade'];
        }
        if (!empty($filters['responsavel_id'])) {
            $where .= ' AND t.responsavel_id = ?';
            $params[] = $filters['responsavel_id'];
        }
        if (!empty($filters['case_id'])) {
            $where .= ' AND t.case_id = ?';
            $params[] = $filters['case_id'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND t.title LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $countSql = "SELECT COUNT(*) FROM tasks t WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT t.*, u.name as responsavel_name, c.numero_cnj, cl.name as client_name
                FROM tasks t
                LEFT JOIN users u ON t.responsavel_id = u.id
                LEFT JOIN cases c ON t.case_id = c.id
                LEFT JOIN clients cl ON t.client_id = cl.id
                WHERE {$where} ORDER BY t.prioridade DESC, t.prazo ASC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return ['data' => $items, 'total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => (int)ceil($total / $perPage)];
    }

    public function getStats(): array
    {
        $db = $this->db;
        return [
            'pending'     => (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='pendente' AND deleted_at IS NULL")->fetchColumn(),
            'in_progress' => (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='em_andamento' AND deleted_at IS NULL")->fetchColumn(),
            'completed'   => (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='concluida' AND deleted_at IS NULL")->fetchColumn(),
            'overdue'     => (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status IN ('pendente','em_andamento') AND prazo < CURDATE() AND deleted_at IS NULL")->fetchColumn(),
        ];
    }

    public function complete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE tasks SET status='concluida', updated_at=NOW() WHERE id=?");
        return $stmt->execute([$id]);
    }
}
