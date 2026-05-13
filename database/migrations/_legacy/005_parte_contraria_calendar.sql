-- Campos opcionais para qualificação da parte contrária
ALTER TABLE `cases` ADD COLUMN `parte_contraria_nome` VARCHAR(200) NULL AFTER `segredo_justica`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_tipo_pessoa` VARCHAR(20) NULL AFTER `parte_contraria_nome`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_cpf_cnpj` VARCHAR(30) NULL AFTER `parte_contraria_tipo_pessoa`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_rg_ie` VARCHAR(30) NULL AFTER `parte_contraria_cpf_cnpj`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_email` VARCHAR(190) NULL AFTER `parte_contraria_rg_ie`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_telefone` VARCHAR(30) NULL AFTER `parte_contraria_email`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_endereco` VARCHAR(255) NULL AFTER `parte_contraria_telefone`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_numero` VARCHAR(20) NULL AFTER `parte_contraria_endereco`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_complemento` VARCHAR(100) NULL AFTER `parte_contraria_numero`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_bairro` VARCHAR(100) NULL AFTER `parte_contraria_complemento`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_cidade` VARCHAR(100) NULL AFTER `parte_contraria_bairro`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_estado` CHAR(2) NULL AFTER `parte_contraria_cidade`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_cep` VARCHAR(10) NULL AFTER `parte_contraria_estado`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_advogado` VARCHAR(200) NULL AFTER `parte_contraria_cep`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_advogado_oab` VARCHAR(50) NULL AFTER `parte_contraria_advogado`;
ALTER TABLE `cases` ADD COLUMN `parte_contraria_observacoes` TEXT NULL AFTER `parte_contraria_advogado_oab`;
