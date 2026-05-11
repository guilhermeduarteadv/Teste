<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
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
        $recentMovements = (new \App\Models\LegalCase())->query(
            "SELECT m.*, c.numero_cnj FROM case_movements m JOIN cases c ON m.case_id = c.id ORDER BY m.data_movimento DESC LIMIT 10"
        );

        // CNJ status
        $cnj_last_sync = $db->query("SELECT valor FROM settings WHERE chave='cnj_last_sync' LIMIT 1")->fetchColumn();
        $db_status = true;
        try { Database::getInstance(); } catch (\Exception $e) { $db_status = false; }

        // Cases by status chart data
        $casesByStatus = $caseModel->getByStatus();

        // Monthly revenue chart
        $monthlyRevenue = $financialModel->getMonthlyRevenue((int)date('Y'));

        $this->render('dashboard/index', [
            'pageTitle'        => 'Dashboard',
            // Aliased for view compatibility
            'activeCases'      => $caseStats['ativo'] ?? 0,
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
            'recentMovements'  => $recentMovements,
            'cnj_last_sync'    => $cnj_last_sync,
            'db_status'        => $db_status,
            'casesByStatus'    => $casesByStatus,
            'monthlyRevenue'   => $monthlyRevenue,
        ]);
    }
}
