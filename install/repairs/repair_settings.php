<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/installed.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(150) NOT NULL UNIQUE,
        valor LONGTEXT NULL,
        tipo VARCHAR(50) DEFAULT 'string',
        descricao TEXT NULL,
        grupo VARCHAR(100) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        deleted_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

foreach ([
    'tipo' => "VARCHAR(50) DEFAULT 'string'",
    'descricao' => "TEXT NULL",
    'grupo' => "VARCHAR(100) NULL",
    'created_at' => "DATETIME NULL",
    'updated_at' => "DATETIME NULL",
    'deleted_at' => "DATETIME NULL",
] as $column => $definition) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM settings LIKE ?");
    $stmt->execute([$column]);
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN {$column} {$definition}");
    }
}

$pdo->exec("UPDATE settings SET tipo = 'string' WHERE tipo IS NULL OR tipo = ''");

echo "Tabela settings corrigida com sucesso.";
