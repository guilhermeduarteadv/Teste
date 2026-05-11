<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class CaseModel extends Model
{
    protected $table = 'cases';

    public function search(string $term, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "c.deleted_at IS NULL";

        if (!empty($term)) {
            $like = "%{$term}%";
            $where .= " AND (c.numero_cnj LIKE ? OR c.assunto LIKE ? OR c.comarca LIKE ?)";
            $params = array_merge($params, [$like, $like, $like]);
        }
        if (!empty($filters['status'])) {
            $where .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['tribunal'])) {
            $where .= " AND c.tribunal = ?";
            $params[] = $filters['tribunal'];
        }
        if (!empty($filters['responsavel_id'])) {
            $where .= " AND c.responsavel_id = ?";
            $params[] = $filters['responsavel_id'];
        }

        $countRow = $this->queryOne("SELECT COUNT(*) AS cnt FROM cases c WHERE {$where}", $params);
        $total = (int)($countRow['cnt'] ?? 0);

        $items = $this->query(
            "SELECT c.*, u.name AS responsavel_name,
                    GROUP_CONCAT(cl.name ORDER BY cl.name SEPARATOR ', ') AS clientes
             FROM cases c
             LEFT JOIN users u ON c.responsavel_id = u.id
             LEFT JOIN case_clients cc ON c.id = cc.case_id
             LEFT JOIN clients cl ON cc.client_id = cl.id
             WHERE {$where}
             GROUP BY c.id
             ORDER BY c.created_at DESC
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
        $case = $this->queryOne(
            "SELECT c.*, u.name AS responsavel_name, u.email AS responsavel_email
             FROM cases c
             LEFT JOIN users u ON c.responsavel_id = u.id
             WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1",
            [$id]
        );
        if (!$case) return null;

        $case['clients']   = $this->getCaseClients($id);
        $case['movements'] = $this->getCaseMovements($id);
        $case['deadlines'] = $this->getCaseDeadlines($id);
        $case['hearings']  = $this->getCaseHearings($id);

        return $case;
    }

    public function getCaseClients(int $caseId): array
    {
        return $this->query(
            "SELECT cl.*, cc.tipo AS polo
             FROM clients cl
             JOIN case_clients cc ON cl.id = cc.client_id
             WHERE cc.case_id = ? AND cl.deleted_at IS NULL
             ORDER BY cl.name ASC",
            [$caseId]
        );
    }

    public function getCaseMovements(int $caseId, int $limit = 0): array
    {
        $sql = "SELECT cm.*, u.name AS created_by_name
                FROM case_movements cm
                LEFT JOIN users u ON cm.created_by = u.id
                WHERE cm.case_id = ?
                ORDER BY cm.data_movimento DESC";
        if ($limit > 0) $sql .= " LIMIT {$limit}";
        return $this->query($sql, [$caseId]);
    }

    public function getCaseDeadlines(int $caseId): array
    {
        return $this->query(
            "SELECT cd.*, u.name AS created_by_name, uc.name AS confirmado_por_name
             FROM case_deadlines cd
             LEFT JOIN users u ON cd.created_by = u.id
             LEFT JOIN users uc ON cd.confirmado_por = uc.id
             WHERE cd.case_id = ? AND cd.deleted_at IS NULL
             ORDER BY cd.data_final ASC",
            [$caseId]
        );
    }

    public function getCaseHearings(int $caseId): array
    {
        return $this->query(
            "SELECT ch.*, u.name AS created_by_name
             FROM case_hearings ch
             LEFT JOIN users u ON ch.created_by = u.id
             WHERE ch.case_id = ? AND ch.deleted_at IS NULL
             ORDER BY ch.data ASC, ch.hora ASC",
            [$caseId]
        );
    }

    public function addClient(int $caseId, int $clientId, string $tipo): bool
    {
        $existing = $this->queryOne(
            "SELECT id FROM case_clients WHERE case_id = ? AND client_id = ?",
            [$caseId, $clientId]
        );
        if ($existing) return true;
        return $this->execute(
            "INSERT INTO case_clients (case_id, client_id, tipo) VALUES (?, ?, ?)",
            [$caseId, $clientId, $tipo]
        );
    }

    public function removeClient(int $caseId, int $clientId): bool
    {
        return $this->execute(
            "DELETE FROM case_clients WHERE case_id = ? AND client_id = ?",
            [$caseId, $clientId]
        );
    }

    public function addMovement(array $data): int
    {
        $data['hash'] = hash('sha256', $data['descricao'] . $data['data_movimento'] . $data['case_id']);
        $stmt = $this->db->prepare(
            "INSERT INTO case_movements (case_id, data_movimento, tipo, descricao, fonte, cnj_id, hash, visivel_cliente, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $data['case_id'],
            $data['data_movimento'],
            $data['tipo'] ?? null,
            $data['descricao'],
            $data['fonte'] ?? 'manual',
            $data['cnj_id'] ?? null,
            $data['hash'],
            $data['visivel_cliente'] ?? 0,
            $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function addDeadline(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO case_deadlines (case_id, tipo, descricao, data_inicio, prazo_dias, data_final, data_final_calculada, status, visivel_cliente, observacoes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $data['case_id'],
            $data['tipo'],
            $data['descricao'],
            $data['data_inicio'],
            $data['prazo_dias'] ?? 0,
            $data['data_final'],
            $data['data_final_calculada'] ?? $data['data_final'],
            $data['visivel_cliente'] ?? 0,
            $data['observacoes'] ?? null,
            $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function addHearing(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO case_hearings (case_id, client_id, tipo, titulo, data, hora, local, observacoes, status, visivel_cliente, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $data['case_id'] ?? null,
            $data['client_id'] ?? null,
            $data['tipo'],
            $data['titulo'],
            $data['data'],
            $data['hora'],
            $data['local'] ?? null,
            $data['observacoes'] ?? null,
            $data['visivel_cliente'] ?? 0,
            $data['created_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByNumeroCnj(string $numeroCnj): ?array
    {
        return $this->queryOne(
            "SELECT * FROM cases WHERE numero_cnj = ? AND deleted_at IS NULL LIMIT 1",
            [$numeroCnj]
        );
    }

    public function updateCnjData(int $id, array $data): bool
    {
        return $this->execute(
            "UPDATE cases SET cnj_raw_data = ?, last_sync_at = NOW(), last_movement_hash = ?, updated_at = NOW() WHERE id = ?",
            [json_encode($data), $data['hash'] ?? null, $id]
        );
    }

    public function getUpcomingDeadlines(int $days = 7): array
    {
        return $this->query(
            "SELECT cd.*, c.numero_cnj, c.assunto, u.name AS responsavel_name
             FROM case_deadlines cd
             JOIN cases c ON cd.case_id = c.id
             LEFT JOIN users u ON c.responsavel_id = u.id
             WHERE cd.status = 'pendente' AND cd.data_final BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND c.deleted_at IS NULL
             ORDER BY cd.data_final ASC",
            [$days]
        );
    }

    public function getStatusCounts(): array
    {
        $rows = $this->query(
            "SELECT status, COUNT(*) AS cnt FROM cases WHERE deleted_at IS NULL GROUP BY status"
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
        }
        return $counts;
    }

    public function getForSelect(): array
    {
        return $this->query(
            "SELECT id, numero_cnj, assunto, status FROM cases WHERE deleted_at IS NULL AND status = 'ativo' ORDER BY numero_cnj ASC"
        );
    }
}
