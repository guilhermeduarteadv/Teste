
ALTER TABLE financial_entries
ADD COLUMN tipo_operacao VARCHAR(50) NULL,
ADD COLUMN valor_bruto DECIMAL(15,2) NULL,
ADD COLUMN honorario_percent DECIMAL(5,2) NULL,
ADD COLUMN honorario_valor DECIMAL(15,2) NULL,
ADD COLUMN valor_liquido DECIMAL(15,2) NULL,
ADD COLUMN comprovante_path VARCHAR(255) NULL;
