<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class DashboardStatsService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function processesByMonth(int $months = 12): array
    {
        $sql = "
            SELECT DATE_FORMAT(data_distribuicao, '%Y-%m') AS month_key, COUNT(*) AS total
            FROM cases
            WHERE data_distribuicao IS NOT NULL
              AND data_distribuicao >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
              AND (deleted_at IS NULL OR deleted_at = '')
            GROUP BY DATE_FORMAT(data_distribuicao, '%Y-%m')
            ORDER BY month_key ASC
        ";
        return $this->fetchAll($sql, [$months]);
    }

    public function receivedByMonth(int $months = 12): array
    {
        $sql = "
            SELECT DATE_FORMAT(COALESCE(data_pagamento, vencimento), '%Y-%m') AS month_key,
                   SUM(valor) AS total
            FROM financial_entries
            WHERE status = 'pago'
              AND COALESCE(data_pagamento, vencimento) IS NOT NULL
              AND COALESCE(data_pagamento, vencimento) >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
              AND (deleted_at IS NULL OR deleted_at = '')
            GROUP BY DATE_FORMAT(COALESCE(data_pagamento, vencimento), '%Y-%m')
            ORDER BY month_key ASC
        ";
        return $this->fetchAll($sql, [$months]);
    }

    public function casesByArea(): array
    {
        $sql = "
            SELECT COALESCE(NULLIF(area, ''), 'Não informada') AS label, COUNT(*) AS total
            FROM cases
            WHERE deleted_at IS NULL OR deleted_at = ''
            GROUP BY COALESCE(NULLIF(area, ''), 'Não informada')
            ORDER BY total DESC
        ";
        return $this->fetchAll($sql);
    }

    public function casesByComarca(): array
    {
        $sql = "
            SELECT COALESCE(NULLIF(comarca, ''), 'Não informada') AS label, COUNT(*) AS total
            FROM cases
            WHERE deleted_at IS NULL OR deleted_at = ''
            GROUP BY COALESCE(NULLIF(comarca, ''), 'Não informada')
            ORDER BY total DESC
            LIMIT 10
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
            return [];
        }
    }
}
