-- Campo para impedir sincronização automática de processos em segredo de justiça
ALTER TABLE `cases` ADD COLUMN `segredo_justica` TINYINT(1) NOT NULL DEFAULT 0 AFTER `fonte_importacao`;
