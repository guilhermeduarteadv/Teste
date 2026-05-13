-- v63: roles e tribunal_connections
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO roles (id, name, label, description, created_at) VALUES
(1, 'admin', 'Administrador', 'Acesso total ao sistema', NOW()),
(2, 'lawyer', 'Advogado', 'Usuário advogado', NOW()),
(3, 'assistant', 'Assistente', 'Usuário assistente', NOW()),
(4, 'client', 'Cliente', 'Acesso ao portal do cliente', NOW());

ALTER TABLE users ADD COLUMN role_id INT NULL;
ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN blocked_until DATETIME NULL;
ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN password_reset_expires DATETIME NULL;

ALTER TABLE tribunal_connections ADD COLUMN last_test_at DATETIME NULL;
ALTER TABLE tribunal_connections ADD COLUMN last_sync_at DATETIME NULL;
ALTER TABLE tribunal_connections ADD COLUMN last_error TEXT NULL;
ALTER TABLE tribunal_connections ADD COLUMN expires_at DATETIME NULL;
