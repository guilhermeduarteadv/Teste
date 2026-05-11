<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use PDO;

class ReportController extends Controller
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $this->render('reports.index', [
            'title'      => 'Relatórios - JurisControl',
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    public function cases(): void
    {
        $dateFrom = $this->input('date_from', date('Y-01-01'));
        $dateTo   = $this->input('date_to', date('Y-m-d'));
        $status   = $this->input('status', '');

        $params = [$dateFrom, $dateTo];
        $where  = "c.deleted_at IS NULL AND DATE(c.created_at) BETWEEN ? AND ?";
        if ($status) {
            $where .= " AND c.status = ?";
            $params[] = $status;
        }

        $cases = $this->db->prepare(
            "SELECT c.*, u.name AS responsavel_name,
                    GROUP_CONCAT(cl.name SEPARATOR ', ') AS clientes
             FROM cases c
             LEFT JOIN users u ON c.responsavel_id = u.id
             LEFT JOIN case_clients cc ON c.id = cc.case_id
             LEFT JOIN clients cl ON cc.client_id = cl.id
             WHERE {$where}
             GROUP BY c.id
             ORDER BY c.created_at DESC"
        );
        $cases->execute($params);
        $caseList = $cases->fetchAll(PDO::FETCH_ASSOC);

        // Summary by status
        $statusSummary = [];
        foreach ($caseList as $c) {
            $statusSummary[$c['status']] = ($statusSummary[$c['status']] ?? 0) + 1;
        }

        // Summary by tribunal
        $tribunalSummary = [];
        foreach ($caseList as $c) {
            if ($c['tribunal']) {
                $tribunalSummary[$c['tribunal']] = ($tribunalSummary[$c['tribunal']] ?? 0) + 1;
            }
        }

        $this->render('reports.cases', [
            'title'          => 'Relatório de Processos - JurisControl',
            'cases'          => $caseList,
            'statusSummary'  => $statusSummary,
            'tribunalSummary'=> $tribunalSummary,
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'filter_status'  => $status,
            'csrf_token'     => Session::csrfToken(),
        ]);
    }

    public function financial(): void
    {
        $dateFrom = $this->input('date_from', date('Y-01-01'));
        $dateTo   = $this->input('date_to', date('Y-m-d'));
        $status   = $this->input('status', '');
        $tipo     = $this->input('tipo', '');

        $params = [$dateFrom, $dateTo];
        $where  = "fe.deleted_at IS NULL AND fe.vencimento BETWEEN ? AND ?";
        if ($status) {
            $where .= " AND fe.status = ?";
            $params[] = $status;
        }
        if ($tipo) {
            $where .= " AND fe.tipo = ?";
            $params[] = $tipo;
        }

        $stmt = $this->db->prepare(
            "SELECT fe.*, cl.name AS client_name, c.numero_cnj
             FROM financial_entries fe
             LEFT JOIN clients cl ON fe.client_id = cl.id
             LEFT JOIN cases c ON fe.case_id = c.id
             WHERE {$where}
             ORDER BY fe.vencimento DESC"
        );
        $stmt->execute($params);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summary
        $totalRecebido = 0;
        $totalPendente = 0;
        $totalVencido  = 0;
        $byTipo = [];
        foreach ($entries as $e) {
            if ($e['status'] === 'pago') $totalRecebido += (float)$e['valor'];
            elseif ($e['status'] === 'pendente') $totalPendente += (float)$e['valor'];
            elseif ($e['status'] === 'vencido') $totalVencido += (float)$e['valor'];
            $byTipo[$e['tipo']] = ($byTipo[$e['tipo']] ?? 0) + (float)$e['valor'];
        }

        $this->render('reports.financial', [
            'title'          => 'Relatório Financeiro - JurisControl',
            'entries'        => $entries,
            'totalRecebido'  => $totalRecebido,
            'totalPendente'  => $totalPendente,
            'totalVencido'   => $totalVencido,
            'byTipo'         => $byTipo,
            'date_from'      => $dateFrom,
            'date_to'        => $dateTo,
            'filter_status'  => $status,
            'filter_tipo'    => $tipo,
            'csrf_token'     => Session::csrfToken(),
        ]);
    }

    public function clients(): void
    {
        $stmt = $this->db->prepare(
            "SELECT cl.*,
                    COUNT(DISTINCT cc.case_id) AS total_processos,
                    COUNT(DISTINCT fe.id) AS total_lancamentos,
                    SUM(CASE WHEN fe.status = 'pago' THEN fe.valor ELSE 0 END) AS total_pago,
                    SUM(CASE WHEN fe.status = 'pendente' THEN fe.valor ELSE 0 END) AS total_pendente
             FROM clients cl
             LEFT JOIN case_clients cc ON cl.id = cc.client_id
             LEFT JOIN financial_entries fe ON cl.id = fe.client_id AND fe.deleted_at IS NULL
             WHERE cl.deleted_at IS NULL
             GROUP BY cl.id
             ORDER BY cl.name ASC"
        );
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('reports.clients', [
            'title'      => 'Relatório de Clientes - JurisControl',
            'clients'    => $clients,
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    public function tasks(): void
    {
        $dateFrom = $this->input('date_from', date('Y-01-01'));
        $dateTo   = $this->input('date_to', date('Y-m-d'));
        $status   = $this->input('status', '');

        $params = [$dateFrom, $dateTo];
        $where  = "t.deleted_at IS NULL AND DATE(t.created_at) BETWEEN ? AND ?";
        if ($status) {
            $where .= " AND t.status = ?";
            $params[] = $status;
        }

        $stmt = $this->db->prepare(
            "SELECT t.*, u.name AS responsavel_name, c.numero_cnj, cl.name AS client_name
             FROM tasks t
             LEFT JOIN users u ON t.responsavel_id = u.id
             LEFT JOIN cases c ON t.case_id = c.id
             LEFT JOIN clients cl ON t.client_id = cl.id
             WHERE {$where}
             ORDER BY t.prazo ASC"
        );
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $bystatus = [];
        foreach ($tasks as $t) {
            $bystatus[$t['status']] = ($bystatus[$t['status']] ?? 0) + 1;
        }

        $this->render('reports.tasks', [
            'title'       => 'Relatório de Tarefas - JurisControl',
            'tasks'       => $tasks,
            'byStatus'    => $bystatus,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'filter_status' => $status,
            'csrf_token'  => Session::csrfToken(),
        ]);
    }

    public function exportPdf(): void
    {
        // In production: use mPDF, TCPDF, or Dompdf
        $type = $this->input('type', 'cases');
        http_response_code(501);
        $this->json([
            'success' => false,
            'message' => 'Exportação PDF requer instalação de biblioteca (mPDF/TCPDF). Configure no ambiente de produção.',
        ]);
    }

    public function exportExcel(): void
    {
        // In production: use PhpSpreadsheet
        $type = $this->input('type', 'cases');
        http_response_code(501);
        $this->json([
            'success' => false,
            'message' => 'Exportação Excel requer instalação de biblioteca (PhpSpreadsheet). Configure no ambiente de produção.',
        ]);
    }
}
