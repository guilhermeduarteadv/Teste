<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\UserModel;
use App\Models\CaseModel;
use App\Services\CNJService;
use App\Helpers\DateHelper;
use App\Services\SystemLogService;

class ApiController extends Controller
{
    public function syncCnj(): void
    {
        $this->validateCsrf();

        $user = Session::get('user');
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Não autorizado.'], 401);
            return;
        }

        $oabNumber = trim($_POST['oab_number'] ?? $user['oab_number'] ?? '');
        $oabState  = trim($_POST['oab_state'] ?? $user['oab_state'] ?? '');
        $tribunais = $_POST['tribunais'] ?? [];

        if (empty($oabNumber) || empty($oabState)) {
            $this->json(['success' => false, 'message' => 'Número OAB e estado são obrigatórios.'], 422);
            return;
        }

        $cnjService = new CNJService();
        $result = $cnjService->searchProcessesByOAB($oabNumber, $oabState, is_array($tribunais) ? $tribunais : []);
        SystemLogService::log('cnj_sync', 'api', "Sincronização CNJ: OAB {$oabNumber}/{$oabState}");

        $this->json($result);
    }

    public function status(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Não autorizado.'], 401);
            return;
        }

        $apiConfig = require ROOT_PATH . '/config/api.php';
        $hasApiKey = !empty($apiConfig['cnj']['api_key']);

        $lastSync = null;
        try {
            $db = \Core\Database::getInstance();
            $stmt = $db->query("SELECT valor FROM settings WHERE chave = 'cnj_last_sync' LIMIT 1");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $lastSync = $row ? $row['valor'] : null;
        } catch (\Exception $e) {}

        $this->json([
            'success'   => true,
            'configured'=> $hasApiKey,
            'last_sync' => $lastSync,
            'tribunais' => $apiConfig['cnj']['tribunais'],
        ]);
    }

    public function calculateDeadline(): void
    {
        $this->validateCsrf();

        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $days      = (int)($_POST['days'] ?? 0);
        $tipo      = $_POST['tipo'] ?? 'processual';
        $state     = $_POST['state'] ?? '';

        if ($days <= 0) {
            $this->json(['success' => false, 'message' => 'Número de dias inválido.'], 422);
            return;
        }

        try {
            $result = DateHelper::calculateDeadline($startDate, $days, $tipo, $state);
            $daysUntil = DateHelper::daysUntil($result);
            $human     = DateHelper::humanDiff($result);

            $this->json([
                'success'          => true,
                'deadline'         => $result,
                'deadline_br'      => DateHelper::formatBr($result),
                'days_until'       => $daysUntil,
                'human_diff'       => $human,
                'is_overdue'       => $daysUntil < 0,
                'is_today'         => DateHelper::isToday($result),
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro ao calcular prazo: ' . $e->getMessage()], 500);
        }
    }
}
