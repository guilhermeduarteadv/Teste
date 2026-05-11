<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class LegalCase extends Model
{
    protected string $table = 'cases';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 'c.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['search'])) {
            $where .= ' AND (c.numero_cnj LIKE ? OR c.assunto LIKE ? OR c.classe LIKE ? OR c.comarca LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s]);
        }
        if (!empty($filters['status'])) {
            $where .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['tribunal'])) {
            $where .= ' AND c.tribunal = ?';
            $params[] = $filters['tribunal'];
        }
        if (!empty($filters['client_id'])) {
            $where .= ' AND EXISTS (SELECT 1 FROM case_clients cc WHERE cc.case_id = c.id AND cc.client_id = ?)';
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['responsavel_id'])) {
            $where .= ' AND c.responsavel_id = ?';
            $params[] = $filters['responsavel_id'];
        }

        $countSql = "SELECT COUNT(*) FROM cases c WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, u.name as responsavel_name,
                    (SELECT GROUP_CONCAT(cl.name SEPARATOR ', ') FROM case_clients cc JOIN clients cl ON cc.client_id = cl.id WHERE cc.case_id = c.id) as client_names
                FROM cases c
                LEFT JOIN users u ON c.responsavel_id = u.id
                WHERE {$where} ORDER BY c.updated_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

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
            "SELECT c.*, u.name as responsavel_name FROM cases c LEFT JOIN users u ON c.responsavel_id = u.id WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }

    public function getClients(int $caseId): array
    {
        return $this->query(
            "SELECT cl.*, cc.tipo as participacao FROM clients cl JOIN case_clients cc ON cc.client_id = cl.id WHERE cc.case_id = ? AND cl.deleted_at IS NULL ORDER BY cl.name ASC",
            [$caseId]
        );
    }

    public function addClient(int $caseId, int $clientId, string $tipo = 'autor'): bool
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO case_clients (case_id, client_id, tipo) VALUES (?, ?, ?)");
        return $stmt->execute([$caseId, $clientId, $tipo]);
    }

    public function removeClient(int $caseId, int $clientId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM case_clients WHERE case_id = ? AND client_id = ?");
        return $stmt->execute([$caseId, $clientId]);
    }

    public function getMovements(int $caseId): array
    {
        return $this->query(
            "SELECT m.*, u.name as created_by_name FROM case_movements m LEFT JOIN users u ON m.created_by = u.id WHERE m.case_id = ? ORDER BY m.data_movimento DESC",
            [$caseId]
        );
    }

    public function addMovement(int $caseId, array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO case_movements (case_id, data_movimento, tipo, descricao, fonte, visivel_cliente, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $caseId,
            $data['data_movimento'],
            $data['tipo'] ?? null,
            $data['descricao'],
            $data['fonte'] ?? 'manual',
            $data['visivel_cliente'] ?? 0,
            $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getDeadlines(int $caseId): array
    {
        return $this->query(
            "SELECT d.*, u.name as confirmado_por_name FROM case_deadlines d LEFT JOIN users u ON d.confirmado_por = u.id WHERE d.case_id = ? AND d.deleted_at IS NULL ORDER BY d.data_final ASC",
            [$caseId]
        );
    }

    public function addDeadline(int $caseId, array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO case_deadlines (case_id, tipo, descricao, data_inicio, prazo_dias, data_final, data_final_calculada, confirmado, status, visivel_cliente, observacoes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $caseId,
            $data['tipo'],
            $data['descricao'],
            $data['data_inicio'],
            $data['prazo_dias'] ?? 0,
            $data['data_final'],
            $data['data_final_calculada'] ?? $data['data_final'],
            $data['confirmado'] ?? 0,
            $data['visivel_cliente'] ?? 0,
            $data['observacoes'] ?? null,
            $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getHearings(int $caseId): array
    {
        return $this->query(
            "SELECT * FROM case_hearings WHERE case_id = ? AND deleted_at IS NULL ORDER BY data ASC, hora ASC",
            [$caseId]
        );
    }

    public function getDocuments(int $caseId): array
    {
        return $this->query(
            "SELECT d.*, u.name as uploaded_by_name FROM documents d LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.entity_type = 'case' AND d.entity_id = ? AND d.deleted_at IS NULL ORDER BY d.created_at DESC",
            [$caseId]
        );
    }

    public function getFinancial(int $caseId): array
    {
        return $this->query(
            "SELECT fe.*, cl.name as client_name FROM financial_entries fe LEFT JOIN clients cl ON fe.client_id = cl.id WHERE fe.case_id = ? AND fe.deleted_at IS NULL ORDER BY fe.vencimento DESC",
            [$caseId]
        );
    }

    public function getTasks(int $caseId): array
    {
        return $this->query(
            "SELECT t.*, u.name as responsavel_name FROM tasks t LEFT JOIN users u ON t.responsavel_id = u.id WHERE t.case_id = ? AND t.deleted_at IS NULL ORDER BY t.prazo ASC",
            [$caseId]
        );
    }

    public function getStats(): array
    {
        $db = $this->db;
        return [
            'total'      => (int)$db->query("SELECT COUNT(*) FROM cases WHERE deleted_at IS NULL")->fetchColumn(),
            'ativos'     => (int)$db->query("SELECT COUNT(*) FROM cases WHERE status='ativo' AND deleted_at IS NULL")->fetchColumn(),
            'arquivados' => (int)$db->query("SELECT COUNT(*) FROM cases WHERE status='arquivado' AND deleted_at IS NULL")->fetchColumn(),
            'suspensos'  => (int)$db->query("SELECT COUNT(*) FROM cases WHERE status='suspenso' AND deleted_at IS NULL")->fetchColumn(),
        ];
    }

    public function getUpcomingDeadlines(int $days = 7): array
    {
        return $this->query(
            "SELECT d.*, c.numero_cnj, c.id as case_id,
                GROUP_CONCAT(cl.name SEPARATOR ', ') as client_names
             FROM case_deadlines d
             JOIN cases c ON d.case_id = c.id
             LEFT JOIN case_clients cc ON cc.case_id = c.id
             LEFT JOIN clients cl ON cc.client_id = cl.id
             WHERE d.status = 'pendente' AND d.data_final BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) AND d.deleted_at IS NULL
             GROUP BY d.id
             ORDER BY d.data_final ASC",
            [$days]
        );
    }

    public function getExpiredDeadlines(): array
    {
        return $this->query(
            "SELECT d.*, c.numero_cnj, c.id as case_id FROM case_deadlines d JOIN cases c ON d.case_id = c.id WHERE d.status = 'pendente' AND d.data_final < CURDATE() AND d.deleted_at IS NULL ORDER BY d.data_final ASC"
        );
    }

    public function getTodayDeadlines(): array
    {
        return $this->query(
            "SELECT d.*, c.numero_cnj, c.id as case_id FROM case_deadlines d JOIN cases c ON d.case_id = c.id WHERE d.status = 'pendente' AND d.data_final = CURDATE() AND d.deleted_at IS NULL ORDER BY d.tipo ASC"
        );
    }

    public function getUpcomingHearings(int $days = 30): array
    {
        return $this->query(
            "SELECT h.*, c.numero_cnj FROM case_hearings h LEFT JOIN cases c ON h.case_id = c.id WHERE h.status = 'agendado' AND h.data >= CURDATE() AND h.data <= DATE_ADD(CURDATE(), INTERVAL ? DAY) AND h.deleted_at IS NULL ORDER BY h.data ASC, h.hora ASC",
            [$days]
        );
    }

    public function getByStatus(): array
    {
        $rows = $this->query("SELECT status, COUNT(*) as total FROM cases WHERE deleted_at IS NULL GROUP BY status");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['total'];
        }
        return $result;
    }
}
