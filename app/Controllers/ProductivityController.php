<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;
use PDO;

class ProductivityController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $periodStart = $this->input('period_start', date('Y-m-01'));
        $periodEnd   = $this->input('period_end', date('Y-m-d'));

        $users = $this->db->query(
            "SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $stats = $this->buildStats($periodStart, $periodEnd);

        $this->render('productivity/index', [
            'pageTitle'   => 'Produtividade',
            'users'       => $users,
            'stats'       => $stats,
            'periodStart' => $periodStart,
            'periodEnd'   => $periodEnd,
            'csrf_token'  => Session::csrfToken(),
        ]);
    }

    public function apiStats(): void
    {
        $periodStart = $_GET['period_start'] ?? date('Y-m-01');
        $periodEnd   = $_GET['period_end'] ?? date('Y-m-d');

        $stats = $this->buildStats($periodStart, $periodEnd);
        $this->json(['success' => true, 'data' => $stats]);
    }

    private function buildStats(string $periodStart, string $periodEnd): array
    {
        $stats = [];

        // Tarefas concluídas por usuário
        try {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.name,
                        COUNT(t.id) AS total_tasks,
                        SUM(CASE WHEN t.status = 'concluida' OR t.status = 'done' OR t.status = 'completed' THEN 1 ELSE 0 END) AS done_tasks
                 FROM users u
                 LEFT JOIN tasks t ON (t.responsavel_id = u.id OR t.responsible_id = u.id)
                     AND t.deleted_at IS NULL
                     AND (t.prazo BETWEEN ? AND ? OR t.due_date BETWEEN ? AND ?)
                 WHERE u.deleted_at IS NULL
                 GROUP BY u.id, u.name
                 ORDER BY done_tasks DESC"
            );
            $stmt->execute([$periodStart, $periodEnd, $periodStart, $periodEnd]);
            $stats['tasks_by_user'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $stats['tasks_by_user'] = [];
        }

        // Horas registradas por usuário + horas faturáveis
        try {
            $stmt = $this->db->prepare(
                "SELECT u.id, u.name,
                        COALESCE(SUM(ts.minutos), 0) AS total_minutos,
                        COALESCE(SUM(CASE WHEN ts.faturavel = 1 THEN ts.minutos ELSE 0 END), 0) AS minutos_faturavel,
                        COALESCE(SUM(CASE WHEN ts.faturavel = 1 THEN (ts.minutos / 60) * COALESCE(ts.valor_hora, 0) ELSE 0 END), 0) AS valor_faturavel
                 FROM users u
                 LEFT JOIN timesheets ts ON ts.user_id = u.id
                     AND ts.deleted_at IS NULL
                     AND ts.data BETWEEN ? AND ?
                 WHERE u.deleted_at IS NULL
                 GROUP BY u.id, u.name
                 ORDER BY total_minutos DESC"
            );
            $stmt->execute([$periodStart, $periodEnd]);
            $stats['hours_by_user'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $stats['hours_by_user'] = [];
        }

        // Horas por cliente
        try {
            $clientCol = $this->columnExists('timesheets', 'client_id') ? 'ts.client_id' : 'NULL';
            $stmt = $this->db->prepare(
                "SELECT cl.name AS client_name,
                        COALESCE(SUM(ts.minutos), 0) AS total_minutos,
                        COALESCE(SUM(CASE WHEN ts.faturavel = 1 THEN ts.minutos ELSE 0 END), 0) AS minutos_faturavel
                 FROM timesheets ts
                 LEFT JOIN clients cl ON cl.id = ts.client_id
                 WHERE ts.deleted_at IS NULL
                   AND ts.data BETWEEN ? AND ?
                   AND ts.client_id IS NOT NULL
                 GROUP BY ts.client_id, cl.name
                 ORDER BY total_minutos DESC
                 LIMIT 20"
            );
            $stmt->execute([$periodStart, $periodEnd]);
            $stats['hours_by_client'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $stats['hours_by_client'] = [];
        }

        // Horas por processo
        try {
            $stmt = $this->db->prepare(
                "SELECT c.numero_cnj, c.titulo,
                        COALESCE(SUM(ts.minutos), 0) AS total_minutos
                 FROM timesheets ts
                 LEFT JOIN cases c ON c.id = ts.case_id
                 WHERE ts.deleted_at IS NULL
                   AND ts.data BETWEEN ? AND ?
                   AND ts.case_id IS NOT NULL
                 GROUP BY ts.case_id, c.numero_cnj, c.titulo
                 ORDER BY total_minutos DESC
                 LIMIT 20"
            );
            $stmt->execute([$periodStart, $periodEnd]);
            $stats['hours_by_case'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $stats['hours_by_case'] = [];
        }

        // Prazos cumpridos vs. vencidos
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    SUM(CASE WHEN (status = 'concluido' OR cumprido = 1) AND data_final >= NOW() THEN 1 ELSE 0 END) AS cumpridos,
                    SUM(CASE WHEN (status = 'vencido' OR (data_final < NOW() AND (status IS NULL OR status != 'concluido'))) THEN 1 ELSE 0 END) AS vencidos,
                    COUNT(*) AS total
                 FROM case_deadlines
                 WHERE deleted_at IS NULL
                   AND data_final BETWEEN ? AND ?"
            );
            $stmt->execute([$periodStart, $periodEnd]);
            $stats['deadlines'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cumpridos' => 0, 'vencidos' => 0, 'total' => 0];
        } catch (\Throwable $e) {
            $stats['deadlines'] = ['cumpridos' => 0, 'vencidos' => 0, 'total' => 0];
        }

        // Total geral de horas faturáveis
        try {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(minutos), 0) AS total_min,
                        COALESCE(SUM(CASE WHEN faturavel = 1 THEN minutos ELSE 0 END), 0) AS fat_min,
                        COALESCE(SUM(CASE WHEN faturavel = 1 THEN (minutos/60)*COALESCE(valor_hora,0) ELSE 0 END), 0) AS valor_fat
                 FROM timesheets
                 WHERE deleted_at IS NULL AND data BETWEEN ? AND ?"
            );
            $stmt->execute([$periodStart, $periodEnd]);
            $stats['summary'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_min' => 0, 'fat_min' => 0, 'valor_fat' => 0];
        } catch (\Throwable $e) {
            $stats['summary'] = ['total_min' => 0, 'fat_min' => 0, 'valor_fat' => 0];
        }

        return $stats;
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
