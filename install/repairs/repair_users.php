<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/installed.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("INSERT IGNORE INTO roles (id,name,label,description,created_at) VALUES
(1,'admin','Administrador','Acesso total',NOW()),
(2,'lawyer','Advogado','Advogado',NOW()),
(3,'assistant','Assistente','Assistente',NOW()),
(4,'client','Cliente','Cliente',NOW())");

$pdo->exec("CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    label VARCHAR(255) NULL,
    module VARCHAR(100) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted TINYINT(1) DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_user_permission (user_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach ([
    'role_id' => 'INT NULL',
    'cargo' => 'VARCHAR(150) NULL',
    'oab_number' => 'VARCHAR(30) NULL',
    'oab_state' => 'VARCHAR(2) NULL',
    'phone' => 'VARCHAR(50) NULL',
    'last_login' => 'DATETIME NULL',
    'login_attempts' => 'INT DEFAULT 0',
    'blocked_until' => 'DATETIME NULL',
    'last_login_ip' => 'VARCHAR(100) NULL',
    'password_reset_token' => 'VARCHAR(255) NULL',
    'password_reset_expires' => 'DATETIME NULL',
    'deleted_at' => 'DATETIME NULL',
    'created_at' => 'DATETIME NULL',
    'updated_at' => 'DATETIME NULL'
] as $column => $definition) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE ?");
    $stmt->execute([$column]);
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
    }
}

$pdo->exec("UPDATE users SET role_id = 1 WHERE role_id IS NULL OR role_id = 0");

echo "Usuários, roles e permissões corrigidos.";
