<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use Core\Database;
use App\Services\DeadlineCalculatorService;
use App\Services\SystemLogService;
use PDO;

class DeadlineController extends Controller
{
    private $db;
    private $calculator;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->calculator = new DeadlineCalculatorService();
    }

    /**
     * Lista todos os prazos com joins em cases.
     */
    public function index(): void
    {
        $filter = trim($_GET['filter'] ?? '');
        $caseId = (int)($_GET['case_id'] ?? 0);

        $where = ['d.deleted_at IS NULL'];
        $params = [];

        if ($caseId > 0) {
            $where[] = 'd.case_id = ?';
            $params[] = $caseId;
        }

        if ($filter !== '') {
            $where[] = '(c.numero_cnj LIKE ? OR c.assunto LIKE ? OR d.title LIKE ? OR d.tipo LIKE ?)';
            $like = '%' . $filter . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT d.*,
                    c.numero_cnj, c.assunto AS case_assunto,
                    u.name AS confirmed_by_name
             FROM case_deadlines d
             LEFT JOIN cases c ON c.id = d.case_id
             LEFT JOIN users u ON u.id = d.checked_by
             WHERE {$whereStr}
             ORDER BY d.data_final ASC, d.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($params);
        $deadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Casos para select
        $casesStmt = $this->db->prepare(
            "SELECT id, numero_cnj, assunto FROM cases WHERE deleted_at IS NULL ORDER BY numero_cnj ASC, assunto ASC LIMIT 500"
        );
        $casesStmt->execute();
        $cases = $casesStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('deadlines/index', [
            'pageTitle' => 'Prazos Processuais',
            'deadlines' => $deadlines,
            'cases'     => $cases,
            'filter'    => $filter,
            'caseId'    => $caseId,
        ]);
    }

    /**
     * Cria um novo prazo com calculated_at = NOW().
     */
    public function store(): void
    {
        $this->validateCsrf();

        $caseId = (int)($_POST['case_id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');

        if ($caseId <= 0 || $title === '') {
            Session::flash('error', 'Processo e título são obrigatórios.');
            $this->redirect('/deadlines');
        }

        $startDate      = !empty($_POST['start_count_at']) ? $_POST['start_count_at'] : null;
        $days           = isset($_POST['prazo_dias']) && $_POST['prazo_dias'] !== '' ? (int)$_POST['prazo_dias'] : null;
        $businessDays   = !empty($_POST['business_days']) ? 1 : 0;
        $calcMethod     = $this->input('calculation_method', 'corridos');
        $state          = $this->input('state', '');
        $dataFinal      = '';

        if ($startDate && $days !== null) {
            try {
                $dataFinal = $this->calculator->calculate(
                    $startDate,
                    $days,
                    (bool)$businessDays,
                    $state
                );
            } catch (\Throwable $e) {
                $dataFinal = '';
            }
        }

        if (empty($dataFinal) && !empty($_POST['data_final'])) {
            $dataFinal = $_POST['data_final'];
        }

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO case_deadlines
                (case_id, title, tipo, data_final, prazo, prazo_dias,
                 business_days, start_count_at, calculation_method,
                 calculated_at, source_type, source_id,
                 observacoes, created_by, confirmado, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)"
        );
        $stmt->execute([
            $caseId,
            $title,
            $title,
            $dataFinal ?: null,
            $dataFinal ?: null,
            $days,
            $businessDays,
            $startDate,
            $calcMethod,
            $now,
            $this->input('source_type', '') ?: null,
            !empty($_POST['source_id']) ? (int)$_POST['source_id'] : null,
            $this->input('observacoes', '') ?: null,
            Session::get('user_id'),
            $now,
            $now,
        ]);

        $newId = (int)$this->db->lastInsertId();
        SystemLogService::create('deadlines', 'case_deadline', $newId, "Prazo cadastrado: {$title} — processo #{$caseId}");
        Logger::audit("Prazo cadastrado ID #{$newId} para processo #{$caseId}");

        Session::flash('success', 'Prazo cadastrado com sucesso!');
        $this->redirect('/deadlines');
    }

    /**
     * Confirma/confere um prazo — seta checked_by e checked_at.
     */
    public function confirm(string $id): void
    {
        $this->validateCsrf();

        $stmt = $this->db->prepare("SELECT * FROM case_deadlines WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([(int)$id]);
        $deadline = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$deadline) {
            Session::flash('error', 'Prazo não encontrado.');
            $this->redirect('/deadlines');
        }

        $now    = date('Y-m-d H:i:s');
        $userId = Session::get('user_id');

        $stmt = $this->db->prepare(
            "UPDATE case_deadlines SET confirmado = 1, confirmado_por = ?, checked_by = ?, checked_at = ?, updated_at = ? WHERE id = ?"
        );
        $stmt->execute([$userId, $userId, $now, $now, (int)$id]);

        SystemLogService::log('confirm', 'deadlines', "Prazo #{$id} conferido pelo usuário #{$userId}", 'case_deadline', (int)$id);
        Logger::audit("Prazo #{$id} conferido");

        Session::flash('success', 'Prazo marcado como conferido!');
        $this->redirect('/deadlines');
    }

    /**
     * API JSON — recebe start_date, days, business_days, state → retorna end_date.
     */
    public function calculate(): void
    {
        $startDate    = trim($_POST['start_date'] ?? $_GET['start_date'] ?? '');
        $days         = (int)($_POST['days'] ?? $_GET['days'] ?? 0);
        $businessDays = !empty($_POST['business_days'] ?? $_GET['business_days'] ?? '');
        $state        = trim($_POST['state'] ?? $_GET['state'] ?? '');

        if ($startDate === '' || $days <= 0) {
            $this->json(['error' => 'Parâmetros inválidos: start_date e days são obrigatórios.'], 400);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $this->json(['error' => 'Formato de data inválido. Use Y-m-d.'], 400);
        }

        try {
            $endDate = $this->calculator->calculate($startDate, $days, $businessDays, $state);
            $holidays = $this->calculator->getHolidays($startDate, $endDate, $state);

            $this->json([
                'start_date'    => $startDate,
                'days'          => $days,
                'business_days' => $businessDays,
                'state'         => $state,
                'end_date'      => $endDate,
                'end_date_br'   => date('d/m/Y', strtotime($endDate)),
                'holidays_count'=> count($holidays),
                'holidays'      => array_map(function ($h) {
                    return ['date' => $h['date'], 'name' => $h['name']];
                }, $holidays),
            ]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Erro ao calcular prazo: ' . $e->getMessage()], 500);
        }
    }
}
