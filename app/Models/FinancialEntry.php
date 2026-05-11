<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class FinancialEntry extends Model
{
    protected string $table = 'financial_entries';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 'fe.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['status'])) {
            $where .= ' AND fe.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND fe.tipo = ?';
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['client_id'])) {
            $where .= ' AND fe.client_id = ?';
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['case_id'])) {
            $where .= ' AND fe.case_id = ?';
            $params[] = $filters['case_id'];
        }
        if (!empty($filters['data_inicio'])) {
            $where .= ' AND fe.vencimento >= ?';
            $params[] = $filters['data_inicio'];
        }
        if (!empty($filters['data_fim'])) {
            $where .= ' AND fe.vencimento <= ?';
            $params[] = $filters['data_fim'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (fe.descricao LIKE ? OR cl.name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }

        $countSql = "SELECT COUNT(*) FROM financial_entries fe LEFT JOIN clients cl ON fe.client_id = cl.id WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT fe.*, cl.name as client_name, ca.numero_cnj FROM financial_entries fe
                LEFT JOIN clients cl ON fe.client_id = cl.id
                LEFT JOIN cases ca ON fe.case_id = ca.id
                WHERE {$where} ORDER BY fe.vencimento DESC LIMIT {$perPage} OFFSET {$offset}";
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

    public function getSummary(): array
    {
        $db = $this->db;
        $toReceive = $db->query("SELECT SUM(valor) FROM financial_entries WHERE status='pendente' AND deleted_at IS NULL")->fetchColumn() ?? 0;
        $received = $db->query("SELECT SUM(valor) FROM financial_entries WHERE status='pago' AND deleted_at IS NULL")->fetchColumn() ?? 0;
        $overdue = $db->query("SELECT SUM(valor) FROM financial_entries WHERE status='pendente' AND vencimento < CURDATE() AND deleted_at IS NULL")->fetchColumn() ?? 0;
        $monthRevenue = $db->query("SELECT SUM(valor) FROM financial_entries WHERE status='pago' AND MONTH(data_pagamento)=MONTH(NOW()) AND YEAR(data_pagamento)=YEAR(NOW()) AND deleted_at IS NULL")->fetchColumn() ?? 0;

        return [
            'to_receive'    => (float)$toReceive,
            'received'      => (float)$received,
            'overdue'       => (float)$overdue,
            'month_revenue' => (float)$monthRevenue,
        ];
    }

    public function getOverdue(): array
    {
        return $this->query(
            "SELECT fe.*, cl.name as client_name FROM financial_entries fe LEFT JOIN clients cl ON fe.client_id = cl.id WHERE fe.status = 'pendente' AND fe.vencimento < CURDATE() AND fe.deleted_at IS NULL ORDER BY fe.vencimento ASC"
        );
    }

    public function markAsPaid(int $id, string $dataPagamento, string $formaPagamento): bool
    {
        $stmt = $this->db->prepare("UPDATE financial_entries SET status='pago', data_pagamento=?, forma_pagamento=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$dataPagamento, $formaPagamento, $id]);
    }

    public function getMonthlyRevenue(int $year, int $months = 12): array
    {
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new \DateTime("first day of -{$i} month");
            $m = $date->format('m');
            $y = $date->format('Y');
            $stmt = $this->db->prepare("SELECT SUM(valor) FROM financial_entries WHERE status='pago' AND MONTH(data_pagamento)=? AND YEAR(data_pagamento)=? AND deleted_at IS NULL");
            $stmt->execute([(int)$m, (int)$y]);
            $result[] = [
                'month'   => $date->format('M/Y'),
                'revenue' => (float)($stmt->fetchColumn() ?? 0),
            ];
        }
        return $result;
    }

    public function getClientFinancial(int $clientId, bool $visibleOnly = false): array
    {
        $sql = "SELECT fe.*, ca.numero_cnj FROM financial_entries fe LEFT JOIN cases ca ON fe.case_id = ca.id WHERE fe.client_id = ? AND fe.deleted_at IS NULL";
        if ($visibleOnly) $sql .= " AND fe.visivel_cliente = 1";
        $sql .= " ORDER BY fe.vencimento DESC";
        return $this->query($sql, [$clientId]);
    }
}
