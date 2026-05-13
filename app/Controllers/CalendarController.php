<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Logger;

class CalendarController extends Controller
{
    public function index(): void
    {
        $this->render('calendar/index', ['pageTitle' => 'Agenda & Calendário']);
    }

    public function events(): void
    {
        $this->jsonNoCache();

        try {
            $start = $this->normalizeDate($this->input('start', ''));
            $end   = $this->normalizeDate($this->input('end', ''));

            if ($start === '') {
                $start = '1900-01-01';
            }
            if ($end === '') {
                $end = '2100-12-31';
            }

            if ((string)$this->input('all', '1') === '1') {
                $start = '1900-01-01';
                $end   = '2100-12-31';
            }

            $db = Database::getInstance();
            $events = [];
            $errors = [];

            foreach ([
                'tasks'     => 'getTasksEvents',
                'hearings'  => 'getHearingsEvents',
                'deadlines' => 'getDeadlinesEvents',
                'financial' => 'getFinancialEvents',
            ] as $source => $method) {
                try {
                    $events = array_merge($events, $this->{$method}($db, $start, $end));
                } catch (\Throwable $e) {
                    $errors[] = $source . ': ' . $e->getMessage();
                    Logger::error('Calendar source failed [' . $source . ']: ' . $e->getMessage());
                }
            }

            usort($events, function ($a, $b) {
                return strcmp((string)($a['start'] ?? ''), (string)($b['start'] ?? ''));
            });

            if ((string)$this->input('debug', '0') === '1') {
                $this->json(['success' => true, 'count' => count($events), 'events' => $events, 'errors' => $errors]);
                return;
            }

            $this->json($events);
        } catch (\Throwable $e) {
            Logger::error('Calendar events failed: ' . $e->getMessage());
            $this->json(['error' => true, 'message' => 'Erro ao carregar eventos do calendário: ' . $e->getMessage()], 500);
        }
    }

