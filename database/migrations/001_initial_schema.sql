-- JurisControl - Schema Inicial
-- Versão: 1.0.0
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABELA: install_status
-- ============================================================
CREATE TABLE IF NOT EXISTS `install_status` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `installed` TINYINT(1) NOT NULL DEFAULT 0,
  `installed_at` DATETIME NULL,
  `version` VARCHAR(20) NOT NULL DEFAULT '1.0.0',
  `admin_email` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: roles
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `label` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `label`, `description`) VALUES
('admin', 'Administrador', 'Acesso total ao sistema'),
('lawyer', 'Advogado', 'Acesso a processos, clientes e financeiro'),
('assistant', 'Assistente', 'Acesso limitado conforme permissões'),
('client', 'Cliente', 'Acesso apenas ao portal do cliente');

-- ============================================================
-- TABELA: permissions
-- ============================================================
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `label` VARCHAR(150) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `label`, `module`) VALUES
('users.create', 'Cadastrar usuário', 'users'),
('users.edit', 'Editar usuário', 'users'),
('users.delete', 'Excluir usuário', 'users'),
('clients.create', 'Cadastrar cliente', 'clients'),
('clients.edit', 'Editar cliente', 'clients'),
('clients.delete', 'Excluir cliente', 'clients'),
('cases.create', 'Cadastrar processo', 'cases'),
('cases.edit', 'Editar processo', 'cases'),
('cases.delete', 'Excluir processo', 'cases'),
('financial.create', 'Cadastrar financeiro', 'financial'),
('financial.edit', 'Editar financeiro', 'financial'),
('financial.delete', 'Excluir financeiro', 'financial'),
('financial.view', 'Visualizar financeiro', 'financial'),
('documents.create', 'Cadastrar documentos', 'documents'),
('documents.delete', 'Excluir documentos', 'documents'),
('tasks.create', 'Cadastrar tarefas', 'tasks'),
('reports.view', 'Visualizar relatórios', 'reports'),
('settings.access', 'Acessar configurações', 'settings');

