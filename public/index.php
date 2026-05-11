<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', dirname(__DIR__));
define('START_TIME', microtime(true));

// When Apache uses FallbackResource (mod_rewrite not loaded) it sets REDIRECT_URL
// to the original request path and may rewrite REQUEST_URI to index.php — restore it.
if (!empty($_SERVER['REDIRECT_URL'])) {
    $qs = !empty($_SERVER['REDIRECT_QUERY_STRING'])
        ? '?' . $_SERVER['REDIRECT_QUERY_STRING']
        : (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    $_SERVER['REQUEST_URI'] = $_SERVER['REDIRECT_URL'] . $qs;
}

// Auto-detect base path from the script's location (e.g. /teste/public)
$_scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('APP_BASE_PATH', rtrim($_scriptDir === '/' ? '' : $_scriptDir, '/'));
unset($_scriptDir);

// Load environment variables
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

// Define constants
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_VERSION', '1.0.0');

// Error handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

// Autoloader
spl_autoload_register(function (string $class): void {
    $paths = [
        ROOT_PATH . '/core/' . str_replace('Core\\', '', $class) . '.php',
        ROOT_PATH . '/app/Controllers/' . str_replace('App\\Controllers\\', '', $class) . '.php',
        ROOT_PATH . '/app/Models/' . str_replace('App\\Models\\', '', $class) . '.php',
        ROOT_PATH . '/app/Services/' . str_replace('App\\Services\\', '', $class) . '.php',
        ROOT_PATH . '/app/Repositories/' . str_replace('App\\Repositories\\', '', $class) . '.php',
        ROOT_PATH . '/app/Helpers/' . str_replace('App\\Helpers\\', '', $class) . '.php',
        ROOT_PATH . '/app/Middleware/' . str_replace('App\\Middleware\\', '', $class) . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
    // Fallback: search by class name only
    $className = basename(str_replace('\\', '/', $class));
    $searchPaths = [
        ROOT_PATH . '/core/' . $className . '.php',
        ROOT_PATH . '/app/Controllers/' . $className . '.php',
        ROOT_PATH . '/app/Models/' . $className . '.php',
        ROOT_PATH . '/app/Services/' . $className . '.php',
        ROOT_PATH . '/app/Repositories/' . $className . '.php',
        ROOT_PATH . '/app/Helpers/' . $className . '.php',
        ROOT_PATH . '/app/Middleware/' . $className . '.php',
    ];
    foreach ($searchPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Initialize core components
use Core\Logger;
use Core\Session;
use Core\Router;

Logger::init(ROOT_PATH . '/logs');
Session::start([
    'name'     => $_ENV['SESSION_NAME'] ?? 'juriscontrol_session',
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
]);

// Strip base path to get the logical URI for route matching
$requestUri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$basePath    = APP_BASE_PATH;
$currentUri  = $basePath !== ''
    ? '/' . trim(substr($requestUri, strlen($basePath)), '/')
    : '/' . trim($requestUri, '/');
if ($currentUri === '') $currentUri = '/';

// Check installation
$installedLock = ROOT_PATH . '/storage/installed.lock';
$installRoutes = ['/install', '/install/run', '/install/test-db'];

if (!file_exists($installedLock) && !in_array($currentUri, $installRoutes)) {
    header('Location: ' . $basePath . '/install');
    exit;
}

if (file_exists($installedLock) && in_array($currentUri, $installRoutes)) {
    header('Location: ' . $basePath . '/login');
    exit;
}

// Load routes and dispatch (pass base path so router prepends it)
$router = new Router($basePath);
require ROOT_PATH . '/routes.php';
$router->dispatch();