    public function debug(): void
    {
        $this->jsonNoCache();

        try {
            $db = Database::getInstance();
            $tables = ['tasks', 'case_hearings', 'case_deadlines', 'financial_entries'];
            $out = [];

            foreach ($tables as $table) {
                $exists = $this->tableExists($db, $table);
                $row = ['exists' => $exists, 'count' => 0, 'columns' => [], 'samples' => []];

                if ($exists) {
                    $row['count'] = (int)$db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    $cols = $db->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
                    foreach ($cols as $col) {
                        $row['columns'][] = $col['Field'] ?? $col[0] ?? '';
                    }
                    try {
                        $row['samples'] = $db->query("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 10")->fetchAll();
                    } catch (\Throwable $e) {
                        $row['sample_error'] = $e->getMessage();
                    }
                }

                $out[$table] = $row;
            }

            $events = [];
            $errors = [];
            foreach (['tasks' => 'getTasksEvents', 'hearings' => 'getHearingsEvents', 'deadlines' => 'getDeadlinesEvents', 'financial' => 'getFinancialEvents'] as $source => $method) {
                try {
                    $part = $this->{$method}($db, '1900-01-01', '2100-12-31');
                    $out[$source . '_events_count'] = count($part);
                    $events = array_merge($events, $part);
                } catch (\Throwable $e) {
                    $errors[] = $source . ': ' . $e->getMessage();
                }
            }

            $this->json(['success' => true, 'events_count' => count($events), 'events' => $events, 'errors' => $errors, 'tables' => $out]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getTasksEvents($db, string $start, string $end): array
    {
        if (!$this->tableExists($db, 'tasks')) {
            return [];
        }

        $dateColumn = $this->firstExistingColumn($db, 'tasks', ['prazo', 'due_date', 'data_prazo', 'data_vencimento', 'deadline']);
        if ($dateColumn === null) {
            return [];
        }

        $titleColumn = $this->firstExistingColumn($db, 'tasks', ['title', 'titulo', 'nome', 'descricao']);
        $timeColumn = $this->firstExistingColumn($db, 'tasks', ['hora', 'horario', 'time']);
        $locationColumn = $this->firstExistingColumn($db, 'tasks', ['local', 'location', 'endereco']);
        $hasCaseJoin = $this->tableExists($db, 'cases') && $this->columnExists($db, 'tasks', 'case_id');

        $selectTitle = $titleColumn ? "t.`{$titleColumn}` AS calendar_title" : "'Tarefa' AS calendar_title";
        $selectTime = $timeColumn ? "t.`{$timeColumn}` AS calendar_time" : "NULL AS calendar_time";
        $selectLocal = $locationColumn ? "t.`{$locationColumn}` AS calendar_local" : "NULL AS calendar_local";
        $selectCase = $hasCaseJoin ? ', c.numero_cnj' : ', NULL AS numero_cnj';
        $joinCase = $hasCaseJoin ? ' LEFT JOIN cases c ON t.case_id = c.id ' : '';
        $deletedCondition = $this->deletedCondition($db, 'tasks', 't');

        // Importante: não filtramos por DATE() no SQL, porque em instalações antigas o campo
        // pode vir como texto ou com formato brasileiro. Coletamos e filtramos em PHP.
        $sql = "SELECT t.*, {$selectTitle}, {$selectTime}, {$selectLocal}, t.`{$dateColumn}` AS calendar_date {$selectCase}
                FROM tasks t {$joinCase}
                WHERE t.`{$dateColumn}` IS NOT NULL
                  {$deletedCondition}
                ORDER BY t.`{$dateColumn}` ASC, t.id ASC";

        $rows = $db->query($sql)->fetchAll();
        $events = [];

        foreach ($rows as $t) {
            $date = $this->normalizeDate($t['calendar_date'] ?? '');
            if (!$this->dateInRange($date, $start, $end)) {
                continue;
            }

            $status = $this->normalizeStatus($t['status'] ?? '');
            if (in_array($status, ['concluida', 'concluido', 'cancelada', 'cancelado'], true)) {
                continue;
            }

            $time = $this->normalizeTime($t['calendar_time'] ?? '');
            $title = trim((string)($t['calendar_title'] ?? ''));
            if ($title === '') {
                $title = 'Tarefa';
            }

            $events[] = [
                'id' => 'task-' . ($t['id'] ?? md5(json_encode($t))),
                'title' => '✅ ' . $title,
                'start' => $time ? ($date . 'T' . $time) : $date,
                'allDay' => $time ? false : true,
                'color' => '#1a56db',
                'url' => !empty($t['case_id']) ? $this->url('/cases/' . $t['case_id']) : $this->url('/tasks'),
                'extendedProps' => [
                    'categoria' => 'Tarefa',
                    'tipo' => $t['tipo'] ?? '',
                    'prioridade' => $t['prioridade'] ?? '',
                    'status' => $t['status'] ?? '',
                    'local' => $t['calendar_local'] ?? '',
                    'cnj' => $t['numero_cnj'] ?? ''
                ]
            ];
        }

        return $events;
    }

    private function getHearingsEvents($db, string $start, string $end): array
    {
        if (!$this->tableExists($db, 'case_hearings')) {
            return [];
        }

        $dateColumn = $this->firstExistingColumn($db, 'case_hearings', ['data', 'data_audiencia', 'date']);
        if ($dateColumn === null) {
            return [];
        }

        $titleColumn = $this->firstExistingColumn($db, 'case_hearings', ['titulo', 'title', 'tipo', 'descricao']);
        $timeColumn = $this->firstExistingColumn($db, 'case_hearings', ['hora', 'time']);
        $hasCaseJoin = $this->tableExists($db, 'cases') && $this->columnExists($db, 'case_hearings', 'case_id');
        $selectTitle = $titleColumn ? "h.`{$titleColumn}` AS calendar_title" : "'Audiência/compromisso' AS calendar_title";
        $selectTime = $timeColumn ? "h.`{$timeColumn}` AS calendar_time" : "NULL AS calendar_time";
        $selectCase = $hasCaseJoin ? ', c.numero_cnj' : ', NULL AS numero_cnj';
        $joinCase = $hasCaseJoin ? ' LEFT JOIN cases c ON h.case_id = c.id ' : '';
        $deletedCondition = $this->deletedCondition($db, 'case_hearings', 'h');

        $sql = "SELECT h.*, {$selectTitle}, {$selectTime}, h.`{$dateColumn}` AS calendar_date {$selectCase}
                FROM case_hearings h {$joinCase}
                WHERE h.`{$dateColumn}` IS NOT NULL {$deletedCondition}
                ORDER BY h.`{$dateColumn}` ASC, h.id ASC";

        $rows = $db->query($sql)->fetchAll();
        $events = [];

        foreach ($rows as $h) {
            $date = $this->normalizeDate($h['calendar_date'] ?? '');
            if (!$this->dateInRange($date, $start, $end)) {
                continue;
            }
            $status = $this->normalizeStatus($h['status'] ?? '');
            if (in_array($status, ['cancelada', 'cancelado'], true)) {
                continue;
            }
            $time = $this->normalizeTime($h['calendar_time'] ?? '') ?: '09:00:00';
            $events[] = [
                'id' => 'hearing-' . ($h['id'] ?? md5(json_encode($h))),
                'title' => '🏛 ' . (($h['calendar_title'] ?? '') ?: 'Audiência/compromisso'),
                'start' => $date . 'T' . $time,
                'allDay' => false,
                'color' => '#3788d8',
                'url' => !empty($h['case_id']) ? $this->url('/cases/' . $h['case_id']) : null,
                'extendedProps' => ['categoria' => 'Audiência/compromisso', 'tipo' => $h['tipo'] ?? '', 'local' => $h['local'] ?? '', 'cnj' => $h['numero_cnj'] ?? '']
            ];
        }

        return $events;
    }

    private function getDeadlinesEvents($db, string $start, string $end): array
    {
        if (!$this->tableExists($db, 'case_deadlines')) {
            return [];
        }

        $dateColumn = $this->firstExistingColumn($db, 'case_deadlines', ['data_final', 'prazo', 'data_prazo', 'due_date']);
        if ($dateColumn === null) {
            return [];
        }

        $titleColumn = $this->firstExistingColumn($db, 'case_deadlines', ['descricao', 'title', 'titulo', 'nome']);
        $hasCaseJoin = $this->tableExists($db, 'cases') && $this->columnExists($db, 'case_deadlines', 'case_id');
        $selectTitle = $titleColumn ? "d.`{$titleColumn}` AS calendar_title" : "'Prazo' AS calendar_title";
        $selectCase = $hasCaseJoin ? ', c.numero_cnj' : ', NULL AS numero_cnj';
        $joinCase = $hasCaseJoin ? ' LEFT JOIN cases c ON d.case_id = c.id ' : '';
        $deletedCondition = $this->deletedCondition($db, 'case_deadlines', 'd');

        $sql = "SELECT d.*, {$selectTitle}, d.`{$dateColumn}` AS calendar_date {$selectCase}
                FROM case_deadlines d {$joinCase}
                WHERE d.`{$dateColumn}` IS NOT NULL {$deletedCondition}
                ORDER BY d.`{$dateColumn}` ASC, d.id ASC";

        $rows = $db->query($sql)->fetchAll();
        $events = [];
        foreach ($rows as $d) {
            $date = $this->normalizeDate($d['calendar_date'] ?? '');
            if (!$this->dateInRange($date, $start, $end)) {
                continue;
            }
            $status = $this->normalizeStatus($d['status'] ?? '');
            if (in_array($status, ['concluido', 'concluida', 'cancelado', 'cancelada'], true)) {
                continue;
            }
            $events[] = [
                'id' => 'deadline-' . ($d['id'] ?? md5(json_encode($d))),
                'title' => '⏰ ' . (($d['calendar_title'] ?? '') ?: 'Prazo'),
                'start' => $date,
                'allDay' => true,
                'color' => '#f39c12',
                'url' => !empty($d['case_id']) ? $this->url('/cases/' . $d['case_id']) : null,
                'extendedProps' => ['categoria' => 'Prazo', 'tipo' => $d['tipo'] ?? '', 'cnj' => $d['numero_cnj'] ?? '']
            ];
        }
        return $events;
    }

    private function getFinancialEvents($db, string $start, string $end): array
    {
        if (!$this->tableExists($db, 'financial_entries')) {
            return [];
        }

        $dateColumn = $this->firstExistingColumn($db, 'financial_entries', ['vencimento', 'data_vencimento', 'due_date', 'data']);
        if ($dateColumn === null) {
            return [];
        }

        $titleColumn = $this->firstExistingColumn($db, 'financial_entries', ['descricao', 'title', 'titulo']);
        $hasCaseJoin = $this->tableExists($db, 'cases') && $this->columnExists($db, 'financial_entries', 'case_id');
        $selectTitle = $titleColumn ? "fe.`{$titleColumn}` AS calendar_title" : "'Financeiro' AS calendar_title";
        $selectCase = $hasCaseJoin ? ', c.numero_cnj' : ', NULL AS numero_cnj';
        $joinCase = $hasCaseJoin ? ' LEFT JOIN cases c ON fe.case_id = c.id ' : '';
        $deletedCondition = $this->deletedCondition($db, 'financial_entries', 'fe');

        $sql = "SELECT fe.*, {$selectTitle}, fe.`{$dateColumn}` AS calendar_date {$selectCase}
                FROM financial_entries fe {$joinCase}
                WHERE fe.`{$dateColumn}` IS NOT NULL {$deletedCondition}
                ORDER BY fe.`{$dateColumn}` ASC, fe.id ASC";

        $rows = $db->query($sql)->fetchAll();
        $events = [];
        foreach ($rows as $f) {
            $date = $this->normalizeDate($f['calendar_date'] ?? '');
            if (!$this->dateInRange($date, $start, $end)) {
                continue;
            }
            $status = $this->normalizeStatus($f['status'] ?? '');
            if (in_array($status, ['pago', 'recebido', 'cancelado', 'cancelada'], true)) {
                continue;
            }
            $events[] = [
                'id' => 'financial-' . ($f['id'] ?? md5(json_encode($f))),
                'title' => '💰 ' . (($f['calendar_title'] ?? '') ?: 'Financeiro'),
                'start' => $date,
                'allDay' => true,
                'color' => '#059669',
                'url' => $this->url('/financial'),
                'extendedProps' => ['categoria' => 'Financeiro', 'valor' => $f['valor'] ?? '', 'cnj' => $f['numero_cnj'] ?? '']
            ];
        }
        return $events;
    }

    private function normalizeDate($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        $ts = strtotime($value);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        return '';
    }

    private function normalizeTime($value): string
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '00:00:00' || $value === '00:00') {
            return '';
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':' . ($m[3] ?? '00');
        }
        return '';
    }

    private function normalizeStatus($value): string
    {
        $value = strtolower(trim((string)$value));
        $value = str_replace([' ', '-'], '_', $value);
        $map = [
            'em_andamento' => 'em_andamento',
            'em andamento' => 'em_andamento',
            'concluída' => 'concluida',
            'concluído' => 'concluido',
            'concluida' => 'concluida',
            'concluido' => 'concluido',
        ];
        return $map[$value] ?? $value;
    }

    private function dateInRange(string $date, string $start, string $end): bool
    {
        if ($date === '') {
            return false;
        }
        return $date >= $start && $date <= $end;
    }

    private function tableExists($db, string $table): bool
    {
        try {
            // Em algumas instalações MySQL/PDO com ATTR_EMULATE_PREPARES=false,
            // SHOW TABLES LIKE ? pode retornar vazio mesmo com a tabela existente.
            // Por isso usamos information_schema com DATABASE() como fonte principal.
            $stmt = $db->prepare(
                "SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?"
            );
            $stmt->execute([$table]);
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }

            // Fallback simples para ambientes antigos.
            $quoted = $db->quote($table);
            $fallback = $db->query("SHOW TABLES LIKE {$quoted}");
            return (bool)$fallback->fetchColumn();
        } catch (\Throwable $e) {
            Logger::error('Calendar tableExists failed for ' . $table . ': ' . $e->getMessage());
            return false;
        }
    }

    private function columnExists($db, string $table, string $column): bool
    {
        try {
            $stmt = $db->prepare(
                "SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }

            $tableSafe = str_replace('`', '``', $table);
            $quotedColumn = $db->quote($column);
            $fallback = $db->query("SHOW COLUMNS FROM `{$tableSafe}` LIKE {$quotedColumn}");
            return (bool)$fallback->fetchColumn();
        } catch (\Throwable $e) {
            Logger::error('Calendar columnExists failed for ' . $table . '.' . $column . ': ' . $e->getMessage());
            return false;
        }
    }

    private function firstExistingColumn($db, string $table, array $columns)
    {
        foreach ($columns as $column) {
            if ($this->columnExists($db, $table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function deletedCondition($db, string $table, string $alias): string
    {
        if ($this->columnExists($db, $table, 'deleted_at')) {
            return " AND ({$alias}.deleted_at IS NULL OR {$alias}.deleted_at = '0000-00-00 00:00:00')";
        }
        return '';
    }

    private function url(string $path): string
    {
        $base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
        return rtrim($base, '/') . $path;
    }

    private function jsonNoCache(): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
    }
}
