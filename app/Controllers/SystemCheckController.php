<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Services\SystemCheckService;

class SystemCheckController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new SystemCheckService();
    }

    private function requireAdmin(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->redirect('/login');
        }
        if (($user['role'] ?? '') !== 'admin') {
            Session::flash('error', 'Acesso restrito ao administrador.');
            $this->redirect('/dashboard');
        }
    }

    public function index(): void
    {
        $this->requireAdmin();
        $user = Session::get('user');

        $db = \Core\Database::getInstance();
        $runs = [];
        try {
            $guard = new \App\Services\SchemaGuardService();
            if ($guard->tableExists('system_check_runs')) {
                $stmt = $db->prepare("SELECT * FROM system_check_runs ORDER BY id DESC LIMIT 10");
                $stmt->execute();
                $runs = $stmt->fetchAll();
            }
        } catch (\Throwable $e) {}

        $this->render('admin/system_check', [
            'pageTitle'   => 'Saúde do Sistema',
            'currentUser' => $user,
            'runs'        => $runs,
        ]);
    }

    public function run(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $user = Session::get('user');

        try {
            $items = $this->service->run();
            $runId = $this->service->saveRun($items, (int)($user['id'] ?? 0));

            $errors   = count(array_filter($items, function($i) { return $i['status'] === 'error'; }));
            $warnings = count(array_filter($items, function($i) { return $i['status'] === 'warning'; }));

            Logger::audit("SystemCheck executado pelo usuário {$user['email']} — Erros: {$errors}, Avisos: {$warnings}");

            $this->json([
                'success'  => true,
                'run_id'   => $runId,
                'items'    => $items,
                'summary'  => ['errors' => $errors, 'warnings' => $warnings, 'total' => count($items)],
            ]);
        } catch (\Throwable $e) {
            Logger::error('SystemCheck run failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function repair(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();
        $user = Session::get('user');

        try {
            $result = $this->service->repair();
            Logger::audit("SystemCheck repair executado pelo usuário {$user['email']}");
            $this->json(['success' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            Logger::error('SystemCheck repair failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export(): void
    {
        $this->requireAdmin();
        $user = Session::get('user');

        try {
            $items = $this->service->run();
            $errors   = count(array_filter($items, function($i) { return $i['status'] === 'error'; }));
            $warnings = count(array_filter($items, function($i) { return $i['status'] === 'warning'; }));

            $report = [
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $user['email'] ?? 'unknown',
                'php_version'  => phpversion(),
                'summary'      => ['total' => count($items), 'errors' => $errors, 'warnings' => $warnings],
                'items'        => $items,
            ];

            $filename = 'system_check_' . date('Ymd_His') . '.json';
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
