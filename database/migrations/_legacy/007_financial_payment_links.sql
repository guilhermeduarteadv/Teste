-- 007 - Vínculo de pagamentos/parcela com cobrança principal
ALTER TABLE `financial_entries`
  ADD COLUMN `parent_entry_id` INT UNSIGNED NULL AFTER `case_id`,
  ADD COLUMN `is_payment` TINYINT(1) NOT NULL DEFAULT 0 AFTER `parent_entry_id`;

ALTER TABLE `financial_entries`
  MODIFY `status` ENUM('pendente','pago','vencido','cancelado','parcial') NOT NULL DEFAULT 'pendente';

CREATE INDEX `idx_fin_parent` ON `financial_entries` (`parent_entry_id`);
