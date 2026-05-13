-- v65: settings + backup PHP compat
-- Em MySQL antigo, rode somente se as colunas não existirem.

ALTER TABLE settings ADD COLUMN tipo VARCHAR(50) DEFAULT 'string';
ALTER TABLE settings ADD COLUMN descricao TEXT NULL;
ALTER TABLE settings ADD COLUMN grupo VARCHAR(100) NULL;
ALTER TABLE settings ADD COLUMN deleted_at DATETIME NULL;

UPDATE settings SET tipo = 'string' WHERE tipo IS NULL OR tipo = '';
