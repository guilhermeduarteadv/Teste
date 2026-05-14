<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class SchemaGuardService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function ensureV52Schema(): void
    {
        $this->ensureV49Schema();

        try { $this->createV52Tables(); } catch (\Throwable $e) {}
        try { $this->ensureV62FullCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureV52SoftDeleteColumns(); } catch (\Throwable $e) {}
        try { $this->ensureTaskCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureSettingsCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureDeadlineCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureHearingCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureHearingClientCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureV62FullCompatibilityColumns(); } catch (\Throwable $e) {}
    }

    public function ensureV49Schema(): void
    {
        $this->ensureV42Schema();
        try { $this->createEnabledTribunals(); } catch (\Throwable $e) {}
        try { $this->ensureAdministrativeCartorioColumns(); } catch (\Throwable $e) {}
    }

    public function ensureV42Schema(): void
    {
        try { $this->createAdministrativeProcedures(); } catch (\Throwable $e) {}
        try { $this->createLegalConsultancies(); } catch (\Throwable $e) {}
        try { $this->ensureFinancialColumns(); } catch (\Throwable $e) {}
        try { $this->ensureDocumentColumns(); } catch (\Throwable $e) {}
    }

    public function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function columnExists(string $table, string $column): bool
    {
        try {
            if (!$this->tableExists($table)) return false;
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        try {
            if ($this->tableExists($table) && !$this->columnExists($table, $column)) {
                $this->db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            }
        } catch (\Throwable $e) {}
    }

    private function createAdministrativeProcedures(): void
    {
        try {
            $this->db->exec("
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
                    INDEX idx_admin_proc_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}
    }

    private function createLegalConsultancies(): void
    {
        try {
            $this->db->exec("
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
                    INDEX idx_consult_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}
    }

    private function ensureFinancialColumns(): void
    {
        $this->addColumnIfMissing('financial_entries', 'entity_type', 'VARCHAR(50) NULL');
        $this->addColumnIfMissing('financial_entries', 'entity_id', 'INT NULL');
        $this->addColumnIfMissing('financial_entries', 'administrative_procedure_id', 'INT NULL');
        $this->addColumnIfMissing('financial_entries', 'consultancy_id', 'INT NULL');
    }

    private function ensureDocumentColumns(): void
    {
        $this->addColumnIfMissing('documents', 'uploaded_by_client_id', 'INT NULL');
        $this->addColumnIfMissing('documents', 'origem_upload', 'VARCHAR(50) NULL');
        $this->addColumnIfMissing('documents', 'tamanho_bytes', 'INT NULL');
    }

    private function createEnabledTribunals(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS enabled_tribunals (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(30) NOT NULL UNIQUE,
                    name VARCHAR(120) NOT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 1,
                    ordem INT DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $count = (int)$this->db->query("SELECT COUNT(*) FROM enabled_tribunals")->fetchColumn();
            if ($count === 0) {
                $tribunais = ['TJSP','TRF3','TRT2','TRT15','STJ','STF','TST','TSE','STM'];
                $stmt = $this->db->prepare("INSERT INTO enabled_tribunals (code, name, enabled, ordem, created_at, updated_at) VALUES (?, ?, 1, ?, NOW(), NOW())");
                foreach ($tribunais as $i => $t) {
                    $stmt->execute([$t, $t, $i + 1]);
                }
            }
        } catch (\Throwable $e) {}
    }

    private function ensureAdministrativeCartorioColumns(): void
    {
        $this->addColumnIfMissing('administrative_procedures', 'natureza', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_nome', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_cnpj', "VARCHAR(30) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_oficial', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_livro', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_folha', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_matricula', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('administrative_procedures', 'cartorio_ato', "VARCHAR(150) NULL");
    }


    private function createV52Tables(): void
    {
        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS checklist_templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    module VARCHAR(100) NULL,
                    procedure_type VARCHAR(150) NULL,
                    items_json LONGTEXT NULL,
                    active TINYINT(1) DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS schema_migrations_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    executed_at DATETIME NOT NULL,
                    status VARCHAR(30) DEFAULT 'executed',
                    message TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS task_comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    task_id INT NOT NULL,
                    user_id INT NULL,
                    comment TEXT NOT NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS task_checklist_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    task_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    done TINYINT(1) DEFAULT 0,
                    ordem INT DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS hearing_preparations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    hearing_id INT NOT NULL,
                    preparation_notes TEXT NULL,
                    required_documents TEXT NULL,
                    witness_notes TEXT NULL,
                    result_notes TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS document_categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    parent_id INT NULL,
                    active TINYINT(1) DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS module_permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    module VARCHAR(100) NOT NULL,
                    permission_key VARCHAR(150) NOT NULL,
                    description VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL,
                    UNIQUE KEY uk_module_permission (module, permission_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}
    }


    private function ensureV52SoftDeleteColumns(): void
    {
        // Algumas tabelas da expansão v52 foram criadas sem deleted_at,
        // mas o Model::findAll() padrão do sistema filtra por deleted_at IS NULL.
        $this->addColumnIfMissing('checklist_templates', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('client_requests', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('task_comments', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('task_checklist_items', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('procedural_deadline_rules', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('hearing_preparations', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('document_categories', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('module_permissions', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('schema_migrations_log', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('error_logs', 'deleted_at', "DATETIME NULL");
    }


    private function ensureTaskCompatibilityColumns(): void
    {
        // Compatibilidade dupla: versões antigas usam prazo/hora/prioridade/responsavel_id;
        // versões novas podem usar due_date/due_time/priority/responsible_id.
        $this->addColumnIfMissing('tasks', 'prazo', "DATE NULL");
        $this->addColumnIfMissing('tasks', 'hora', "TIME NULL");
        $this->addColumnIfMissing('tasks', 'prioridade', "VARCHAR(50) DEFAULT 'media'");
        $this->addColumnIfMissing('tasks', 'responsavel_id', "INT NULL");

        $this->addColumnIfMissing('tasks', 'due_date', "DATE NULL");
        $this->addColumnIfMissing('tasks', 'due_time', "TIME NULL");
        $this->addColumnIfMissing('tasks', 'priority', "VARCHAR(50) DEFAULT 'media'");
        $this->addColumnIfMissing('tasks', 'responsible_id', "INT NULL");
        $this->addColumnIfMissing('tasks', 'description', "TEXT NULL");
        $this->addColumnIfMissing('tasks', 'descricao', "TEXT NULL");
        $this->addColumnIfMissing('tasks', 'type', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('tasks', 'tipo', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('tasks', 'local', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('tasks', 'completed_at', "DATETIME NULL");

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET prazo = due_date WHERE prazo IS NULL AND due_date IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET due_date = prazo WHERE due_date IS NULL AND prazo IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET hora = due_time WHERE hora IS NULL AND due_time IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET due_time = hora WHERE due_time IS NULL AND hora IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET prioridade = priority WHERE prioridade IS NULL AND priority IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET priority = prioridade WHERE priority IS NULL AND prioridade IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET responsavel_id = responsible_id WHERE responsavel_id IS NULL AND responsible_id IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('tasks')) {
                $this->db->exec("UPDATE tasks SET responsible_id = responsavel_id WHERE responsible_id IS NULL AND responsavel_id IS NOT NULL");
            }
        } catch (\Throwable $e) {}
    }

    private function ensureDeadlineCompatibilityColumns(): void
    {
        $this->addColumnIfMissing('case_deadlines', 'tipo', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('case_deadlines', 'prazo', "DATE NULL");

        try {
            if ($this->tableExists('case_deadlines')) {
                $this->db->exec("UPDATE case_deadlines SET prazo = data_final WHERE prazo IS NULL AND data_final IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('case_deadlines')) {
                $this->db->exec("UPDATE case_deadlines SET data_final = prazo WHERE data_final IS NULL AND prazo IS NOT NULL");
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('case_deadlines')) {
                $this->db->exec("UPDATE case_deadlines SET tipo = title WHERE tipo IS NULL AND title IS NOT NULL");
            }
        } catch (\Throwable $e) {}
    }


    private function ensureHearingCompatibilityColumns(): void
    {
        $this->addColumnIfMissing('case_hearings', 'status', "VARCHAR(50) DEFAULT 'agendada'");

        try {
            if ($this->tableExists('case_hearings')) {
                $this->db->exec("UPDATE case_hearings SET status = 'agendada' WHERE status IS NULL OR status = ''");
            }
        } catch (\Throwable $e) {}
    }


    private function ensureHearingClientCompatibilityColumns(): void
    {
        $this->addColumnIfMissing('case_hearings', 'client_id', "INT NULL");

        try {
            if ($this->tableExists('case_hearings') && $this->tableExists('case_clients')) {
                $this->db->exec("
                    UPDATE case_hearings h
                    LEFT JOIN case_clients cc ON cc.case_id = h.case_id
                    SET h.client_id = cc.client_id
                    WHERE h.client_id IS NULL AND cc.client_id IS NOT NULL
                ");
            }
        } catch (\Throwable $e) {}
    }


    private function ensureV62FullCompatibilityColumns(): void
    {
        // Colunas mais antigas/novas consultadas pelo código atual.
        $this->addColumnIfMissing('case_clients', 'tipo', "VARCHAR(80) DEFAULT 'autor'");
        $this->addColumnIfMissing('case_clients', 'participacao', "VARCHAR(80) DEFAULT 'autor'");
        $this->addColumnIfMissing('case_movements', 'fonte', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('case_movements', 'hash', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('case_movements', 'external_id', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('case_movements', 'cnj_id', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('case_movements', 'evento_numero', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('case_movements', 'documento_tipo', "VARCHAR(150) NULL");
        $this->addColumnIfMissing('case_movements', 'documento_url', "VARCHAR(500) NULL");
        $this->addColumnIfMissing('case_movements', 'conteudo', "TEXT NULL");
        $this->addColumnIfMissing('case_movements', 'usuario_origem', "VARCHAR(255) NULL");

        $this->addColumnIfMissing('case_deadlines', 'prazo_dias', "INT NULL");
        $this->addColumnIfMissing('case_deadlines', 'confirmado', "TINYINT(1) DEFAULT 0");
        $this->addColumnIfMissing('case_deadlines', 'confirmado_por', "INT NULL");
        $this->addColumnIfMissing('case_deadlines', 'observacoes', "TEXT NULL");
        $this->addColumnIfMissing('case_deadlines', 'created_by', "INT NULL");

        $this->addColumnIfMissing('case_hearings', 'created_by', "INT NULL");
        $this->addColumnIfMissing('case_hearings', 'link', "VARCHAR(500) NULL");
        $this->addColumnIfMissing('case_hearings', 'modalidade', "VARCHAR(50) NULL");

        $this->addColumnIfMissing('documents', 'descricao', "TEXT NULL");
        $this->addColumnIfMissing('documents', 'titulo', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('documents', 'case_id', "INT NULL");
        $this->addColumnIfMissing('documents', 'confirmado_por', "INT NULL");
        $this->addColumnIfMissing('documents', 'status', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('documents', 'imported', "TINYINT(1) DEFAULT 0");

        $this->addColumnIfMissing('financial_entries', 'created_by', "INT NULL");
        $this->addColumnIfMissing('financial_entries', 'is_payment', "TINYINT(1) DEFAULT 0");
        $this->addColumnIfMissing('financial_entries', 'observacoes', "TEXT NULL");

        $this->addColumnIfMissing('cases', 'sistema', "VARCHAR(80) NULL");
        $this->addColumnIfMissing('cases', 'cnj_raw_data', "LONGTEXT NULL");
        $this->addColumnIfMissing('cases', 'fonte_importacao', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('cases', 'last_sync_at', "DATETIME NULL");
        $this->addColumnIfMissing('cases', 'last_movement_hash', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('cases', 'parte_contraria_tipo_pessoa', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('cases', 'parte_contraria_advogado_oab', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('cases', 'parte_contraria_observacoes', "TEXT NULL");
    }


    public function ensureV62FullRuntimeSchema(): void
    {
        $this->ensureV52Schema();

        try { $this->createRolesTable(); } catch (\Throwable $e) {}
        try { $this->ensureUsersCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureTribunalConnectionsCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureTaskCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureSettingsCompatibilityColumns(); } catch (\Throwable $e) {}
        try { $this->ensureSystemCheckTables(); } catch (\Throwable $e) {}
        try { $this->ensureNotificationsTable(); } catch (\Throwable $e) {}
        try { $this->ensureDashboardTables(); } catch (\Throwable $e) {}
        try { $this->ensurePhase3Tables(); } catch (\Throwable $e) {}
        try { $this->ensureDeadlinePhase3Columns(); } catch (\Throwable $e) {}
        try { $this->ensurePhase2Tables(); } catch (\Throwable $e) {}
        try { $this->ensurePhase4Tables(); } catch (\Throwable $e) {}
    }

    public function ensurePhase2Tables(): void
    {
        // 2.14 Busca Global
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS search_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    query VARCHAR(500) NOT NULL,
                    results_count INT NOT NULL DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    INDEX idx_search_logs_user (user_id),
                    INDEX idx_search_logs_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.2 Gerador de Documentos
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS generated_documents (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    template_id INT NULL,
                    entity_type VARCHAR(50) NULL,
                    entity_id INT NULL,
                    client_id INT NULL,
                    case_id INT NULL,
                    title VARCHAR(255) NOT NULL,
                    output_format VARCHAR(20) DEFAULT 'html',
                    file_path VARCHAR(500) NULL,
                    created_by INT NULL,
                    created_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_gen_docs_template (template_id),
                    INDEX idx_gen_docs_client (client_id),
                    INDEX idx_gen_docs_case (case_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // Adicionar colunas ao legal_templates via addColumnIfMissing
        $this->addColumnIfMissing('legal_templates', 'variables_json', 'TEXT NULL');
        $this->addColumnIfMissing('legal_templates', 'active', 'TINYINT(1) DEFAULT 1');

        // 2.3 Histórico / Timeline do Cliente
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS client_timeline (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NOT NULL,
                    event_date DATETIME NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    source VARCHAR(50) DEFAULT 'manual',
                    source_id INT NULL,
                    visible_client TINYINT(1) NOT NULL DEFAULT 1,
                    created_by INT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_timeline_client (client_id),
                    INDEX idx_timeline_date (event_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS client_notes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NOT NULL,
                    note TEXT NOT NULL,
                    visibility VARCHAR(20) DEFAULT 'internal',
                    created_by INT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_notes_client (client_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.4 Agenda Jurídica Unificada
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS calendar_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    start_at DATETIME NOT NULL,
                    end_at DATETIME NULL,
                    all_day TINYINT(1) NOT NULL DEFAULT 0,
                    location VARCHAR(255) NULL,
                    event_type VARCHAR(50) DEFAULT 'outro',
                    entity_type VARCHAR(50) NULL,
                    entity_id INT NULL,
                    client_id INT NULL,
                    case_id INT NULL,
                    responsible_id INT NULL,
                    status VARCHAR(30) DEFAULT 'pendente',
                    created_by INT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_cal_events_start (start_at),
                    INDEX idx_cal_events_client (client_id),
                    INDEX idx_cal_events_case (case_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // schema_version record
        try {
            $this->db->exec("INSERT IGNORE INTO schema_version (version) VALUES ('v40-search-templates-timeline-calendar')");
        } catch (\Throwable $e) {}
    }

    /**
     * Phase 3+5: cria tabelas dos módulos Provas (2.11), Estratégia (2.12),
     * Prazos (2.5) e Jurisprudência (2.18).
     */
    public function ensurePhase3Tables(): void
    {
        // 2.11 — Provas
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS case_evidence (
                id INT AUTO_INCREMENT PRIMARY KEY,
                case_id INT NOT NULL,
                document_id INT DEFAULT NULL,
                evidence_type VARCHAR(50) DEFAULT 'documento',
                title VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                probative_strength VARCHAR(20) DEFAULT 'media',
                legal_note VARCHAR(500) DEFAULT NULL,
                visible_client TINYINT(1) DEFAULT 0,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                INDEX idx_evidence_case (case_id),
                INDEX idx_evidence_type (evidence_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2.12 — Estratégia Processual
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS process_strategy_notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                case_id INT NOT NULL,
                tese_principal TEXT DEFAULT NULL,
                tese_subsidiaria TEXT DEFAULT NULL,
                riscos TEXT DEFAULT NULL,
                provas_favoraveis TEXT DEFAULT NULL,
                provas_desfavoraveis TEXT DEFAULT NULL,
                proximos_passos TEXT DEFAULT NULL,
                valor_provavel DECIMAL(15,2) DEFAULT NULL,
                chance_acordo TINYINT(3) DEFAULT NULL,
                valor_minimo_acordo DECIMAL(15,2) DEFAULT NULL,
                observacoes TEXT DEFAULT NULL,
                internal_only TINYINT(1) DEFAULT 1,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                INDEX idx_strategy_case (case_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2.5 — Feriados
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS holidays (
                id INT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                name VARCHAR(200) NOT NULL,
                scope VARCHAR(20) DEFAULT 'nacional',
                state CHAR(2) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                INDEX idx_holiday_date (date),
                INDEX idx_holiday_scope (scope)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2.5 — Suspensões de Prazo
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS deadline_suspensions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                reason VARCHAR(255) DEFAULT NULL,
                scope VARCHAR(20) DEFAULT 'nacional',
                state CHAR(2) DEFAULT NULL,
                city VARCHAR(100) DEFAULT NULL,
                active TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT NULL,
                INDEX idx_suspension_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2.18 — Jurisprudência
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS jurisprudence_library (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                court VARCHAR(100) DEFAULT NULL,
                area VARCHAR(80) DEFAULT NULL,
                theme VARCHAR(150) DEFAULT NULL,
                summary TEXT DEFAULT NULL,
                ementa TEXT DEFAULT NULL,
                link VARCHAR(500) DEFAULT NULL,
                decision_date DATE DEFAULT NULL,
                tags VARCHAR(500) DEFAULT NULL,
                used_in_case_id INT DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                INDEX idx_juris_area (area),
                INDEX idx_juris_court (court)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2.18 — Teses Jurídicas
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS legal_theses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                area VARCHAR(80) DEFAULT NULL,
                thesis_text TEXT DEFAULT NULL,
                legal_basis VARCHAR(500) DEFAULT NULL,
                tags VARCHAR(500) DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_at DATETIME DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL,
                deleted_at DATETIME DEFAULT NULL,
                INDEX idx_thesis_area (area)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Garante novas colunas em case_deadlines para o módulo de Prazos (2.5).
     */
    private function ensureDeadlinePhase3Columns(): void
    {
        $this->addColumnIfMissing('case_deadlines', 'calculation_method', "VARCHAR(20) DEFAULT 'corridos'");
        $this->addColumnIfMissing('case_deadlines', 'business_days',      "TINYINT(1) DEFAULT 0");
        $this->addColumnIfMissing('case_deadlines', 'start_count_at',     "DATE NULL");
        $this->addColumnIfMissing('case_deadlines', 'calculated_at',      "DATETIME NULL");
        $this->addColumnIfMissing('case_deadlines', 'checked_by',         "INT NULL");
        $this->addColumnIfMissing('case_deadlines', 'checked_at',         "DATETIME NULL");
        $this->addColumnIfMissing('case_deadlines', 'source_type',        "VARCHAR(50) NULL");
        $this->addColumnIfMissing('case_deadlines', 'source_id',          "INT NULL");
    }

    public function ensureDashboardTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dashboard_widgets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                widget_key VARCHAR(100) NOT NULL,
                position INT DEFAULT 0,
                visible TINYINT(1) DEFAULT 1,
                config_json TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_widget_user (user_id, widget_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dashboard_snapshots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                snapshot_date DATE NOT NULL,
                data_json LONGTEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_snap_date (snapshot_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function ensureSystemCheckTables(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS system_check_runs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                status VARCHAR(20) DEFAULT 'ok',
                started_at DATETIME NOT NULL,
                finished_at DATETIME NOT NULL,
                total_errors INT DEFAULT 0,
                total_warnings INT DEFAULT 0,
                result_json LONGTEXT NULL,
                created_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS system_check_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                run_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                expected VARCHAR(255) NULL,
                actual VARCHAR(255) NULL,
                status VARCHAR(20) DEFAULT 'ok',
                repair_action VARCHAR(500) NULL,
                message TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sci_run (run_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function ensureNotificationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NULL,
                type VARCHAR(50) DEFAULT 'info',
                priority VARCHAR(20) DEFAULT 'normal',
                entity_type VARCHAR(100) NULL,
                entity_id INT NULL,
                read_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX idx_notif_user (user_id),
                INDEX idx_notif_read (read_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function createRolesTable(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    label VARCHAR(150) NOT NULL,
                    description TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $stmt = $this->db->prepare("
                INSERT IGNORE INTO roles (id, name, label, description, created_at) VALUES
                (1, 'admin', 'Administrador', 'Acesso total ao sistema', NOW()),
                (2, 'lawyer', 'Advogado', 'Usuário advogado', NOW()),
                (3, 'assistant', 'Assistente', 'Usuário assistente', NOW()),
                (4, 'client', 'Cliente', 'Acesso ao portal do cliente', NOW())
            ");
            $stmt->execute();

            if ($this->tableExists('users')) {
                $this->db->exec("UPDATE users SET role_id = 1 WHERE (role_id IS NULL OR role_id = 0) AND (role = 'admin' OR role IS NULL OR role = '')");
            }
        } catch (\Throwable $e) {}
    }

    private function ensureUsersCompatibilityColumns(): void
    {
        $this->addColumnIfMissing('users', 'role_id', "INT NULL");
        $this->addColumnIfMissing('users', 'cargo', "VARCHAR(150) NULL");
        $this->addColumnIfMissing('users', 'oab_number', "VARCHAR(30) NULL");
        $this->addColumnIfMissing('users', 'oab_state', "VARCHAR(2) NULL");
        $this->addColumnIfMissing('users', 'phone', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('users', 'last_login', "DATETIME NULL");
        $this->addColumnIfMissing('users', 'login_attempts', "INT DEFAULT 0");
        $this->addColumnIfMissing('users', 'blocked_until', "DATETIME NULL");
        $this->addColumnIfMissing('users', 'last_login_ip', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('users', 'password_reset_token', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('users', 'password_reset_expires', "DATETIME NULL");

        try {
            if ($this->tableExists('users')) {
                $this->db->exec("UPDATE users SET role_id = 1 WHERE role_id IS NULL OR role_id = 0");
            }
        } catch (\Throwable $e) {}
    }

    private function ensureTribunalConnectionsCompatibilityColumns(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS tribunal_connections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    tribunal VARCHAR(50) NULL,
                    sistema VARCHAR(100) NULL,
                    username VARCHAR(255) NULL,
                    encrypted_password TEXT NULL,
                    encrypted_cookies LONGTEXT NULL,
                    has_password TINYINT(1) DEFAULT 0,
                    has_cookies TINYINT(1) DEFAULT 0,
                    oab_number VARCHAR(50) NULL,
                    oab_state VARCHAR(2) NULL,
                    status VARCHAR(50) DEFAULT 'ativo',
                    last_test_at DATETIME NULL,
                    last_sync_at DATETIME NULL,
                    last_error TEXT NULL,
                    expires_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        $this->addColumnIfMissing('tribunal_connections', 'last_test_at', "DATETIME NULL");
        $this->addColumnIfMissing('tribunal_connections', 'last_sync_at', "DATETIME NULL");
        $this->addColumnIfMissing('tribunal_connections', 'last_error', "TEXT NULL");
        $this->addColumnIfMissing('tribunal_connections', 'expires_at', "DATETIME NULL");
        $this->addColumnIfMissing('tribunal_connections', 'deleted_at', "DATETIME NULL");
    }


    private function ensureSettingsCompatibilityColumns(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chave VARCHAR(150) NOT NULL UNIQUE,
                    valor LONGTEXT NULL,
                    tipo VARCHAR(50) DEFAULT 'string',
                    descricao TEXT NULL,
                    grupo VARCHAR(100) NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        $this->addColumnIfMissing('settings', 'tipo', "VARCHAR(50) DEFAULT 'string'");
        $this->addColumnIfMissing('settings', 'descricao', "TEXT NULL");
        $this->addColumnIfMissing('settings', 'grupo', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('settings', 'created_at', "DATETIME NULL");
        $this->addColumnIfMissing('settings', 'updated_at', "DATETIME NULL");
        $this->addColumnIfMissing('settings', 'deleted_at', "DATETIME NULL");

        try {
            if ($this->tableExists('settings')) {
                $this->db->exec("UPDATE settings SET tipo = 'string' WHERE tipo IS NULL OR tipo = ''");
            }
        } catch (\Throwable $e) {}
    }

    public function ensurePhase4Tables(): void
    {
        // 2.7 — Partes do processo (DataJud parte contrária)
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS case_parties (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    case_id INT NOT NULL,
                    tipo VARCHAR(50) DEFAULT 'reu',
                    polo VARCHAR(20) DEFAULT 'passivo',
                    nome VARCHAR(255) NOT NULL,
                    cpf_cnpj VARCHAR(30) NULL,
                    advogado VARCHAR(255) NULL,
                    advogado_oab VARCHAR(50) NULL,
                    observacoes TEXT NULL,
                    source VARCHAR(30) DEFAULT 'manual',
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_parties_case (case_id),
                    INDEX idx_parties_tipo (tipo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.9 — Repasses ao cliente
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS client_repasses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    client_id INT NULL,
                    case_id INT NULL,
                    valor_recebido DECIMAL(15,2) NOT NULL DEFAULT 0,
                    valor_repassado DECIMAL(15,2) NOT NULL DEFAULT 0,
                    percentual_honorarios DECIMAL(5,2) NOT NULL DEFAULT 0,
                    data_recebimento DATE NULL,
                    data_repasse DATE NULL,
                    descricao TEXT NULL,
                    status VARCHAR(30) DEFAULT 'pendente',
                    created_by INT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_repasses_client (client_id),
                    INDEX idx_repasses_case (case_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.9 — Comprovantes financeiros (múltiplos por lançamento)
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS financial_receipts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    financial_entry_id INT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    file_name VARCHAR(255) NULL,
                    file_type VARCHAR(50) NULL,
                    description VARCHAR(255) NULL,
                    uploaded_by INT NULL,
                    created_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_receipts_entry (financial_entry_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.17 — Itens de checklist por processo
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS case_checklist_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    case_id INT NOT NULL,
                    template_id INT NULL,
                    item_text VARCHAR(500) NOT NULL,
                    completed TINYINT(1) NOT NULL DEFAULT 0,
                    completed_by INT NULL,
                    completed_at DATETIME NULL,
                    due_date DATE NULL,
                    sort_order INT DEFAULT 0,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_checklist_case (case_id),
                    INDEX idx_checklist_tpl (template_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.19 — Snapshots de produtividade
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS productivity_snapshots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    snapshot_date DATE NOT NULL,
                    tasks_completed INT DEFAULT 0,
                    tasks_overdue INT DEFAULT 0,
                    hours_logged DECIMAL(6,2) DEFAULT 0,
                    cases_active INT DEFAULT 0,
                    deadlines_met INT DEFAULT 0,
                    deadlines_missed INT DEFAULT 0,
                    data_json TEXT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX idx_prod_snap_user (user_id),
                    INDEX idx_prod_snap_date (snapshot_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.13 — Relatórios gerados
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS generated_reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    report_type VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    entity_type VARCHAR(50) NULL,
                    entity_id INT NULL,
                    file_path VARCHAR(500) NULL,
                    output_format VARCHAR(20) DEFAULT 'html',
                    params_json TEXT NULL,
                    created_by INT NULL,
                    created_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_gen_reports_type (report_type),
                    INDEX idx_gen_reports_entity (entity_type, entity_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.16 — Logs de acesso a documentos (auditoria)
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS document_access_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    document_id INT NULL,
                    user_id INT NULL,
                    client_id INT NULL,
                    action VARCHAR(50) DEFAULT 'view',
                    ip_address VARCHAR(50) NULL,
                    user_agent VARCHAR(500) NULL,
                    accessed_at DATETIME NOT NULL,
                    INDEX idx_doc_access_doc (document_id),
                    INDEX idx_doc_access_user (user_id),
                    INDEX idx_doc_access_date (accessed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.16 — Exportações de dados (LGPD)
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS data_exports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    entity_type VARCHAR(50) NOT NULL,
                    entity_id INT NOT NULL,
                    requested_by INT NULL,
                    file_path VARCHAR(500) NULL,
                    status VARCHAR(30) DEFAULT 'pending',
                    expires_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    completed_at DATETIME NULL,
                    INDEX idx_data_exports_entity (entity_type, entity_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // 2.10 — Mensagens do portal (cliente ↔ escritório)
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS portal_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NULL,
                    client_id INT NULL,
                    case_id INT NULL,
                    sender_type VARCHAR(20) DEFAULT 'client',
                    sender_id INT NULL,
                    message TEXT NOT NULL,
                    read_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    deleted_at DATETIME NULL,
                    INDEX idx_portal_msg_request (request_id),
                    INDEX idx_portal_msg_client (client_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        // Coluna status na tabela publications (2.6)
        $this->addColumnIfMissing('publications', 'status', "VARCHAR(30) DEFAULT 'pending'");

        // Garante deleted_at em tabelas que podem ter sido criadas sem ela
        $this->addColumnIfMissing('checklist_templates', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('checklist_templates', 'items_json', "TEXT NULL");
        $this->addColumnIfMissing('checklist_templates', 'active', "TINYINT(1) DEFAULT 1");
        $this->addColumnIfMissing('client_requests', 'deleted_at', "DATETIME NULL");
        $this->addColumnIfMissing('case_checklist_items', 'deleted_at', "DATETIME NULL");

        try {
            $this->db->exec("INSERT IGNORE INTO schema_version (version) VALUES ('v41-phase4-parties-repasses-checklists')");
        } catch (\Throwable $e) {}
    }

}
