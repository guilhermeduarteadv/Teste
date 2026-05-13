<?php
declare(strict_types=1);

/**
 * Repair: Creates system_check_runs, system_check_items, notifications,
 *         dashboard_widgets and dashboard_snapshots tables if missing.
 */

$root = dirname(__DIR__, 2);
require_once $root . '/core/Database.php';
require_once $root . '/app/Services/SchemaGuardService.php';

if (file_exists($root . '/.env')) {
    $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v, " \t\n\r\0\x0B\"'");
        $_ENV[$k] = $v; putenv("{$k}={$v}");
    }
}

define('ROOT_PATH', $root);

try {
    $guard = new \App\Services\SchemaGuardService();
    $guard->ensureSystemCheckTables();
    $guard->ensureNotificationsTable();
    $guard->ensureDashboardTables();
    echo "OK: tabelas system_check_runs, system_check_items, notifications, dashboard_widgets, dashboard_snapshots verificadas/criadas.\n";
} catch (\Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
