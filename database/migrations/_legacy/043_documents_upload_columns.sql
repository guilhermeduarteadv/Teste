-- v43: correções complementares
-- Execute apenas se as colunas ainda não existirem.

ALTER TABLE documents ADD COLUMN tamanho_bytes INT NULL;
ALTER TABLE documents ADD COLUMN uploaded_by_client_id INT NULL;
ALTER TABLE documents ADD COLUMN origem_upload VARCHAR(50) NULL;

-- Depois de adicionar tamanho_bytes, opcionalmente copie o tamanho antigo:
UPDATE documents SET tamanho_bytes = size WHERE tamanho_bytes IS NULL;
