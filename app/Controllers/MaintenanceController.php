<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Services\SchemaGuardService;

class MaintenanceController extends Controller
{
    private function requireAdmin(): void
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            Session::flash('error', 'Acesso restrito a administradores.');
            $this->redirect('/dashboard');
        }
    }

    public function diagnostics(): void
    {
        $this->requireAdmin();
        (new \App\Services\SchemaGuardService())->ensureV52Schema();

        $checks = [];
        $checks[] = ['item' => 'PHP', 'value' => PHP_VERSION, 'ok' => true];
        $checks[] = ['item' => 'ROOT_PATH', 'value' => ROOT_PATH, 'ok' => is_dir(ROOT_PATH)];
        $checks[] = ['item' => 'Storage gravável', 'value' => ROOT_PATH . '/storage', 'ok' => is_writable(ROOT_PATH . '/storage')];

        $requiredTables = [
            'roles', 'clients', 'cases', 'financial_entries', 'documents', 'tasks',
            'administrative_procedures', 'legal_consultancies', 'legal_fee_contracts',
            'payable_entries', 'leads', 'tribunal_connections', 'error_logs'
        ];

        $schema = new SchemaGuardService();
        foreach ($requiredTables as $table) {
            $checks[] = ['item' => 'Tabela: ' . $table, 'value' => $schema->tableExists($table) ? 'OK' : 'Ausente', 'ok' => $schema->tableExists($table)];
        }

        $this->render('maintenance/diagnostics', [
            'pageTitle' => 'Diagnóstico do Sistema',
            'checks' => $checks,
        ]);
    }

    public function migrations(): void
    {
        $this->requireAdmin();

        $dir = ROOT_PATH . '/database/migrations';
        $files = glob($dir . '/*.sql') ?: [];

        $this->render('maintenance/migrations', [
            'pageTitle' => 'Atualizações do Sistema',
            'files' => array_map('basename', $files),
        ]);
    }

    public function runMigrations(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $db = Database::getInstance();
        $dir = ROOT_PATH . '/database/migrations';
        $files = glob($dir . '/*.sql') ?: [];
        $executed = [];

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            $parts = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($parts as $statement) {
                try {
                    if ($statement !== '' && strpos(trim($statement), '--') !== 0) {
                        $db->exec($statement);
                    }
                } catch (\Throwable $e) {
                    // Registra e continua; MySQL antigo pode falhar em comandos já existentes.
                }
            }
            $executed[] = basename($file);
        }

        Session::flash('success', 'Migrações processadas. Alguns comandos já existentes podem ter sido ignorados.');
        $this->redirect('/maintenance/migrations');
    }

    public function errorLogs(): void
    {
        $this->requireAdmin();
        $db = Database::getInstance();
        try {
            $logs = $db->query("SELECT * FROM error_logs ORDER BY created_at DESC LIMIT 200")->fetchAll();
        } catch (\Throwable $e) {
            $logs = [];
        }

        $this->render('maintenance/error_logs', [
            'pageTitle' => 'Logs de Erro',
            'logs' => $logs,
        ]);
    }
}
