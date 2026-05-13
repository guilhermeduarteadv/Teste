-- Complementos de funcionalidade: áreas, eproc, timesheet e publicações
ALTER TABLE `cases` ADD COLUMN `area` VARCHAR(80) NULL AFTER `assunto`;
ALTER TABLE `cases` ADD COLUMN `sistema` VARCHAR(30) NULL AFTER `tribunal`;
ALTER TABLE `cases` ADD COLUMN `fonte_importacao` VARCHAR(50) NULL AFTER `cnj_raw_data`;
ALTER TABLE `case_movements` ADD COLUMN `external_id` VARCHAR(100) NULL AFTER `cnj_id`;

CREATE TABLE IF NOT EXISTS `timesheets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NULL,
  `client_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `data` DATE NOT NULL,
  `inicio` TIME NULL,
  `fim` TIME NULL,
  `minutos` INT UNSIGNED NOT NULL DEFAULT 0,
  `descricao` TEXT NOT NULL,
  `atividade` VARCHAR(100) NULL,
  `valor_hora` DECIMAL(15,2) NULL DEFAULT 0.00,
  `faturavel` TINYINT(1) NOT NULL DEFAULT 1,
  `faturado` TINYINT(1) NOT NULL DEFAULT 0,
  `financial_entry_id` INT UNSIGNED NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_case_date` (`case_id`, `data`),
  INDEX `idx_client_date` (`client_id`, `data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `publications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `case_id` INT UNSIGNED NULL,
  `numero_cnj` VARCHAR(50) NULL,
  `tribunal` VARCHAR(20) NULL,
  `diario` VARCHAR(120) NULL,
  `data_publicacao` DATE NULL,
  `titulo` VARCHAR(255) NULL,
  `texto` LONGTEXT NOT NULL,
  `fonte` VARCHAR(50) NOT NULL DEFAULT 'manual',
  `hash` VARCHAR(64) NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_hash` (`hash`),
  INDEX `idx_numero` (`numero_cnj`),
  INDEX `idx_data` (`data_publicacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movimentações completas eproc/DataJud

ALTER TABLE `case_movements` MODIFY `fonte` VARCHAR(50) NOT NULL DEFAULT 'manual';
ALTER TABLE `case_movements` ADD COLUMN `evento_numero` VARCHAR(50) NULL AFTER `external_id`;
ALTER TABLE `case_movements` ADD COLUMN `documento_url` TEXT NULL AFTER `descricao`;
ALTER TABLE `case_movements` ADD COLUMN `documento_tipo` VARCHAR(100) NULL AFTER `documento_url`;
ALTER TABLE `case_movements` ADD COLUMN `usuario_origem` VARCHAR(150) NULL AFTER `documento_tipo`;
ALTER TABLE `case_movements` ADD COLUMN `conteudo` LONGTEXT NULL AFTER `usuario_origem`;
