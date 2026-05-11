<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class FinancialModel extends Model
{
    protected $table = 'financial_entries';

    public function search(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "fe.deleted_at IS NULL";

        if (!empty($filters['status'])) {
            $where .= " AND fe.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['tipo'])) {
            $where .= " AND fe.tipo = ?";
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['client_id'])) {
            $where .= " AND fe.client_id = ?";
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['case_id'])) {
            $where .= " AND fe.case_id = ?";
            $params[] = $filters['case_id'];
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND fe.vencimento >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND fe.vencimento <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['term'])) {
            $like = "%{$filters['term']}%";
            $where .= " AND (fe.descricao LIKE ? OR cl.name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }

        $countRow = $this->queryOne(
            "SELECT COUNT(*) AS cnt FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             WHERE {$where}",
            $params
        );
        $total = (int)($countRow['cnt'] ?? 0);

        $items = $this->query(
            "SELECT fe.*, cl.name AS client_name, c.numero_cnj, u.name AS created_by_name
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             LEFT JOIN cases c ON fe.case_id = c.id
             LEFT JOIN users u ON fe.created_by = u.id
             WHERE {$where}
             ORDER BY fe.vencimento DESC
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

    public function getSummary(): array
    {
        $row = $this->queryOne(
            "SELECT
                SUM(CASE WHEN status = 'pago' THEN valor ELSE 0 END) AS total_recebido,
                SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END) AS total_pendente,
                SUM(CASE WHEN status = 'vencido' THEN valor ELSE 0 END) AS total_vencido,
                SUM(CASE WHEN status NOT IN ('cancelado') THEN valor ELSE 0 END) AS total_geral
             FROM financial_entries WHERE deleted_at IS NULL"
        );
        return $row ?? ['total_recebido' => 0, 'total_pendente' => 0, 'total_vencido' => 0, 'total_geral' => 0];
    }

    public function getMonthlyRevenue(int $months = 12): array
    {
        return $this->query(
            "SELECT DATE_FORMAT(data_pagamento, '%Y-%m') AS mes,
                    SUM(valor) AS total
             FROM financial_entries
             WHERE status = 'pago' AND deleted_at IS NULL
             AND data_pagamento >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY mes
             ORDER BY mes ASC",
            [$months]
        );
    }

    public function getOverdue(): array
    {
        return $this->query(
            "SELECT fe.*, cl.name AS client_name
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             WHERE fe.status = 'pendente' AND fe.vencimento < CURDATE() AND fe.deleted_at IS NULL
             ORDER BY fe.vencimento ASC"
        );
    }

    public function markAsPaid(int $id, string $dataPagamento, string $formaPagamento): bool
    {
        return $this->execute(
            "UPDATE financial_entries SET status = 'pago', data_pagamento = ?, forma_pagamento = ?, updated_at = NOW() WHERE id = ?",
            [$dataPagamento, $formaPagamento, $id]
        );
    }

    public function updateOverdueStatus(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE financial_entries SET status = 'vencido' WHERE status = 'pendente' AND vencimento < CURDATE() AND deleted_at IS NULL"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function findWithDetails(int $id): ?array
    {
        return $this->queryOne(
            "SELECT fe.*, cl.name AS client_name, c.numero_cnj, c.assunto, u.name AS created_by_name
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             LEFT JOIN cases c ON fe.case_id = c.id
             LEFT JOIN users u ON fe.created_by = u.id
             WHERE fe.id = ? AND fe.deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }

    public function getClientFinancialSummary(int $clientId): array
    {
        $row = $this->queryOne(
            "SELECT
                SUM(CASE WHEN status = 'pago' THEN valor ELSE 0 END) AS total_pago,
                SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END) AS total_pendente,
                SUM(CASE WHEN status = 'vencido' THEN valor ELSE 0 END) AS total_vencido,
                COUNT(*) AS total_lancamentos
             FROM financial_entries WHERE client_id = ? AND deleted_at IS NULL",
            [$clientId]
        );
        return $row ?? ['total_pago' => 0, 'total_pendente' => 0, 'total_vencido' => 0, 'total_lancamentos' => 0];
    }

    public function getUpcomingDue(int $days = 7): array
    {
        return $this->query(
            "SELECT fe.*, cl.name AS client_name
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             WHERE fe.status = 'pendente'
             AND fe.vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             AND fe.deleted_at IS NULL
             ORDER BY fe.vencimento ASC",
            [$days]
        );
    }
}
