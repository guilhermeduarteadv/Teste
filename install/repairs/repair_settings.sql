-- Correção manual da tabela settings
-- Rode somente as colunas que ainda não existirem se seu MySQL reclamar de duplicidade.

ALTER TABLE settings ADD COLUMN tipo VARCHAR(50) DEFAULT 'string';
ALTER TABLE settings ADD COLUMN descricao TEXT NULL;
ALTER TABLE settings ADD COLUMN grupo VARCHAR(100) NULL;
ALTER TABLE settings ADD COLUMN created_at DATETIME NULL;
ALTER TABLE settings ADD COLUMN updated_at DATETIME NULL;
ALTER TABLE settings ADD COLUMN deleted_at DATETIME NULL;

UPDATE settings SET tipo = 'string' WHERE tipo IS NULL OR tipo = '';
