<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Logger;
use App\Helpers\SecurityHelper;
use PDO;

class InstallController extends Controller
{
    private $lockFile;

    public function __construct()
    {
        $this->lockFile = ROOT_PATH . '/storage/installed.lock';
    }

    public function index(): void
    {
        if (file_exists($this->lockFile)) {
            $this->redirect('/login');
        }
        $this->render('install/index', [], 'install');
    }

    public function testDb(): void
    {
        $this->validateCsrf();
        $params = [
            'host'     => $this->input('db_host', 'localhost'),
            'port'     => $this->input('db_port', '3306'),
            'database' => $this->input('db_database', ''),
            'username' => $this->input('db_username', ''),
            'password' => $_POST['db_password'] ?? '',
        ];

        if (empty($params['database']) || empty($params['username'])) {
            $this->json(['success' => false, 'message' => 'Preencha todos os campos de banco de dados.']);
        }

        $result = Database::testConnection($params);
        if ($result) {
            $this->json(['success' => true, 'message' => 'Banco de dados conectado com sucesso!']);
        } else {
            $this->json(['success' => false, 'message' => 'Falha na conexão com banco de dados. Verifique as credenciais.']);
        }
    }

    public function run(): void
    {
        if (file_exists($this->lockFile)) {
            $this->json(['success' => false, 'message' => 'Sistema já instalado.']);
        }

        $this->validateCsrf();

        $name     = trim($this->input('admin_name', ''));
        $email    = trim($this->input('admin_email', ''));
        $password = $_POST['admin_password'] ?? '';
        $confirm  = $_POST['admin_password_confirmation'] ?? '';

        $dbHost   = $this->input('db_host', 'localhost');
        $dbPort   = $this->input('db_port', '3306');
        $dbName   = $this->input('db_database', '');
        $dbUser   = $this->input('db_username', '');
        $dbPass   = $_POST['db_password'] ?? '';

        // Validations
        if (empty($name) || empty($email) || empty($password) || empty($dbName)) {
            $this->json(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'E-mail inválido.']);
        }
        if ($password !== $confirm) {
            $this->json(['success' => false, 'message' => 'As senhas não coincidem.']);
        }
        $pwErrors = SecurityHelper::validatePasswordStrength($password);
        if (!empty($pwErrors)) {
            $this->json(['success' => false, 'message' => implode(' ', $pwErrors)]);
        }

        // Test DB connection
        $params = ['host' => $dbHost, 'port' => $dbPort, 'database' => $dbName, 'username' => $dbUser, 'password' => $dbPass];
        if (!Database::testConnection($params)) {
            $this->json(['success' => false, 'message' => 'Falha na conexão com banco de dados. Verifique as credenciais.']);
        }

        // Update .env
        $this->updateEnv($dbHost, $dbPort, $dbName, $dbUser, $dbPass);

        // Reset DB connection to use new config
        Database::reset();
        // Force reload config with new .env values
        $this->reloadEnv();

        try {
            $db = Database::getInstance();
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro ao conectar: ' . $e->getMessage()]);
        }

        // Run migrations
        $sqlFile = ROOT_PATH . '/database/migrations/001_initial_schema.sql';
        if (!file_exists($sqlFile)) {
            $this->json(['success' => false, 'message' => 'Arquivo de migração não encontrado.']);
        }

        try {
            $sql = file_get_contents($sqlFile);
            $statements = explode(';', $sql);
            foreach ($statements as $statement) {
                // Strip comment lines, then check if anything executable remains
                $lines = explode("\n", $statement);
                $clean = array_filter($lines, function($line) {
                    return strpos(trim($line), '--') !== 0;
                });
                $statement = trim(implode("\n", $clean));
                if ($statement !== '') {
                    $db->exec($statement);
                }
            }
        } catch (\Exception $e) {
            Logger::critical('Install migration failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao criar tabelas: ' . $e->getMessage()]);
        }

        // Create admin user
        try {
            $hash = SecurityHelper::hashPassword($password);
            $appKey = SecurityHelper::generateToken(32);

            // Check if admin already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([strtolower($email)]);
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role_id, status, created_at, updated_at) VALUES (?, ?, ?, 1, 'active', NOW(), NOW())");
                $stmt->execute([$name, strtolower($email), $hash]);
                $adminId = (int)$db->lastInsertId();

                // Set install status
                $db->prepare("INSERT INTO install_status (installed, installed_at, admin_email) VALUES (1, NOW(), ?)")->execute([strtolower($email)]);

                Logger::audit("Sistema instalado. Admin criado: {$email}");
            }

            // Update APP_KEY in .env
            $this->updateEnvKey($appKey);

        } catch (\Exception $e) {
            Logger::critical('Install user creation failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erro ao criar usuário administrador: ' . $e->getMessage()]);
        }

        // Create lock file
        $lockContent = json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version'      => '1.0.0',
            'admin'        => $email,
        ]);
        file_put_contents($this->lockFile, $lockContent);
        chmod($this->lockFile, 0444);

        Logger::audit('Sistema instalado com sucesso.');
        $this->json(['success' => true, 'message' => 'Sistema instalado com sucesso! Redirecionando para o login...', 'redirect' => '/login']);
    }

    private function updateEnv(string $host, string $port, string $database, string $username, string $password): void
    {
        $envFile = ROOT_PATH . '/.env';
        if (!file_exists($envFile)) {
            copy(ROOT_PATH . '/.env.example', $envFile);
        }
        $content = file_get_contents($envFile);
        $replacements = [
            '/^DB_HOST=.*/m'     => "DB_HOST={$host}",
            '/^DB_PORT=.*/m'     => "DB_PORT={$port}",
            '/^DB_DATABASE=.*/m' => "DB_DATABASE={$database}",
            '/^DB_USERNAME=.*/m' => "DB_USERNAME={$username}",
            '/^DB_PASSWORD=.*/m' => "DB_PASSWORD={$password}",
        ];
        foreach ($replacements as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        file_put_contents($envFile, $content);
    }

    private function updateEnvKey(string $key): void
    {
        $envFile = ROOT_PATH . '/.env';
        $content = file_get_contents($envFile);
        $content = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$key}", $content);
        file_put_contents($envFile, $content);
    }

    private function reloadEnv(): void
    {
        $envFile = ROOT_PATH . '/.env';
        if (!file_exists($envFile)) return;
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
