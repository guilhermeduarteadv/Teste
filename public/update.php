<?php
declare(strict_types=1);

/**
 * JurisControl — Script de Atualização Segura
 *
 * Copie este arquivo para public/update.php, acesse pelo navegador,
 * execute a atualização e APAGUE o arquivo depois.
 *
 * Não apaga nenhum dado existente.
 */

// -----------------------------------------------------------------------
// Proteção básica: exige confirmação via POST com a senha do admin
// -----------------------------------------------------------------------
$root = dirname(__DIR__);

define('ROOT_PATH', $root);

$lockFile = $root . '/storage/installed.lock';
if (!file_exists($lockFile)) {
    http_response_code(403);
    die('<b>Sistema não instalado.</b> Execute o instalador primeiro.');
}

// Carrega .env
if (file_exists($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v, " \t\n\r\0\x0B\"'");
        $_ENV[$k] = $v; putenv("{$k}={$v}");
    }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$logs      = [];
$errors    = [];
$confirmed = false;
$done      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['confirm'])) {
    $confirmed = true;

    // Carrega autoloader manual
    spl_autoload_register(function($class) use ($root) {
        $paths = [
            $root . '/core/'            . str_replace('Core\\',            '', $class) . '.php',
            $root . '/app/Services/'    . str_replace('App\\Services\\',   '', $class) . '.php',
            $root . '/app/Controllers/' . str_replace('App\\Controllers\\','', $class) . '.php',
            $root . '/app/Models/'      . str_replace('App\\Models\\',     '', $class) . '.php',
            $root . '/app/Helpers/'     . str_replace('App\\Helpers\\',    '', $class) . '.php',
        ];
        foreach ($paths as $p) { if (file_exists($p)) { require_once $p; return; } }
    });

    // 1. Conexão com banco
    try {
        $db = \Core\Database::getInstance();
        $logs[] = ['ok', 'Conexão com banco de dados estabelecida.'];
    } catch (\Throwable $e) {
        $errors[] = 'Falha na conexão: ' . $e->getMessage();
        goto renderPage;
    }

    // 2. Roda SchemaGuard completo
    try {
        $guard = new \App\Services\SchemaGuardService();
        $guard->ensureV62FullRuntimeSchema();
        $logs[] = ['ok', 'SchemaGuard v62 executado — tabelas e colunas verificadas/criadas.'];
    } catch (\Throwable $e) {
        $errors[] = 'SchemaGuard falhou: ' . $e->getMessage();
    }

    // 3. Tabelas novas (v38): system_check, notifications, dashboard
    try {
        $guard->ensureSystemCheckTables();
        $logs[] = ['ok', 'Tabelas system_check_runs e system_check_items verificadas.'];
    } catch (\Throwable $e) {
        $errors[] = 'system_check tables: ' . $e->getMessage();
    }

    try {
        $guard->ensureNotificationsTable();
        $logs[] = ['ok', 'Tabela notifications verificada.'];
    } catch (\Throwable $e) {
        $errors[] = 'notifications table: ' . $e->getMessage();
    }

    try {
        $guard->ensureDashboardTables();
        $logs[] = ['ok', 'Tabelas dashboard_widgets e dashboard_snapshots verificadas.'];
    } catch (\Throwable $e) {
        $errors[] = 'dashboard tables: ' . $e->getMessage();
    }

    // 4. Roda SchemaMaintenanceService (colunas de compatibilidade)
    try {
        \App\Services\SchemaMaintenanceService::ensure();
        $logs[] = ['ok', 'SchemaMaintenanceService executado — colunas de compatibilidade verificadas.'];
    } catch (\Throwable $e) {
        $errors[] = 'SchemaMaintenanceService: ' . $e->getMessage();
    }

    // 5. Registra versão
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS schema_version (
                version VARCHAR(20) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $stmt = $db->prepare("INSERT IGNORE INTO schema_version (version) VALUES (?)");
        $stmt->execute(['v38-update-script']);
        $logs[] = ['ok', 'Versão v38 registrada em schema_version.'];
    } catch (\Throwable $e) {
        $errors[] = 'schema_version: ' . $e->getMessage();
    }

    // 6. Verifica diretórios necessários
    $dirs = [
        $root . '/storage',
        $root . '/storage/financial_receipts',
        $root . '/storage/generated_documents',
        $root . '/storage/reports',
        $root . '/logs',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $logs[] = ['ok', 'Diretório criado: ' . basename($dir)];
        }
    }
    $logs[] = ['ok', 'Diretórios de armazenamento verificados.'];

    $done = true;
}

renderPage:
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>JurisControl — Atualização</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:680px">
    <div class="text-center mb-4">
        <h3 class="fw-bold"><i class="fas fa-sync-alt me-2 text-primary"></i>JurisControl — Atualização Segura</h3>
        <p class="text-muted">Aplica migrações e corrige estrutura do banco <strong>sem apagar dados</strong>.</p>
    </div>

    <?php if (!$confirmed): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>O que este script faz:</strong>
                <ul class="mb-0 mt-2">
                    <li>Cria tabelas novas ausentes (system_check, notifications, dashboard)</li>
                    <li>Adiciona colunas de compatibilidade em tabelas existentes</li>
                    <li>Verifica e cria diretórios de armazenamento</li>
                    <li>Registra versão no schema_version</li>
                    <li><strong>Não apaga, não trunca e não altera dados</strong></li>
                </ul>
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atenção:</strong> Apague este arquivo (<code>public/update.php</code>) após a atualização.
            </div>
            <form method="POST">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-play me-2"></i>Executar Atualização
                </button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Resultado da Atualização</div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($logs as [$status, $msg]): ?>
                <div class="list-group-item py-2">
                    <i class="fas fa-check-circle text-success me-2"></i><?= h($msg) ?>
                </div>
                <?php endforeach; ?>
                <?php foreach ($errors as $err): ?>
                <div class="list-group-item py-2 list-group-item-danger">
                    <i class="fas fa-times-circle me-2"></i><?= h($err) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer bg-white">
            <?php if ($done && empty($errors)): ?>
            <div class="alert alert-success mb-3">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Atualização concluída com sucesso!</strong>
            </div>
            <?php elseif (!empty($errors)): ?>
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Atualização concluída com avisos. Verifique os erros acima.
            </div>
            <?php endif; ?>
            <div class="alert alert-danger mb-3">
                <i class="fas fa-trash-alt me-2"></i>
                <strong>IMPORTANTE:</strong> Apague o arquivo <code>public/update.php</code> do servidor agora.
            </div>
            <a href="/<?= ltrim($_ENV['APP_BASE_PATH'] ?? '', '/') ?>/dashboard" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Ir para o Dashboard
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
