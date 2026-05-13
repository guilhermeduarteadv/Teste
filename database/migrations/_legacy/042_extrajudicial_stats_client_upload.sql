-- v42: módulos extrajudiciais, estatísticas e upload de documentos pelo cliente
-- Compatível com MySQL/MariaDB antigo do AppServ. Rode comandos individualmente se alguma coluna já existir.

CREATE TABLE IF NOT EXISTS administrative_procedures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NULL,
    titulo VARCHAR(255) NOT NULL,
    numero_processo VARCHAR(100) NULL,
    prefeitura VARCHAR(255) NULL,
    secretaria VARCHAR(255) NULL,
    setor VARCHAR(255) NULL,
    tipo_procedimento VARCHAR(150) NULL,
    assunto VARCHAR(255) NULL,
    status VARCHAR(50) DEFAULT 'ativo',
    data_protocolo DATE NULL,
    prazo_resposta DATE NULL,
    valor_estimado DECIMAL(15,2) NULL,
    portal_url VARCHAR(500) NULL,
    portal_login VARCHAR(255) NULL,
    portal_password_encrypted TEXT NULL,
    responsavel_id INT NULL,
    observacoes TEXT NULL,
    created_by INT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_admin_proc_client (client_id),
    INDEX idx_admin_proc_status (status),
    INDEX idx_admin_proc_data (data_protocolo)
);

CREATE TABLE IF NOT EXISTS legal_consultancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NULL,
    titulo VARCHAR(255) NOT NULL,
    area VARCHAR(100) NULL,
    tipo_consultoria VARCHAR(150) NULL,
    descricao TEXT NULL,
    status VARCHAR(50) DEFAULT 'em_andamento',
    data_inicio DATE NULL,
    data_conclusao DATE NULL,
    valor_estimado DECIMAL(15,2) NULL,
    responsavel_id INT NULL,
    observacoes TEXT NULL,
    created_by INT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_consult_client (client_id),
    INDEX idx_consult_status (status),
    INDEX idx_consult_data (data_inicio)
);

CREATE TABLE IF NOT EXISTS office_stats_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL,
    valor_json LONGTEXT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uk_stats_chave (chave)
);

-- Extensão do financeiro para vínculo polimórfico.
ALTER TABLE financial_entries ADD COLUMN entity_type VARCHAR(50) NULL;
ALTER TABLE financial_entries ADD COLUMN entity_id INT NULL;
ALTER TABLE financial_entries ADD COLUMN administrative_procedure_id INT NULL;
ALTER TABLE financial_entries ADD COLUMN consultancy_id INT NULL;

CREATE INDEX idx_fin_entity ON financial_entries (entity_type, entity_id);
CREATE INDEX idx_fin_admin_proc ON financial_entries (administrative_procedure_id);
CREATE INDEX idx_fin_consultancy ON financial_entries (consultancy_id);

-- Extensão dos documentos para upload do cliente.
ALTER TABLE documents ADD COLUMN uploaded_by_client_id INT NULL;
ALTER TABLE documents ADD COLUMN origem_upload VARCHAR(50) NULL;
ALTER TABLE documents ADD COLUMN tamanho_bytes INT NULL;
