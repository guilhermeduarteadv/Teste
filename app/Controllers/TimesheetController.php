<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;
use App\Models\LegalCase;
use App\Models\Client;
use PDO;

class TimesheetController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();

        // Filtros
        $userId   = $this->input('user_id', '');
        $caseId   = $this->input('case_id', '');
        $dateFrom = $this->input('date_from', '');
        $dateTo   = $this->input('date_to', '');

        $where  = "ts.deleted_at IS NULL";
        $params = [];

        if ($userId !== '') {
            $where .= " AND ts.user_id = ?";
            $params[] = (int)$userId;
        }
        if ($caseId !== '') {
            $where .= " AND ts.case_id = ?";
            $params[] = (int)$caseId;
        }
        if ($dateFrom !== '') {
            $where .= " AND ts.data >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where .= " AND ts.data <= ?";
            $params[] = $dateTo;
        }

        $sql = "SELECT ts.*, c.numero_cnj, cl.name AS client_name, u.name AS user_name
                FROM timesheets ts
                LEFT JOIN cases c  ON ts.case_id  = c.id
                LEFT JOIN clients cl ON ts.client_id = cl.id
                LEFT JOIN users u  ON ts.user_id  = u.id
                WHERE {$where}
                ORDER BY ts.data DESC, ts.id DESC
                LIMIT 200";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = $db->query(
            "SELECT COALESCE(SUM(minutos),0) AS minutos,
                    COALESCE(SUM((minutos/60)*COALESCE(valor_hora,0)),0) AS valor,
                    COALESCE(SUM(CASE WHEN faturavel=1 THEN minutos ELSE 0 END),0) AS minutos_faturavel
             FROM timesheets WHERE deleted_at IS NULL"
        )->fetch(PDO::FETCH_ASSOC);

        $users = $db->query("SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('timesheets/index', [
            'pageTitle'   => 'Timesheet',
            'entries'     => $rows,
            'summary'     => $summary,
            'users'       => $users,
            'cases'       => (new LegalCase())->findAll([], 'numero_cnj ASC'),
            'clients'     => (new Client())->findAll(['status' => 'active'], 'name ASC'),
            'filter_user' => $userId,
            'filter_case' => $caseId,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $caseId   = (int)$this->input('case_id', '0') ?: null;
        $clientId = (int)$this->input('client_id', '0') ?: null;
        $data     = $this->input('data', date('Y-m-d'));
        $inicio   = $this->input('inicio', '') ?: null;
        $fim      = $this->input('fim', '') ?: null;
        $minutos  = (int)$this->input('minutos', '0');

        if ($minutos <= 0 && $inicio && $fim) {
            $minutos = (int)max(0, (strtotime($fim) - strtotime($inicio)) / 60);
        }

        $descricao = $this->input('descricao', '');
        if ($descricao === '') {
            $this->json(['success' => false, 'message' => 'Descrição é obrigatória.']);
            return;
        }

        $valorHora = (float)str_replace(['.', ','], ['', '.'], $this->input('valor_hora', '0'));
        $faturavel = isset($_POST['faturavel']) ? 1 : 0;

        $stmt = Database::getInstance()->prepare(
            "INSERT INTO timesheets
                (case_id, client_id, user_id, data, inicio, fim, minutos, descricao, atividade, valor_hora, faturavel, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())"
        );
        $stmt->execute([
            $caseId,
            $clientId,
            Session::get('user_id'),
            $data,
            $inicio,
            $fim,
            $minutos,
            $descricao,
            $this->input('atividade', ''),
            $valorHora,
            $faturavel,
        ]);

        $this->json(['success' => true, 'message' => 'Hora lançada com sucesso.']);
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        Database::getInstance()->prepare(
            "UPDATE timesheets SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([(int)$id]);
        $this->json(['success' => true, 'message' => 'Lançamento excluído.']);
    }
}
