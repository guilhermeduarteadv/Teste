<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\OfficeStatsService;
use App\Services\SchemaGuardService;

class OfficeStatsController extends Controller
{
    public function index(): void
    {
        (new SchemaGuardService())->ensureV42Schema();

        $dataset = $_GET['dataset'] ?? 'financial';
        $metric = $_GET['metric'] ?? 'sum';
        $groupBy = $_GET['group_by'] ?? 'client';
        $orderBy = $_GET['order_by'] ?? 'total';
        $direction = $_GET['direction'] ?? 'desc';
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $service = new OfficeStatsService();
        $dynamicRows = $service->dynamicStats($dataset, $metric, $groupBy, $orderBy, $direction, $dateFrom ?: null, $dateTo ?: null);

        $this->render('stats/index', [
            'pageTitle' => 'Estatísticas do Escritório',
            'stats' => $service->fullStats(),
            'dynamicRows' => $dynamicRows,
            'filters' => [
                'dataset' => $dataset,
                'metric' => $metric,
                'group_by' => $groupBy,
                'order_by' => $orderBy,
                'direction' => $direction,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function api(): void
    {
        (new SchemaGuardService())->ensureV42Schema();

        $service = new OfficeStatsService();

        $this->json([
            'success' => true,
            'rows' => $service->dynamicStats(
                $_GET['dataset'] ?? 'financial',
                $_GET['metric'] ?? 'sum',
                $_GET['group_by'] ?? 'client',
                $_GET['order_by'] ?? 'total',
                $_GET['direction'] ?? 'desc',
                $_GET['date_from'] ?? null,
                $_GET['date_to'] ?? null
            )
        ]);
    }
}
