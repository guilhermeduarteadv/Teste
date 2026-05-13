-- Campos de horário e local para tarefas.
-- Execute manualmente se o instalador não aplicar migrations incrementais.

SET @has_hora := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'hora'
);
SET @sql := IF(@has_hora = 0, 'ALTER TABLE `tasks` ADD COLUMN `hora` TIME NULL AFTER `prazo`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_local := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tasks' AND COLUMN_NAME = 'local'
);
SET @sql := IF(@has_local = 0, 'ALTER TABLE `tasks` ADD COLUMN `local` VARCHAR(255) NULL AFTER `hora`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