-- ============================================================
-- TABELA: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL DEFAULT 2,
  `cargo` VARCHAR(100) NULL,
  `oab_number` VARCHAR(50) NULL,
  `oab_state` CHAR(2) NULL,
  `phone` VARCHAR(20) NULL,
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) NULL,
  `login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `blocked_until` DATETIME NULL,
  `last_login` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `password_reset_token` VARCHAR(100) NULL,
  `password_reset_expires` DATETIME NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `two_factor_secret` VARCHAR(100) NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: user_permissions
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  `granted` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_permission_unique` (`user_id`, `permission_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: clients
-- ============================================================
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tipo_pessoa` ENUM('fisica','juridica') NOT NULL DEFAULT 'fisica',
  `name` VARCHAR(200) NOT NULL,
  `cpf` VARCHAR(14) NULL,
  `cnpj` VARCHAR(18) NULL,
  `rg` VARCHAR(20) NULL,
  `data_nascimento` DATE NULL,
  `estado_civil` ENUM('solteiro','casado','divorciado','viuvo','uniao_estavel','outros') NULL,
  `profissao` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `whatsapp` VARCHAR(20) NULL,
  `email` VARCHAR(255) NULL,
  `cep` VARCHAR(9) NULL,
  `endereco` VARCHAR(255) NULL,
  `numero` VARCHAR(20) NULL,
  `complemento` VARCHAR(100) NULL,
  `bairro` VARCHAR(100) NULL,
  `cidade` VARCHAR(100) NULL,
  `estado` CHAR(2) NULL,
  `outras_informacoes` TEXT NULL,
  `portal_access` TINYINT(1) NOT NULL DEFAULT 0,
  `portal_password` VARCHAR(255) NULL,
  `portal_token` VARCHAR(100) NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: cases (processos)
-- ============================================================
CREATE TABLE IF NOT EXISTS `cases` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `numero_cnj` VARCHAR(50) NULL,
  `tribunal` VARCHAR(20) NULL,
  `comarca` VARCHAR(100) NULL,
  `vara` VARCHAR(100) NULL,
  `classe` VARCHAR(150) NULL,
  `assunto` VARCHAR(255) NULL,
  `valor_causa` DECIMAL(15,2) NULL DEFAULT 0.00,
  `fase_processual` VARCHAR(100) NULL,
  `status` ENUM('ativo','arquivado','suspenso','encerrado','aguardando') NOT NULL DEFAULT 'ativo',
  `risco_processual` ENUM('baixo','medio','alto','critico') NULL,
  `probabilidade_exito` ENUM('alta','media','baixa','incerta') NULL,
  `proxima_providencia` TEXT NULL,
  `outras_informacoes` TEXT NULL,
  `responsavel_id` INT UNSIGNED NULL,
  `cnj_raw_data` LONGTEXT NULL,
  `last_sync_at` DATETIME NULL,
  `last_movement_hash` VARCHAR(64) NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`responsavel_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_numero_cnj` (`numero_cnj`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: case_clients (N:N)
-- ============================================================
CREATE TABLE IF NOT EXISTS `case_clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('autor','reu','terceiro','interessado') NOT NULL DEFAULT 'autor',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `case_client_unique` (`case_id`, `client_id`),
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: case_movements
-- ============================================================
CREATE TABLE IF NOT EXISTS `case_movements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `data_movimento` DATETIME NOT NULL,
  `tipo` VARCHAR(100) NULL,
  `descricao` TEXT NOT NULL,
  `fonte` ENUM('manual','cnj_api','import') NOT NULL DEFAULT 'manual',
  `cnj_id` VARCHAR(100) NULL,
  `hash` VARCHAR(64) NULL,
  `visivel_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_case_date` (`case_id`, `data_movimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: case_deadlines
-- ============================================================
CREATE TABLE IF NOT EXISTS `case_deadlines` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('processual','interno','fatal') NOT NULL DEFAULT 'processual',
  `descricao` VARCHAR(255) NOT NULL,
  `data_inicio` DATE NOT NULL,
  `prazo_dias` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `data_final` DATE NOT NULL,
  `data_final_calculada` DATE NULL,
  `confirmado` TINYINT(1) NOT NULL DEFAULT 0,
  `confirmado_por` INT UNSIGNED NULL,
  `confirmado_em` DATETIME NULL,
  `status` ENUM('pendente','concluido','vencido','cancelado') NOT NULL DEFAULT 'pendente',
  `visivel_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `observacoes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`confirmado_por`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_case_deadline` (`case_id`, `data_final`),
  INDEX `idx_status_date` (`status`, `data_final`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: case_hearings (audiências)
-- ============================================================
CREATE TABLE IF NOT EXISTS `case_hearings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NULL,
  `client_id` INT UNSIGNED NULL,
  `tipo` ENUM('conciliacao','instrucao','julgamento','reuniao_cliente','diligencia','compromisso') NOT NULL DEFAULT 'conciliacao',
  `titulo` VARCHAR(255) NOT NULL,
  `data` DATE NOT NULL,
  `hora` TIME NOT NULL,
  `local` VARCHAR(255) NULL,
  `observacoes` TEXT NULL,
  `status` ENUM('agendado','realizado','cancelado','adiado') NOT NULL DEFAULT 'agendado',
  `visivel_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: financial_entries
-- ============================================================
CREATE TABLE IF NOT EXISTS `financial_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NULL,
  `case_id` INT UNSIGNED NULL,
  `tipo` ENUM('honorarios_contratuais','honorarios_exito','sucumbencia','horas_trabalhadas','custas','diligencias','despesas','reembolso','parcela','outros') NOT NULL,
  `descricao` VARCHAR(255) NOT NULL,
  `valor` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `vencimento` DATE NOT NULL,
  `data_pagamento` DATE NULL,
  `status` ENUM('pendente','pago','vencido','cancelado','parcial') NOT NULL DEFAULT 'pendente',
  `forma_pagamento` ENUM('dinheiro','pix','transferencia','boleto','cartao_credito','cartao_debito','cheque','outros') NULL,
  `parcela_numero` SMALLINT UNSIGNED NULL,
  `parcela_total` SMALLINT UNSIGNED NULL,
  `observacoes` TEXT NULL,
  `visivel_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `nf_numero` VARCHAR(50) NULL,
  `recibo_path` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_vencimento` (`vencimento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: documents
-- ============================================================
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `entity_type` ENUM('client','case','financial','hearing','task') NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `categoria` ENUM('procuracao','contrato_honorarios','declaracao','comprovante_pagamento','documentos_pessoais','peticoes','sentenca','decisao','recibo','outros') NOT NULL DEFAULT 'outros',
  `descricao` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `extension` VARCHAR(10) NOT NULL,
  `size` INT UNSIGNED NOT NULL DEFAULT 0,
  `visivel_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `uploaded_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: tasks
-- ============================================================
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `tipo` ENUM('ligacao','peticao','audiencia','reuniao','diligencia','financeiro','outros') NOT NULL DEFAULT 'outros',
  `case_id` INT UNSIGNED NULL,
  `client_id` INT UNSIGNED NULL,
  `responsavel_id` INT UNSIGNED NULL,
  `prazo` DATE NULL,
  `prioridade` ENUM('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
  `status` ENUM('pendente','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'pendente',
  `observacoes` TEXT NULL,
  `created_by` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`responsavel_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_status_prazo` (`status`, `prazo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: holidays
-- ============================================================
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `data` DATE NOT NULL,
  `tipo` ENUM('nacional','estadual','municipal') NOT NULL DEFAULT 'nacional',
  `estado` CHAR(2) NULL,
  `cidade` VARCHAR(100) NULL,
  `recorrente` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feriados nacionais fixos
INSERT INTO `holidays` (`nome`, `data`, `tipo`, `recorrente`) VALUES
('Confraternização Universal', '2025-01-01', 'nacional', 1),
('Tiradentes', '2025-04-21', 'nacional', 1),
('Dia do Trabalho', '2025-05-01', 'nacional', 1),
('Independência do Brasil', '2025-09-07', 'nacional', 1),
('Nossa Senhora Aparecida', '2025-10-12', 'nacional', 1),
('Finados', '2025-11-02', 'nacional', 1),
('Proclamação da República', '2025-11-15', 'nacional', 1),
('Natal', '2025-12-25', 'nacional', 1);

-- ============================================================
-- TABELA: api_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service` VARCHAR(50) NOT NULL DEFAULT 'cnj',
  `endpoint` VARCHAR(255) NULL,
  `method` VARCHAR(10) NULL,
  `request_data` TEXT NULL,
  `response_code` SMALLINT NULL,
  `response_body` LONGTEXT NULL,
  `status` ENUM('success','error','timeout') NOT NULL DEFAULT 'success',
  `error_message` TEXT NULL,
  `duration_ms` INT UNSIGNED NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_service_date` (`service`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: system_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `entity_type` VARCHAR(50) NULL,
  `entity_id` INT UNSIGNED NULL,
  `description` TEXT NULL,
  `old_data` JSON NULL,
  `new_data` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_action` (`user_id`, `action`),
  INDEX `idx_module` (`module`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NULL,
  `tipo` ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  `descricao` VARCHAR(255) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`chave`, `valor`, `tipo`, `descricao`) VALUES
('office_name', 'Escritório de Advocacia', 'string', 'Nome do escritório'),
('office_oab', '', 'string', 'Número OAB do escritório'),
('office_oab_state', '', 'string', 'Estado da OAB'),
('office_email', '', 'string', 'Email do escritório'),
('office_phone', '', 'string', 'Telefone do escritório'),
('office_address', '', 'string', 'Endereço do escritório'),
('cnj_sync_enabled', '1', 'boolean', 'Sincronização CNJ ativa'),
('cnj_last_sync', NULL, 'string', 'Data da última sincronização CNJ'),
('cnj_sync_interval', '24', 'integer', 'Intervalo de sincronização em horas'),
('session_timeout', '120', 'integer', 'Timeout da sessão em minutos'),
('max_login_attempts', '5', 'integer', 'Máximo de tentativas de login'),
('login_block_minutes', '15', 'integer', 'Minutos de bloqueio após tentativas'),
('logo_path', '', 'string', 'Caminho do logo'),
('theme_color', '#1a56db', 'string', 'Cor principal do tema');

SET FOREIGN_KEY_CHECKS = 1;
