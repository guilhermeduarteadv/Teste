<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\DashboardStatsService;

class StatsController
{
    public function dashboard(): void
    {
        if (ob_get_length()) { ob_clean(); }
        header('Content-Type: application/json; charset=utf-8');

        try {
            $service = new DashboardStatsService();
            echo json_encode([
                'success' => true,
                'processes_by_month' => $service->processesByMonth(12),
                'received_by_month' => $service->receivedByMonth(12),
                'cases_by_area' => $service->casesByArea(),
                'cases_by_comarca' => $service->casesByComarca(),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}
