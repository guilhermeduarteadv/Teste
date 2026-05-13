<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Task extends Model
{
    protected $table = 'tasks';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where='t.deleted_at IS NULL'; $params=[];
        if(!empty($filters['status'])){$where.=' AND t.status=?';$params[]=$filters['status'];}
        if(!empty($filters['prioridade'])){$where.=' AND t.prioridade=?';$params[]=$filters['prioridade'];}
        if(!empty($filters['responsavel_id'])){$where.=' AND t.responsavel_id=?';$params[]=$filters['responsavel_id'];}
        if(!empty($filters['case_id'])){$where.=' AND t.case_id=?';$params[]=$filters['case_id'];}
        if(!empty($filters['search'])){$where.=' AND (t.title LIKE ? OR t.descricao LIKE ?)';$s='%'.$filters['search'].'%';$params[]=$s;$params[]=$s;}
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM tasks t WHERE {$where}");$stmt->execute($params);$total=(int)$stmt->fetchColumn();
        $offset=($page-1)*$perPage;
        $stmt=$this->db->prepare("SELECT t.*, u.name as responsavel_name, c.numero_cnj, cl.name as client_name FROM tasks t LEFT JOIN users u ON t.responsavel_id=u.id LEFT JOIN cases c ON t.case_id=c.id LEFT JOIN clients cl ON t.client_id=cl.id WHERE {$where} ORDER BY t.prazo IS NULL, t.prazo ASC, t.prioridade DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['data'=>$stmt->fetchAll(),'total'=>$total,'per_page'=>$perPage,'current_page'=>$page,'last_page'=>(int)ceil($total/$perPage)];
    }

    public function getStats(): array
    {
        $overdue = $this->query("
            SELECT t.*, u.name as responsavel_name
            FROM tasks t
            LEFT JOIN users u ON t.responsavel_id = u.id
            WHERE t.status IN ('pendente','em_andamento')
              AND t.prazo IS NOT NULL
              AND t.prazo < CURDATE()
              AND t.deleted_at IS NULL
            ORDER BY t.prazo ASC
            LIMIT 10
        ");

        $upcoming = $this->query("
            SELECT t.*, u.name as responsavel_name
            FROM tasks t
            LEFT JOIN users u ON t.responsavel_id = u.id
            WHERE t.status IN ('pendente','em_andamento')
              AND (t.prazo IS NULL OR t.prazo >= CURDATE())
              AND t.deleted_at IS NULL
            ORDER BY t.prazo IS NULL, t.prazo ASC
            LIMIT 10
        ");

        $pendentes = (int)$this->db->query("
            SELECT COUNT(*) FROM tasks
            WHERE status = 'pendente'
              AND deleted_at IS NULL
        ")->fetchColumn();

        $emAndamento = (int)$this->db->query("
            SELECT COUNT(*) FROM tasks
            WHERE status = 'em_andamento'
              AND deleted_at IS NULL
        ")->fetchColumn();

        $concluidas = (int)$this->db->query("
            SELECT COUNT(*) FROM tasks
            WHERE status = 'concluida'
              AND deleted_at IS NULL
        ")->fetchColumn();

        $atrasadas = (int)$this->db->query("
            SELECT COUNT(*) FROM tasks
            WHERE status IN ('pendente','em_andamento')
              AND prazo IS NOT NULL
              AND prazo < CURDATE()
              AND deleted_at IS NULL
        ")->fetchColumn();

        return [
            // Chaves usadas pela view atual em app/Views/tasks/index.php
            'pendentes' => $pendentes,
            'em_andamento' => $emAndamento,
            'concluidas' => $concluidas,
            'atrasadas' => $atrasadas,

            // Aliases mantidos para compatibilidade com dashboard/relatórios antigos
            'pending' => $pendentes,
            'in_progress' => $emAndamento,
            'completed' => $concluidas,
            'overdue_count' => $atrasadas,
            'upcoming_count' => count($upcoming),
            'overdue' => $overdue,
            'upcoming' => $upcoming,
        ];
    }

    public function complete(int $id): bool
    {
        $stmt=$this->db->prepare("UPDATE tasks SET status='concluida', updated_at=NOW() WHERE id=?");
        return $stmt->execute([$id]);
    }
}
