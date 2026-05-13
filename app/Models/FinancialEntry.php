<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class FinancialEntry extends Model
{
    protected $table = 'financial_entries';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 'fe.deleted_at IS NULL';
        $params = [];
        if (!empty($filters['status'])) { $where .= ' AND fe.status = ?'; $params[] = $filters['status']; }
        if (!empty($filters['tipo'])) { $where .= ' AND fe.tipo = ?'; $params[] = $filters['tipo']; }
        if (!empty($filters['client_id'])) { $where .= ' AND fe.client_id = ?'; $params[] = $filters['client_id']; }
        if (!empty($filters['case_id'])) { $where .= ' AND fe.case_id = ?'; $params[] = $filters['case_id']; }
        if (!empty($filters['data_inicio'])) { $where .= ' AND fe.vencimento >= ?'; $params[] = $filters['data_inicio']; }
        if (!empty($filters['data_fim'])) { $where .= ' AND fe.vencimento <= ?'; $params[] = $filters['data_fim']; }
        if (!empty($filters['search'])) { $where .= ' AND (fe.descricao LIKE ? OR cl.name LIKE ? OR ca.numero_cnj LIKE ?)'; $s='%'.$filters['search'].'%'; $params=array_merge($params,[$s,$s,$s]); }
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM financial_entries fe LEFT JOIN clients cl ON fe.client_id=cl.id LEFT JOIN cases ca ON fe.case_id=ca.id WHERE {$where}");
        $stmt->execute($params); $total=(int)$stmt->fetchColumn();
        $offset=($page-1)*$perPage;
        $stmt=$this->db->prepare("SELECT fe.*, cl.name as client_name, ca.numero_cnj,
            parent.descricao AS parent_descricao,
            parent.valor AS parent_valor,
            COALESCE(pay.total_pago_vinculado, 0) AS total_pago_vinculado
            FROM financial_entries fe
            LEFT JOIN clients cl ON fe.client_id=cl.id
            LEFT JOIN cases ca ON fe.case_id=ca.id
            LEFT JOIN financial_entries parent ON fe.parent_entry_id = parent.id
            LEFT JOIN (
                SELECT parent_entry_id, SUM(valor) AS total_pago_vinculado
                FROM financial_entries
                WHERE deleted_at IS NULL AND is_payment = 1 AND status = 'pago' AND parent_entry_id IS NOT NULL
                GROUP BY parent_entry_id
            ) pay ON pay.parent_entry_id = fe.id
            WHERE {$where}
            ORDER BY fe.vencimento DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['data'=>$stmt->fetchAll(),'total'=>$total,'per_page'=>$perPage,'current_page'=>$page,'last_page'=>(int)ceil($total/$perPage)];
    }

    public function getSummary(): array
    {
        $row = $this->queryOne("SELECT
            COALESCE(SUM(CASE WHEN status='pago' THEN valor ELSE 0 END),0) AS total_recebido,
            COALESCE(SUM(CASE WHEN status='pendente' THEN valor ELSE 0 END),0) AS total_pendente,
            COALESCE(SUM(CASE WHEN status='pendente' AND vencimento < CURDATE() THEN valor ELSE 0 END),0) AS total_vencido,
            COALESCE(SUM(valor),0) AS total_geral,
            COUNT(*) AS total_lancamentos
            FROM financial_entries WHERE deleted_at IS NULL") ?: [];
        $overdue = $this->getOverdue();
        return [
            'total_recebido'=>(float)($row['total_recebido'] ?? 0),
            'total_pendente'=>(float)($row['total_pendente'] ?? 0),
            'total_vencido'=>(float)($row['total_vencido'] ?? 0),
            'total_geral'=>(float)($row['total_geral'] ?? 0),
            'total_lancamentos'=>(int)($row['total_lancamentos'] ?? 0),
            'total_count'=>(int)($row['total_lancamentos'] ?? 0),
            'overdue_entries'=>$overdue,
            // aliases antigos
            'received'=>(float)($row['total_recebido'] ?? 0),
            'to_receive'=>(float)($row['total_pendente'] ?? 0),
            'overdue'=>(float)($row['total_vencido'] ?? 0),
            'month_revenue'=>(float)($this->db->query("SELECT COALESCE(SUM(valor),0) FROM financial_entries WHERE status='pago' AND MONTH(data_pagamento)=MONTH(NOW()) AND YEAR(data_pagamento)=YEAR(NOW()) AND deleted_at IS NULL")->fetchColumn() ?? 0),
        ];
    }

    public function getOverdue(): array
    {
        return $this->query("SELECT fe.*, cl.name as client_name FROM financial_entries fe LEFT JOIN clients cl ON fe.client_id=cl.id WHERE fe.status='pendente' AND fe.vencimento < CURDATE() AND fe.deleted_at IS NULL ORDER BY fe.vencimento ASC");
    }

    public function markAsPaid(int $id, string $dataPagamento, string $formaPagamento, ?string $reciboPath = null): bool
    {
        if ($reciboPath !== null) {
            $stmt=$this->db->prepare("UPDATE financial_entries SET status='pago', data_pagamento=?, forma_pagamento=?, recibo_path=?, updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
            return $stmt->execute([$dataPagamento,$formaPagamento,$reciboPath,$id]);
        }

        $stmt=$this->db->prepare("UPDATE financial_entries SET status='pago', data_pagamento=?, forma_pagamento=?, updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
        return $stmt->execute([$dataPagamento,$formaPagamento,$id]);
    }

    public function getMonthlyRevenue(int $year = 0, int $months = 12): array
    {
        $months = max(1, min(24, $months));
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = new \DateTime("first day of -{$i} month");
            $month = (int)$date->format('m');
            $yearValue = (int)$date->format('Y');

            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(valor), 0)
                FROM financial_entries
                WHERE status = 'pago'
                  AND data_pagamento IS NOT NULL
                  AND MONTH(data_pagamento) = ?
                  AND YEAR(data_pagamento) = ?
                  AND deleted_at IS NULL
            ");
            $stmt->execute([$month, $yearValue]);

            $result[] = [
                'month' => $date->format('m/Y'),
                'year' => $yearValue,
                'month_number' => $month,
                'revenue' => (float)$stmt->fetchColumn()
            ];
        }

        return $result;
    }


    public function getOpenReceivables(?int $excludeId = null): array
    {
        $params = [];
        $where = "fe.deleted_at IS NULL AND fe.is_payment = 0 AND fe.status IN ('pendente','parcial','vencido')";
        if ($excludeId) {
            $where .= " AND fe.id <> ?";
            $params[] = $excludeId;
        }
        return $this->query(
            "SELECT fe.*, cl.name AS client_name, ca.numero_cnj,
                    COALESCE(pay.total_pago, 0) AS total_pago_vinculado,
                    GREATEST(fe.valor - COALESCE(pay.total_pago, 0), 0) AS saldo_aberto
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             LEFT JOIN cases ca ON fe.case_id = ca.id
             LEFT JOIN (
                SELECT parent_entry_id, SUM(valor) AS total_pago
                FROM financial_entries
                WHERE deleted_at IS NULL AND is_payment = 1 AND status = 'pago' AND parent_entry_id IS NOT NULL
                GROUP BY parent_entry_id
             ) pay ON pay.parent_entry_id = fe.id
             WHERE {$where}
             ORDER BY fe.vencimento DESC, fe.id DESC",
            $params
        );
    }

    public function getLinkedPayments(int $parentId): array
    {
        return $this->query(
            "SELECT fe.*, cl.name AS client_name
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             WHERE fe.deleted_at IS NULL AND fe.parent_entry_id = ?
             ORDER BY fe.data_pagamento DESC, fe.vencimento DESC, fe.id DESC",
            [$parentId]
        );
    }

    public function getPaymentProgress(int $parentId): array
    {
        $parent = $this->findById($parentId);
        if (!$parent) {
            return ['valor_total' => 0.0, 'total_pago' => 0.0, 'saldo' => 0.0, 'percentual' => 0.0];
        }
        $row = $this->queryOne(
            "SELECT COALESCE(SUM(valor), 0) AS total_pago
             FROM financial_entries
             WHERE deleted_at IS NULL AND parent_entry_id = ? AND is_payment = 1 AND status = 'pago'",
            [$parentId]
        );
        $valorTotal = (float)($parent['valor'] ?? 0);
        $totalPago = (float)($row['total_pago'] ?? 0);
        $saldo = max($valorTotal - $totalPago, 0);
        $percentual = $valorTotal > 0 ? min(100, ($totalPago / $valorTotal) * 100) : 0;
        return [
            'valor_total' => $valorTotal,
            'total_pago' => $totalPago,
            'saldo' => $saldo,
            'percentual' => $percentual,
        ];
    }

    public function refreshParentPaymentStatus(int $parentId): void
    {
        $progress = $this->getPaymentProgress($parentId);
        $status = 'pendente';
        $dataPagamento = null;
        $formaPagamento = null;

        if ($progress['valor_total'] > 0 && $progress['total_pago'] >= $progress['valor_total']) {
            $status = 'pago';
            $last = $this->queryOne(
                "SELECT data_pagamento, forma_pagamento FROM financial_entries
                 WHERE deleted_at IS NULL AND parent_entry_id = ? AND is_payment = 1 AND status = 'pago'
                 ORDER BY data_pagamento DESC, id DESC LIMIT 1",
                [$parentId]
            );
            $dataPagamento = $last['data_pagamento'] ?? date('Y-m-d');
            $formaPagamento = $last['forma_pagamento'] ?? null;
        } elseif ($progress['total_pago'] > 0) {
            $status = 'parcial';
        } else {
            $parent = $this->findById($parentId);
            if (!empty($parent['vencimento']) && $parent['vencimento'] < date('Y-m-d')) {
                $status = 'vencido';
            }
        }

        $this->execute(
            "UPDATE financial_entries
             SET status = ?, data_pagamento = ?, forma_pagamento = ?, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL",
            [$status, $dataPagamento, $formaPagamento, $parentId]
        );
    }

    public function getClientFinancial(int $clientId, bool $visibleOnly = false): array
    {
        $sql="SELECT fe.*, ca.numero_cnj FROM financial_entries fe LEFT JOIN cases ca ON fe.case_id=ca.id WHERE fe.client_id=? AND fe.deleted_at IS NULL";
        if($visibleOnly) $sql.=" AND fe.visivel_cliente = 1";
        return $this->query($sql." ORDER BY fe.vencimento DESC",[$clientId]);
    }
}
