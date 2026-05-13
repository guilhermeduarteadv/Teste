<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class SystemCheckService
{
    private $db;
    private $items = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): array
    {
        $this->items = [];

        $this->checkTables();
        $this->checkColumns();
        $this->checkAdminUser();
        $this->checkFolders();
        $this->checkPhpExtensions();
        $this->checkPhpVersion();
        $this->checkEnvFile();
        $this->checkPermissions();

        return $this->items;
    }

    public function repair(): array
    {
        $repaired = [];
        $errors = [];

        try {
            $guard = new SchemaGuardService();
            $guard->ensureV52Schema();
            $guard->ensureV62FullRuntimeSchema();
            $repaired[] = 'Schema atualizado pelo SchemaGuardService';
        } catch (\Throwable $e) {
            $errors[] = 'SchemaGuard: ' . $e->getMessage();
        }

        try {
            $this->repairFolders();
            $repaired[] = 'Diretórios de armazenamento verificados/criados';
        } catch (\Throwable $e) {
            $errors[] = 'Pastas: ' . $e->getMessage();
        }

        return ['repaired' => $repaired, 'errors' => $errors];
    }

    private function checkTables(): void
    {
        $required = [
            'users', 'roles', 'permissions', 'user_permissions',
            'clients', 'cases', 'case_movements', 'case_deadlines',
            'case_hearings', 'case_timeline', 'case_contacts', 'case_witnesses',
            'tasks', 'documents', 'financial_entries',
            'publications', 'timesheets', 'settings', 'system_logs',
            'audit_trail', 'api_logs', 'backups',
            'legal_consultancies', 'administrative_procedures',
            'tribunal_connections', 'enabled_tribunals',
            'legal_templates', 'client_requests', 'payable_entries',
            'install_status', 'error_logs',
        ];

        foreach ($required as $table) {
            $exists = $this->tableExists($table);
            $this->items[] = [
                'type'          => 'table',
                'name'          => $table,
                'expected'      => 'exists',
                'actual'        => $exists ? 'exists' : 'missing',
                'status'        => $exists ? 'ok' : 'error',
                'repair_action' => $exists ? null : 'SchemaGuardService::ensureV62FullRuntimeSchema',
                'message'       => $exists ? "Tabela {$table} existe." : "Tabela {$table} não encontrada.",
            ];
        }

        $newTables = ['system_check_runs', 'system_check_items', 'notifications'];
        foreach ($newTables as $table) {
            $exists = $this->tableExists($table);
            $this->items[] = [
                'type'          => 'table',
                'name'          => $table,
                'expected'      => 'exists',
                'actual'        => $exists ? 'exists' : 'missing',
                'status'        => $exists ? 'ok' : 'warning',
                'repair_action' => $exists ? null : 'SchemaGuardService::ensureSystemCheckTables',
                'message'       => $exists ? "Tabela {$table} existe." : "Tabela {$table} ausente (módulo novo).",
            ];
        }
    }

    private function checkColumns(): void
    {
        $checks = [
            ['cases', 'deleted_at'],
            ['cases', 'parte_contraria_nome'],
            ['cases', 'segredo_justica'],
            ['clients', 'deleted_at'],
            ['tasks', 'deleted_at'],
            ['tasks', 'due_date'],
            ['financial_entries', 'deleted_at'],
            ['financial_entries', 'visivel_cliente'],
            ['documents', 'deleted_at'],
            ['documents', 'visivel_cliente'],
            ['users', 'deleted_at'],
            ['users', 'portal_access'],
            ['case_deadlines', 'tipo'],
            ['case_hearings', 'status'],
            ['case_hearings', 'client_id'],
        ];

        foreach ($checks as [$table, $column]) {
            if (!$this->tableExists($table)) {
                continue;
            }
            $exists = $this->columnExists($table, $column);
            $this->items[] = [
                'type'          => 'column',
                'name'          => "{$table}.{$column}",
                'expected'      => 'exists',
                'actual'        => $exists ? 'exists' : 'missing',
                'status'        => $exists ? 'ok' : 'warning',
                'repair_action' => $exists ? null : 'SchemaGuardService::addColumnIfMissing',
                'message'       => $exists
                    ? "Coluna {$table}.{$column} existe."
                    : "Coluna {$table}.{$column} ausente — reparador disponível.",
            ];
        }
    }

    private function checkAdminUser(): void
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1 AND (deleted_at IS NULL OR deleted_at = '') LIMIT 1");
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            $ok = $count > 0;
            $this->items[] = [
                'type'          => 'user',
                'name'          => 'admin_user',
                'expected'      => '>= 1',
                'actual'        => (string)$count,
                'status'        => $ok ? 'ok' : 'error',
                'repair_action' => $ok ? null : 'Criar usuário administrador via /install',
                'message'       => $ok ? "Administrador presente ({$count})." : 'Nenhum usuário administrador encontrado.',
            ];
        } catch (\Throwable $e) {
            $this->items[] = [
                'type'    => 'user',
                'name'    => 'admin_user',
                'expected'=> '>= 1',
                'actual'  => 'erro',
                'status'  => 'warning',
                'repair_action' => null,
                'message' => 'Não foi possível verificar admin: ' . $e->getMessage(),
            ];
        }
    }

    private function checkFolders(): void
    {
        $root = ROOT_PATH;
        $folders = [
            $root . '/storage'                     => 'storage',
            $root . '/storage/financial_receipts'  => 'storage/financial_receipts',
            $root . '/logs'                        => 'logs',
            $root . '/public/assets'               => 'public/assets',
        ];

        foreach ($folders as $path => $label) {
            $exists  = is_dir($path);
            $writable = $exists && is_writable($path);
            if (!$exists) {
                $status = 'warning';
                $msg = "Diretório {$label} não existe.";
            } elseif (!$writable) {
                $status = 'error';
                $msg = "Diretório {$label} não tem permissão de escrita.";
            } else {
                $status = 'ok';
                $msg = "Diretório {$label} existe e tem escrita.";
            }
            $this->items[] = [
                'type'          => 'folder',
                'name'          => $label,
                'expected'      => 'writable',
                'actual'        => $exists ? ($writable ? 'writable' : 'not writable') : 'missing',
                'status'        => $status,
                'repair_action' => $status !== 'ok' ? 'mkdir + chmod' : null,
                'message'       => $msg,
            ];
        }
    }

    private function checkPhpExtensions(): void
    {
        $required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl', 'session'];
        $recommended = ['gd', 'zip', 'curl', 'fileinfo'];

        foreach ($required as $ext) {
            $loaded = extension_loaded($ext);
            $this->items[] = [
                'type'          => 'extension',
                'name'          => "ext_{$ext}",
                'expected'      => 'loaded',
                'actual'        => $loaded ? 'loaded' : 'missing',
                'status'        => $loaded ? 'ok' : 'error',
                'repair_action' => $loaded ? null : "Ativar extensão {$ext} no php.ini",
                'message'       => $loaded ? "Extensão {$ext} ativa." : "Extensão {$ext} ausente (obrigatória).",
            ];
        }

        foreach ($recommended as $ext) {
            $loaded = extension_loaded($ext);
            $this->items[] = [
                'type'          => 'extension',
                'name'          => "ext_{$ext}",
                'expected'      => 'loaded',
                'actual'        => $loaded ? 'loaded' : 'missing',
                'status'        => $loaded ? 'ok' : 'warning',
                'repair_action' => $loaded ? null : "Ativar extensão {$ext} no php.ini (recomendada)",
                'message'       => $loaded ? "Extensão {$ext} ativa." : "Extensão {$ext} ausente (recomendada).",
            ];
        }
    }

    private function checkPhpVersion(): void
    {
        $version = phpversion();
        $ok = version_compare($version, '7.3.0', '>=');
        $this->items[] = [
            'type'          => 'php',
            'name'          => 'php_version',
            'expected'      => '>= 7.3.0',
            'actual'        => $version,
            'status'        => $ok ? 'ok' : 'error',
            'repair_action' => $ok ? null : 'Atualizar PHP para 7.3+',
            'message'       => "PHP {$version}" . ($ok ? ' (compatível).' : ' (incompatível).'),
        ];
    }

    private function checkEnvFile(): void
    {
        $envFile = ROOT_PATH . '/.env';
        $exists = file_exists($envFile);
        $this->items[] = [
            'type'          => 'file',
            'name'          => '.env',
            'expected'      => 'exists',
            'actual'        => $exists ? 'exists' : 'missing',
            'status'        => $exists ? 'ok' : 'error',
            'repair_action' => $exists ? null : 'Copiar .env.example para .env',
            'message'       => $exists ? 'Arquivo .env presente.' : 'Arquivo .env não encontrado.',
        ];

        if ($exists) {
            $dbName = $_ENV['DB_DATABASE'] ?? '';
            $hasDb = !empty($dbName);
            $this->items[] = [
                'type'          => 'config',
                'name'          => 'db_configured',
                'expected'      => 'set',
                'actual'        => $hasDb ? $dbName : 'empty',
                'status'        => $hasDb ? 'ok' : 'warning',
                'repair_action' => $hasDb ? null : 'Configurar DB_DATABASE no .env',
                'message'       => $hasDb ? "DB_DATABASE configurado ({$dbName})." : 'DB_DATABASE não configurado.',
            ];
        }
    }

    private function checkPermissions(): void
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM permissions");
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            $this->items[] = [
                'type'          => 'data',
                'name'          => 'permissions_seeded',
                'expected'      => '> 0',
                'actual'        => (string)$count,
                'status'        => $count > 0 ? 'ok' : 'warning',
                'repair_action' => $count > 0 ? null : 'Executar seed de permissões',
                'message'       => $count > 0 ? "{$count} permissões cadastradas." : 'Nenhuma permissão cadastrada.',
            ];
        } catch (\Throwable $e) {
            $this->items[] = [
                'type'    => 'data',
                'name'    => 'permissions_seeded',
                'expected'=> '> 0',
                'actual'  => 'erro',
                'status'  => 'warning',
                'repair_action' => null,
                'message' => 'Não foi possível verificar permissões: ' . $e->getMessage(),
            ];
        }
    }

    private function repairFolders(): void
    {
        $root = ROOT_PATH;
        $folders = [
            $root . '/storage',
            $root . '/storage/financial_receipts',
            $root . '/storage/generated_documents',
            $root . '/storage/reports',
            $root . '/logs',
        ];
        foreach ($folders as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (\Throwable $e) {
            return false;
        }
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

    public function saveRun(array $items, int $userId): int
    {
        $errors   = count(array_filter($items, function($i) { return $i['status'] === 'error'; }));
        $warnings = count(array_filter($items, function($i) { return $i['status'] === 'warning'; }));
        $status   = $errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'ok');

        $guard = new SchemaGuardService();
        if (!$guard->tableExists('system_check_runs')) {
            $this->createSystemCheckTables();
        }

        $stmt = $this->db->prepare(
            "INSERT INTO system_check_runs (status, started_at, finished_at, total_errors, total_warnings, result_json, created_by)
             VALUES (?, NOW(), NOW(), ?, ?, ?, ?)"
        );
        $stmt->execute([$status, $errors, $warnings, json_encode($items), $userId]);
        $runId = (int)$this->db->lastInsertId();

        $ins = $this->db->prepare(
            "INSERT INTO system_check_items (run_id, type, name, expected, actual, status, repair_action, message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $ins->execute([
                $runId,
                $item['type'],
                $item['name'],
                $item['expected'] ?? '',
                $item['actual'] ?? '',
                $item['status'],
                $item['repair_action'] ?? null,
                $item['message'] ?? '',
            ]);
        }

        return $runId;
    }

    private function createSystemCheckTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS system_check_runs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(20) DEFAULT 'ok',
                started_at DATETIME NOT NULL,
                finished_at DATETIME NOT NULL,
                total_errors INT DEFAULT 0,
                total_warnings INT DEFAULT 0,
                result_json LONGTEXT NULL,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS system_check_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                run_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                expected VARCHAR(255) NULL,
                actual VARCHAR(255) NULL,
                status VARCHAR(20) DEFAULT 'ok',
                repair_action VARCHAR(500) NULL,
                message TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sci_run (run_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}
