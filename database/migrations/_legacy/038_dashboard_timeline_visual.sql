-- v37 corrigido: versão compatível com MySQL/AppServ sem IF NOT EXISTS em ALTER TABLE.
-- Execute apenas os comandos das colunas/índices que ainda não existirem.

ALTER TABLE case_timeline ADD COLUMN source VARCHAR(50) NULL AFTER case_id;
ALTER TABLE case_timeline ADD COLUMN source_id INT NULL AFTER source;
ALTER TABLE case_timeline ADD COLUMN event_type VARCHAR(100) NULL AFTER description;
ALTER TABLE case_timeline ADD COLUMN client_description TEXT NULL AFTER description;
ALTER TABLE case_timeline ADD COLUMN progress_percent INT DEFAULT 0 AFTER event_type;
ALTER TABLE case_timeline ADD COLUMN hash VARCHAR(64) NULL AFTER progress_percent;

CREATE INDEX idx_case_timeline_hash ON case_timeline (hash);
CREATE INDEX idx_case_timeline_important ON case_timeline (is_important);
CREATE INDEX idx_case_timeline_visible_client ON case_timeline (visible_client);
CREATE INDEX idx_financial_entries_data_pagamento ON financial_entries (data_pagamento);
CREATE INDEX idx_financial_entries_status ON financial_entries (status);
