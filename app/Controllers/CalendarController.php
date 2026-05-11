<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use App\Models\Hearing;

class CalendarController extends Controller
{
    public function index(): void
    {
        $this->render('calendar/index', ['pageTitle' => 'Agenda & Calendário']);
    }

    public function events(): void
    {
        $start = $this->input('start', date('Y-m-01'));
        $end   = $this->input('end', date('Y-m-t'));

        $hearingModel = new Hearing();
        $hearings = $hearingModel->getCalendarEvents($start, $end);

        $db = Database::getInstance();

        // Deadlines as events
        $stmt = $db->prepare("SELECT d.*, c.numero_cnj FROM case_deadlines d JOIN cases c ON d.case_id = c.id WHERE d.data_final BETWEEN ? AND ? AND d.deleted_at IS NULL AND d.status = 'pendente'");
        $stmt->execute([$start, $end]);
        $deadlines = $stmt->fetchAll();

        $events = [];

        foreach ($hearings as $h) {
            $colors = ['conciliacao' => '#3788d8', 'instrucao' => '#e74c3c', 'julgamento' => '#8e44ad', 'reuniao_cliente' => '#27ae60', 'diligencia' => '#f39c12', 'compromisso' => '#16a085'];
            $events[] = [
                'id'          => 'h' . $h['id'],
                'title'       => '🏛 ' . $h['titulo'],
                'start'       => $h['data'] . 'T' . $h['hora'],
                'color'       => $colors[$h['tipo']] ?? '#3788d8',
                'tipo'        => 'hearing',
                'url'         => $h['case_id'] ? "/cases/{$h['case_id']}" : null,
                'extendedProps' => ['tipo' => $h['tipo'], 'local' => $h['local'] ?? ''],
            ];
        }

        foreach ($deadlines as $d) {
            $colors = ['fatal' => '#e74c3c', 'processual' => '#f39c12', 'interno' => '#3498db'];
            $events[] = [
                'id'    => 'd' . $d['id'],
                'title' => '⏰ ' . $d['descricao'],
                'start' => $d['data_final'],
                'allDay' => true,
                'color' => $colors[$d['tipo']] ?? '#f39c12',
                'tipo'  => 'deadline',
                'url'   => "/cases/{$d['case_id']}",
                'extendedProps' => ['tipo' => $d['tipo'], 'cnj' => $d['numero_cnj'] ?? ''],
            ];
        }

        $this->json($events);
    }
}
