<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class OfficeStatsService
{
    private $db;
    private $schema;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->schema = new SchemaGuardService();
        $this->schema->ensureV42Schema();
    }

    public function fullStats(): array
    {
        return [
            'clients_by_month' => $this->dynamicStats('clients', 'count', 'month', 'total', 'desc', null, null),
            'clients_by_year' => $this->dynamicStats('clients', 'count', 'year', 'total', 'desc', null, null),
            'revenue_by_client' => $this->dynamicStats('financial', 'sum', 'client', 'total', 'desc', null, null),
            'revenue_by_area' => $this->dynamicStats('financial', 'sum', 'area', 'total', 'desc', null, null),
            'revenue_by_origin' => $this->dynamicStats('financial', 'sum', 'origin', 'total', 'desc', null, null),
            'judicial_vs_extrajudicial' => $this->dynamicStats('portfolio', 'count', 'origin', 'total', 'desc', null, null),
            'processes_by_month' => $this->dynamicStats('cases', 'count', 'month', 'total', 'desc', null, null),
            'administrative_by_month' => $this->dynamicStats('administrative', 'count', 'month', 'total', 'desc', null, null),
            'consultancies_by_month' => $this->dynamicStats('consultancies', 'count', 'month', 'total', 'desc', null, null),
            'top_clients_open_balance' => $this->dynamicStats('financial_open', 'sum', 'client', 'total', 'desc', null, null),
        ];
    }

    public function dynamicStats(
        string $dataset,
        string $metric,
        string $groupBy,
        string $orderBy = 'total',
        string $direction = 'desc',
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $orderBy = in_array($orderBy, ['label', 'total'], true) ? $orderBy : 'total';

        $params = [];
        $dateExpr = 'base.created_at';

        switch ($dataset) {
            case 'clients':
                $dateExpr = 'base.created_at';
                $from = "clients base";
                $where = "base.deleted_at IS NULL";
                $metricExpr = "COUNT(*)";
                $groupExpr = $this->groupExpr($groupBy, $dateExpr, [
                    'client' => "base.name",
                    'status' => "base.status"
                ]);
                break;

            case 'cases':
                $dateExpr = $this->schema->columnExists('cases', 'data_distribuicao')
                    ? "COALESCE(base.data_distribuicao, base.created_at)"
                    : "base.created_at";

                $from = "cases base";
                $where = "base.deleted_at IS NULL";
                $metricExpr = "COUNT(*)";
                $groupExpr = $this->groupExpr($groupBy, $dateExpr, [
                    'area' => $this->schema->columnExists('cases', 'area') ? "COALESCE(NULLIF(base.area,''),'Não informada')" : "'Não informada'",
                    'status' => "base.status",
                    'comarca' => $this->schema->columnExists('cases', 'comarca') ? "COALESCE(NULLIF(base.comarca,''),'Não informada')" : "'Não informada'",
                    'tribunal' => $this->schema->columnExists('cases', 'tribunal') ? "COALESCE(NULLIF(base.tribunal,''),'Não informado')" : "'Não informado'",
                    'fase' => $this->schema->columnExists('cases', 'fase_processual') ? "COALESCE(NULLIF(base.fase_processual,''),'Não informada')" : "'Não informada'"
                ]);
                break;

            case 'administrative':
                if (!$this->schema->tableExists('administrative_procedures')) {
                    return [];
                }

                $dateExpr = "COALESCE(base.data_protocolo, base.created_at)";
                $from = "administrative_procedures base LEFT JOIN clients cl ON cl.id = base.client_id";
                $where = "base.deleted_at IS NULL";
                $metricExpr = "COUNT(*)";
                $groupExpr = $this->groupExpr($groupBy, $dateExpr, [
                    'client' => "COALESCE(cl.name,'Sem cliente')",
                    'status' => "base.status",
                    'area' => "COALESCE(NULLIF(base.tipo_procedimento,''),'Não informado')",
                    'prefeitura' => "COALESCE(NULLIF(base.prefeitura,''),'Não informada')",
                    'origin' => "'Administrativo/Prefeitura'"
                ]);
                break;

            case 'consultancies':
                if (!$this->schema->tableExists('legal_consultancies')) {
                    return [];
                }

                $dateExpr = "COALESCE(base.data_inicio, base.created_at)";
                $from = "legal_consultancies base LEFT JOIN clients cl ON cl.id = base.client_id";
                $where = "base.deleted_at IS NULL";
                $metricExpr = "COUNT(*)";
                $groupExpr = $this->groupExpr($groupBy, $dateExpr, [
                    'client' => "COALESCE(cl.name,'Sem cliente')",
                    'status' => "base.status",
                    'area' => "COALESCE(NULLIF(base.area,''),'Não informada')",
                    'origin' => "'Consultoria'"
                ]);
                break;

            case 'financial':
            case 'financial_open':
                return $this->financialStats($dataset, $metric, $groupBy, $orderBy, $direction, $dateFrom, $dateTo);

            case 'portfolio':
            default:
                return $this->portfolioByOrigin();
        }

        if ($dateFrom) {
            $where .= " AND DATE({$dateExpr}) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where .= " AND DATE({$dateExpr}) <= ?";
            $params[] = $dateTo;
        }

        $sql = "
            SELECT {$groupExpr} AS label, {$metricExpr} AS total
            FROM {$from}
            WHERE {$where}
            GROUP BY {$groupExpr}
            ORDER BY {$orderBy} {$direction}
            LIMIT 100
        ";

        return $this->fetchAll($sql, $params);
    }

    private function financialStats(
        string $dataset,
        string $metric,
        string $groupBy,
        string $orderBy,
        string $direction,
        ?string $dateFrom,
        ?string $dateTo
    ): array {
        $hasCaseId = $this->schema->columnExists('financial_entries', 'case_id');
        $hasAdminId = $this->schema->columnExists('financial_entries', 'administrative_procedure_id');
        $hasConsultancyId = $this->schema->columnExists('financial_entries', 'consultancy_id');
        $hasEntityType = $this->schema->columnExists('financial_entries', 'entity_type');

        $joins = " LEFT JOIN clients cl ON cl.id = base.client_id ";

        if ($hasCaseId && $this->schema->tableExists('cases')) {
            $joins .= " LEFT JOIN cases ca ON ca.id = base.case_id ";
        } else {
            $joins .= " ";
        }

        if ($hasAdminId && $this->schema->tableExists('administrative_procedures')) {
            $joins .= " LEFT JOIN administrative_procedures ap ON ap.id = base.administrative_procedure_id ";
        }

        if ($hasConsultancyId && $this->schema->tableExists('legal_consultancies')) {
            $joins .= " LEFT JOIN legal_consultancies lc ON lc.id = base.consultancy_id ";
        }

        $dateExpr = "COALESCE(base.data_pagamento, base.vencimento, base.created_at)";
        $from = "financial_entries base {$joins}";
        $where = "base.deleted_at IS NULL";

        if ($dataset === 'financial') {
            $where .= " AND base.status = 'pago'";
        } else {
            $where .= " AND base.status IN ('pendente','parcial','vencido')";
        }

        $metricExpr = $metric === 'count' ? "COUNT(*)" : "COALESCE(SUM(base.valor),0)";

        $areaParts = [];
        if ($hasCaseId && $this->schema->tableExists('cases') && $this->schema->columnExists('cases', 'area')) {
            $areaParts[] = "ca.area";
        }
        if ($hasConsultancyId && $this->schema->tableExists('legal_consultancies')) {
            $areaParts[] = "lc.area";
        }
        if ($hasAdminId && $this->schema->tableExists('administrative_procedures')) {
            $areaParts[] = "ap.tipo_procedimento";
        }

        $areaExpr = !empty($areaParts)
            ? "COALESCE(" . implode(", ", $areaParts) . ", 'Não informada')"
            : "'Não informada'";

        $originCase = [];
        if ($hasCaseId) {
            $originCase[] = "WHEN base.case_id IS NOT NULL THEN 'Judicial'";
        }
        if ($hasAdminId) {
            $originCase[] = "WHEN base.administrative_procedure_id IS NOT NULL THEN 'Administrativo/Prefeitura'";
        }
        if ($hasConsultancyId) {
            $originCase[] = "WHEN base.consultancy_id IS NOT NULL THEN 'Consultoria'";
        }
        if ($hasEntityType) {
            $originCase[] = "WHEN base.entity_type = 'case' THEN 'Judicial'";
            $originCase[] = "WHEN base.entity_type = 'administrative_procedure' THEN 'Administrativo/Prefeitura'";
            $originCase[] = "WHEN base.entity_type = 'consultancy' THEN 'Consultoria'";
        }

        $originExpr = "CASE " . implode(" ", $originCase) . " ELSE 'Avulso' END";

        $groupExpr = $this->groupExpr($groupBy, $dateExpr, [
            'client' => "COALESCE(cl.name,'Sem cliente')",
            'area' => $areaExpr,
            'status' => "base.status",
            'origin' => $originExpr,
            'payment_method' => "COALESCE(NULLIF(base.forma_pagamento,''),'Não informada')"
        ]);

        $params = [];

        if ($dateFrom) {
            $where .= " AND DATE({$dateExpr}) >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where .= " AND DATE({$dateExpr}) <= ?";
            $params[] = $dateTo;
        }

        $sql = "
            SELECT {$groupExpr} AS label, {$metricExpr} AS total
            FROM {$from}
            WHERE {$where}
            GROUP BY {$groupExpr}
            ORDER BY {$orderBy} {$direction}
            LIMIT 100
        ";

        return $this->fetchAll($sql, $params);
    }

    private function groupExpr(string $groupBy, string $dateExpr, array $map): string
    {
        switch ($groupBy) {
            case 'day':
                return "DATE_FORMAT({$dateExpr}, '%Y-%m-%d')";
            case 'year':
                return "DATE_FORMAT({$dateExpr}, '%Y')";
            case 'month':
                return "DATE_FORMAT({$dateExpr}, '%Y-%m')";
            default:
                return $map[$groupBy] ?? "DATE_FORMAT({$dateExpr}, '%Y-%m')";
        }
    }

    private function portfolioByOrigin(): array
    {
        $parts = [];

        if ($this->schema->tableExists('cases')) {
            $parts[] = "SELECT 'Judicial' AS origem FROM cases WHERE deleted_at IS NULL";
        }

        if ($this->schema->tableExists('administrative_procedures')) {
            $parts[] = "SELECT 'Administrativo/Prefeitura' AS origem FROM administrative_procedures WHERE deleted_at IS NULL";
        }

        if ($this->schema->tableExists('legal_consultancies')) {
            $parts[] = "SELECT 'Consultoria' AS origem FROM legal_consultancies WHERE deleted_at IS NULL";
        }

        if (empty($parts)) {
            return [];
        }

        $sql = "
            SELECT origem AS label, COUNT(*) AS total
            FROM (" . implode(" UNION ALL ", $parts) . ") x
            GROUP BY origem
            ORDER BY total DESC
        ";

        return $this->fetchAll($sql);
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [['label' => 'Erro', 'total' => 0, 'erro' => $e->getMessage()]];
        }
    }
}
