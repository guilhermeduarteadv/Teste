<?php
declare(strict_types=1);

// INSTALL_ALREADY_CHECK_V57
$__rootCheck = dirname(__DIR__);
$__alreadyInstalled = file_exists($__rootCheck . '/storage/installed.lock') || file_exists($__rootCheck . '/config/installed.php');


error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$installedLock = $root . '/storage/installed.lock';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function splitSqlStatements(string $sql): array {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $parts = [];
    $buffer = '';
    $inString = false;
    $quote = '';

    $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if (($ch === "'" || $ch === '"') && $prev !== '\\') {
            if (!$inString) {
                $inString = true;
                $quote = $ch;
            } elseif ($quote === $ch) {
                $inString = false;
                $quote = '';
            }
        }

        if ($ch === ';' && !$inString) {
            $trim = trim($buffer);
            if ($trim !== '') {
                $parts[] = $trim;
            }
            $buffer = '';
        } else {
            $buffer .= $ch;
        }
    }

    $trim = trim($buffer);
    if ($trim !== '') {
        $parts[] = $trim;
    }

    return $parts;
}

$requirements = [
    'PHP >= 7.3.10'                       => version_compare(PHP_VERSION, '7.3.10', '>='),
    'PDO'                                 => extension_loaded('pdo'),
    'PDO MySQL'                           => extension_loaded('pdo_mysql'),
    'JSON'                                => extension_loaded('json'),
    'mbstring'                            => extension_loaded('mbstring'),
    'OpenSSL'                             => extension_loaded('openssl'),
    'Fileinfo (uploads)'                  => extension_loaded('fileinfo'),
    'GD ou Imagick (thumbnails)'          => extension_loaded('gd') || extension_loaded('imagick'),
    'ZIP (backup/exportações)'            => extension_loaded('zip'),
    'storage/ gravável'                   => is_writable($root . '/storage') || @mkdir($root . '/storage', 0775, true),
    'logs/ gravável'                      => is_writable($root . '/logs')    || @mkdir($root . '/logs',    0775, true),
    'config/ gravável'                    => is_writable($root . '/config')  || @mkdir($root . '/config',  0775, true),
];

$requirementsOk = !in_array(false, $requirements, true);

$messages = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $baseUrl = trim($_POST['base_url'] ?? '/public');
    $adminName = trim($_POST['admin_name'] ?? 'Administrador');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = (string)($_POST['admin_password'] ?? '');

    try {
        if ($dbName === '' || $dbUser === '' || $adminEmail === '' || $adminPass === '') {
            throw new RuntimeException('Preencha banco, usuário, e-mail e senha do administrador.');
        }

        if (!$requirementsOk) {
            throw new RuntimeException('Atenda a todos os requisitos antes de instalar.');
        }

        // Conecta sem dbname para poder criá-lo se não existir
        $pdoServer = new PDO(
            "mysql:host={$dbHost};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $pdo = new PDO(
            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // 1) Schema consolidado
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        foreach (splitSqlStatements($schema) as $statement) {
            try {
                $pdo->exec($statement);
            } catch (Throwable $e) {
                $messages[] = 'Aviso schema: ' . $e->getMessage();
            }
        }

        // 2) Seed mínimo (roles + settings padrão)
        $seedFile = __DIR__ . '/seed.sql';
        if (is_file($seedFile)) {
            foreach (splitSqlStatements(file_get_contents($seedFile)) as $statement) {
                try {
                    $pdo->exec($statement);
                } catch (Throwable $e) {
                    $messages[] = 'Aviso seed: ' . $e->getMessage();
                }
            }
        }

        $hash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, role_id, status, created_at, updated_at)
            VALUES (?, ?, ?, 'admin', 1, 'active', NOW(), NOW())
            ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), role = 'admin', role_id = 1, status = 'active', updated_at = NOW()
        ");
        $stmt->execute([$adminName, $adminEmail, $hash]);

        $configDir = $root . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0775, true);
        }

        $config = "<?php\n\n";
        $config .= "if (!defined('DB_HOST')) { define('DB_HOST', " . var_export($dbHost, true) . "); }\n";
        $config .= "if (!defined('DB_NAME')) { define('DB_NAME', " . var_export($dbName, true) . "); }\n";
        $config .= "if (!defined('DB_USER')) { define('DB_USER', " . var_export($dbUser, true) . "); }\n";
        $config .= "if (!defined('DB_PASS')) { define('DB_PASS', " . var_export($dbPass, true) . "); }\n";
        $config .= "if (!defined('APP_BASE_PATH')) { define('APP_BASE_PATH', " . var_export(rtrim($baseUrl, '/'), true) . "); }\n";
        $config .= "if (!defined('APP_INSTALLED')) { define('APP_INSTALLED', true); }\n";

        file_put_contents($configDir . '/installed.php', $config);

        if (!is_dir($root . '/storage')) {
            mkdir($root . '/storage', 0775, true);
        }

        file_put_contents($installedLock, date('Y-m-d H:i:s'));

        $success = true;
        $messages[] = 'Instalação concluída. Remova ou renomeie a pasta /install por segurança.';
    } catch (Throwable $e) {
        $messages[] = 'Erro: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Instalação - JurisControl</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{font-family:Arial,sans-serif;background:#f4f6fb;margin:0;padding:30px;color:#0f172a}
        .wrap{max-width:960px;margin:auto;background:white;border-radius:14px;padding:25px;box-shadow:0 10px 30px rgba(15,23,42,.08)}
        h1{margin-top:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.field{margin-bottom:12px}
        label{display:block;font-weight:700;margin-bottom:5px}input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:8px}
        .ok{color:#15803d}.bad{color:#b91c1c}.msg{padding:12px;border-radius:8px;background:#f1f5f9;margin:8px 0}
        button{background:#2563eb;color:white;border:0;border-radius:8px;padding:12px 18px;font-weight:700;cursor:pointer}
        @media(max-width:700px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Instalação JurisControl</h1>
    <?php if ($__alreadyInstalled && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="msg">O sistema já parece instalado. Para reinstalar do zero, apague <strong>storage/installed.lock</strong> e <strong>config/installed.php</strong>.</div>
        <p><a href="/public/">Acessar o sistema</a></p>
    <?php endif; ?>

    <h2>Requisitos</h2>
    <?php foreach ($requirements as $name => $ok): ?>
        <div class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓' : '✗' ?> <?= h($name) ?></div>
    <?php endforeach; ?>

    <?php foreach ($messages as $msg): ?>
        <div class="msg"><?= h($msg) ?></div>
    <?php endforeach; ?>

    <?php if ($success): ?>
        <p><a href="/public/">Acessar o sistema</a></p>
    <?php else: ?>
    <h2>Dados da instalação</h2>
    <form method="post">
        <div class="grid">
            <div class="field"><label>Host do banco</label><input name="db_host" value="localhost"></div>
            <div class="field"><label>Nome do banco</label><input name="db_name" required></div>
            <div class="field"><label>Usuário do banco</label><input name="db_user" required></div>
            <div class="field"><label>Senha do banco</label><input name="db_pass" type="password"></div>
            <div class="field"><label>Base URL</label><input name="base_url" value="/public"></div>
        </div>

        <h2>Administrador</h2>
        <div class="grid">
            <div class="field"><label>Nome</label><input name="admin_name" value="Administrador"></div>
            <div class="field"><label>E-mail</label><input name="admin_email" type="email" required></div>
            <div class="field"><label>Senha</label><input name="admin_password" type="password" required></div>
        </div>

        <button>Instalar sistema</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
