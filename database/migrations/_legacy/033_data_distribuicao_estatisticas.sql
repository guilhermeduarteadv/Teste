-- Migração: data de distribuição e estatísticas de processos
-- Compatível com MySQL/MariaDB usado no AppServ.

ALTER TABLE cases
    ADD COLUMN IF NOT EXISTS data_distribuicao DATE NULL AFTER numero_cnj,
    ADD COLUMN IF NOT EXISTS valor_causa DECIMAL(15,2) NULL AFTER data_distribuicao,
    ADD COLUMN IF NOT EXISTS comarca VARCHAR(255) NULL AFTER valor_causa,
    ADD COLUMN IF NOT EXISTS vara VARCHAR(255) NULL AFTER comarca;

-- Índices úteis para relatórios
CREATE INDEX IF NOT EXISTS idx_cases_data_distribuicao ON cases (data_distribuicao);
CREATE INDEX IF NOT EXISTS idx_cases_area ON cases (area);
CREATE INDEX IF NOT EXISTS idx_cases_comarca ON cases (comarca);
