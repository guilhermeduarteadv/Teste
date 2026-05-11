<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo '<h2>PHP ' . phpversion() . '</h2>';
echo '<p>ROOT_PATH: ' . dirname(__DIR__) . '</p>';

define('ROOT_PATH', dirname(__DIR__));
define('APP_BASE_PATH', '/teste/public');
define('APP_DEBUG', true);
define('APP_ENV', 'production');
define('APP_VERSION', '1.0.0');
define('START_TIME', microtime(true));

// Test autoloader
spl_autoload_register(function (string $class): void {
    $map = [
        'Core\\'  => ROOT_PATH . '/core/',
        'App\\Controllers\\' => ROOT_PATH . '/app/Controllers/',
        'App\\Models\\'      => ROOT_PATH . '/app/Models/',
        'App\\Services\\'    => ROOT_PATH . '/app/Services/',
        'App\\Helpers\\'     => ROOT_PATH . '/app/Helpers/',
        'App\\Middleware\\'  => ROOT_PATH . '/app/Middleware/',
    ];
    foreach ($map as $ns => $dir) {
        if (strpos($class, $ns) === 0) {
            $file = $dir . str_replace($ns, '', $class) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
    $cn = basename(str_replace('\\', '/', $class));
    foreach ([ROOT_PATH.'/core/', ROOT_PATH.'/app/Controllers/', ROOT_PATH.'/app/Models/'] as $dir) {
        if (file_exists($dir.$cn.'.php')) { require_once $dir.$cn.'.php'; return; }
    }
});

echo '<h3>Testando arquivos core...</h3>';
$files = ['Logger', 'Session', 'Database', 'Router', 'Controller', 'Model'];
foreach ($files as $f) {
    $path = ROOT_PATH . '/core/' . $f . '.php';
    if (file_exists($path)) {
        try {
            require_once $path;
            echo "<p style='color:green'>✓ core/{$f}.php OK</p>";
        } catch (Throwable $e) {
            echo "<p style='color:red'>✗ core/{$f}.php ERRO: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ core/{$f}.php NÃO ENCONTRADO</p>";
    }
}

echo '<h3>Testando controllers...</h3>';
$controllers = ['InstallController', 'AuthController', 'DashboardController'];
foreach ($controllers as $c) {
    $path = ROOT_PATH . '/app/Controllers/' . $c . '.php';
    if (file_exists($path)) {
        try {
            require_once $path;
            echo "<p style='color:green'>✓ {$c}.php OK</p>";
        } catch (Throwable $e) {
            echo "<p style='color:red'>✗ {$c}.php ERRO: " . $e->getMessage() . ' em linha ' . $e->getLine() . "</p>";
        }
    } else {
        echo "<p style='color:orange'>- {$c}.php não encontrado</p>";
    }
}

echo '<p><strong>Tudo OK se chegou aqui!</strong></p>';
