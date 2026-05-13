
-- v52 Expansão completa do escritório
-- Migração ampla. Em MySQL antigo, execute manualmente apenas comandos ainda não existentes.

CREATE TABLE IF NOT EXISTS schema_migrations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    executed_at DATETIME NOT NULL,
    status VARCHAR(30) DEFAULT 'executed',
    message TEXT NULL
);

CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(30) DEFAULT 'error',
    message TEXT NOT NULL,
    file VARCHAR(500) NULL,
    line INT NULL,
    route VARCHAR(500) NULL,
    user_id INT NULL,
    context LONGTEXT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS legal_fee_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NULL,
    case_id INT NULL,
    administrative_procedure_id INT NULL,
    consultancy_id INT NULL,
    title VARCHAR(255) NOT NULL,
    fee_type VARCHAR(50) DEFAULT 'fixo',
    fixed_amount DECIMAL(15,2) NULL,
    monthly_amount DECIMAL(15,2) NULL,
    success_percentage DECIMAL(5,2) NULL,
    success_minimum DECIMAL(15,2) NULL,
    installments INT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status VARCHAR(50) DEFAULT 'ativo',
    contract_file VARCHAR(500) NULL,
    notes TEXT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS payable_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    supplier VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    due_date DATE NULL,
    payment_date DATE NULL,
    status VARCHAR(50) DEFAULT 'pendente',
    payment_method VARCHAR(100) NULL,
    receipt_path VARCHAR(500) NULL,
    notes TEXT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,
    source VARCHAR(100) NULL,
    area VARCHAR(100) NULL,
    legal_issue TEXT NULL,
    estimated_value DECIMAL(15,2) NULL,
    status VARCHAR(50) DEFAULT 'novo',
    first_contact_at DATETIME NULL,
    meeting_at DATETIME NULL,
    proposal_sent_at DATETIME NULL,
    converted_client_id INT NULL,
    notes TEXT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS task_checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    done TINYINT(1) DEFAULT 0,
    ordem INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS process_strategy_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    tese_principal TEXT NULL,
    tese_subsidiaria TEXT NULL,
    riscos TEXT NULL,
    provas_favoraveis TEXT NULL,
    provas_desfavoraveis TEXT NULL,
    proximos_passos TEXT NULL,
    valor_provavel DECIMAL(15,2) NULL,
    chance_acordo VARCHAR(100) NULL,
    valor_minimo_acordo DECIMAL(15,2) NULL,
    observacoes TEXT NULL,
    internal_only TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS procedural_deadline_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    days INT NOT NULL,
    business_days TINYINT(1) DEFAULT 1,
    area VARCHAR(100) NULL,
    description TEXT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS hearing_preparations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hearing_id INT NOT NULL,
    preparation_notes TEXT NULL,
    required_documents TEXT NULL,
    witness_notes TEXT NULL,
    result_notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS checklist_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    module VARCHAR(100) NULL,
    procedure_type VARCHAR(150) NULL,
    items_json LONGTEXT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    parent_id INT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS client_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    case_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    request_type VARCHAR(100) NULL,
    status VARCHAR(50) DEFAULT 'pendente',
    due_date DATE NULL,
    visible_client TINYINT(1) DEFAULT 1,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS module_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    permission_key VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uk_module_permission (module, permission_key)
);
