<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/installed.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

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

$pdo->exec("
    INSERT IGNORE INTO permissions (name, label, module, created_at) VALUES
    ('clients.view', 'Visualizar clientes', 'clients', NOW()),
    ('clients.create', 'Criar clientes', 'clients', NOW()),
    ('clients.edit', 'Editar clientes', 'clients', NOW()),
    ('clients.delete', 'Excluir clientes', 'clients', NOW()),
    ('cases.view', 'Visualizar processos', 'cases', NOW()),
    ('cases.create', 'Criar processos', 'cases', NOW()),
    ('cases.edit', 'Editar processos', 'cases', NOW()),
    ('cases.delete', 'Excluir processos', 'cases', NOW()),
    ('tasks.view', 'Visualizar tarefas', 'tasks', NOW()),
    ('tasks.create', 'Criar tarefas', 'tasks', NOW()),
    ('tasks.edit', 'Editar tarefas', 'tasks', NOW()),
    ('tasks.delete', 'Excluir tarefas', 'tasks', NOW()),
    ('financial.view', 'Visualizar financeiro', 'financial', NOW()),
    ('financial.create', 'Criar financeiro', 'financial', NOW()),
    ('financial.edit', 'Editar financeiro', 'financial', NOW()),
    ('financial.delete', 'Excluir financeiro', 'financial', NOW()),
    ('documents.view', 'Visualizar documentos', 'documents', NOW()),
    ('documents.create', 'Criar documentos', 'documents', NOW()),
    ('documents.edit', 'Editar documentos', 'documents', NOW()),
    ('documents.delete', 'Excluir documentos', 'documents', NOW()),
    ('admin.users', 'Gerenciar usuários', 'admin', NOW()),
    ('admin.settings', 'Gerenciar configurações', 'admin', NOW())
");

$pdo->exec("UPDATE users SET role_id = 1 WHERE role = 'admin' OR role_id IS NULL OR role_id = 0");

echo "Permissões corrigidas. Faça logout e login novamente para atualizar a sessão.";
