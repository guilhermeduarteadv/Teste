<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\SchemaGuardService;
use Core\Session;
use Core\Database;
use App\Models\LegalCase;
use App\Models\Client;
use App\Models\FinancialEntry;
use App\Models\Task;
use App\Models\Hearing;
use App\Services\CNJService;

class DashboardController extends Controller
{
    public function index(): void
    {
        (new SchemaGuardService())->ensureV52Schema();
        $caseModel     = new LegalCase();
        $clientModel   = new Client();
        $financialModel = new FinancialEntry();
        $taskModel     = new Task();
        $hearingModel  = new Hearing();
        $db            = Database::getInstance();

        // Stats
        $caseStats     = $caseModel->getStats();
        $clientStats   = $clientModel->getStats();
        $financialStats = $financialModel->getSummary();
        $taskStats     = $taskModel->getStats();

        // Deadlines
        $todayDeadlines   = $caseModel->getTodayDeadlines();
        $expiredDeadlines = $caseModel->getExpiredDeadlines();
        $upcomingDeadlines = $caseModel->getUpcomingDeadlines(7);

        // Upcoming hearings
        $upcomingHearings = $hearingModel->getTodayHearings();

        // Recent movements
       //$recentMovements = (new \App\Models\LegalCase())->query("SELECT m.*, c.numero_cnj FROM case_movements m JOIN cases c ON m.case_id = c.id ORDER BY m.data_movimento DESC LIMIT 10");

        // CNJ status
        $cnj_last_sync = $db->query("SELECT valor FROM settings WHERE chave='cnj_last_sync' LIMIT 1")->fetchColumn();
        $db_status = true;
        try { Database::getInstance(); } catch (\Exception $e) { $db_status = false; }

        // Cases by status chart data
        $casesByStatus = $caseModel->getByStatus();
        $casesByArea = method_exists($caseModel, 'getByArea') ? $caseModel->getByArea() : [];

        // Monthly revenue chart
        $monthlyRevenue = $financialModel->getMonthlyRevenue((int)date('Y'));

        $this->render('dashboard/index', [
            'pageTitle'        => 'Dashboard',
            // Aliased for view compatibility
            'activeCases'      => $caseStats['ativo'] ?? ($caseStats['ativos'] ?? 0),
            'activeClients'    => $clientStats['active'] ?? 0,
            'financialSummary' => $financialStats,
            'overdueTasks'     => $taskStats['overdue'] ?? [],
            'upcomingTasks'    => $taskStats['upcoming'] ?? [],
            'overdueFinancial' => $financialStats['overdue_entries'] ?? [],
            'statusCounts'     => $casesByStatus ?? [],
            // Full data for other widgets
            'caseStats'        => $caseStats,
            'clientStats'      => $clientStats,
            'financialStats'   => $financialStats,
            'taskStats'        => $taskStats,
            'todayDeadlines'   => $todayDeadlines,
            'expiredDeadlines' => $expiredDeadlines,
            'upcomingDeadlines' => $upcomingDeadlines,
            'upcomingHearings' => $upcomingHearings,
//            'recentMovements'  => $recentMovements,
            'cnj_last_sync'    => $cnj_last_sync,
            'db_status'        => $db_status,
            'casesByStatus'    => $casesByStatus,
            'casesByArea'      => $casesByArea,
            'monthlyRevenue'   => $monthlyRevenue,
        ]);
    }

    public function apiStats(): void
    {
        $caseModel      = new LegalCase();
        $clientModel    = new Client();
        $financialModel = new FinancialEntry();
        $taskModel      = new Task();
        $hearingModel   = new Hearing();

        $this->json([
            'cases'    => $caseModel->getStats(),
            'clients'  => $clientModel->getStats(),
            'financial'=> $financialModel->getSummary(),
            'tasks'    => $taskModel->getStats(),
        ]);
    }

    public function apiCharts(): void
    {
        $svc = new \App\Services\DashboardStatsService();
        $this->json([
            'processes_by_month' => $svc->processesByMonth(12),
            'revenue_by_month'   => $svc->receivedByMonth(12),
            'cases_by_area'      => $svc->casesByArea(),
        ]);
    }

    public function apiAlerts(): void
    {
        $caseModel      = new LegalCase();
        $financialModel = new FinancialEntry();
        $taskModel      = new Task();

        $expired  = $caseModel->getExpiredDeadlines();
        $overdue  = $taskModel->getStats()['overdue'] ?? [];
        $finStats = $financialModel->getSummary();

        $alerts = [];
        if (!empty($expired)) {
            $alerts[] = ['type' => 'danger', 'message' => count($expired) . ' prazo(s) vencido(s)', 'link' => '/cases'];
        }
        if (!empty($overdue)) {
            $alerts[] = ['type' => 'warning', 'message' => count($overdue) . ' tarefa(s) atrasada(s)', 'link' => '/tasks'];
        }
        if (($finStats['total_vencido'] ?? 0) > 0) {
            $alerts[] = ['type' => 'warning', 'message' => 'Inadimplência: R$ ' . number_format((float)$finStats['total_vencido'], 2, ',', '.'), 'link' => '/financial'];
        }

        $this->json(['alerts' => $alerts, 'count' => count($alerts)]);
    }
}
