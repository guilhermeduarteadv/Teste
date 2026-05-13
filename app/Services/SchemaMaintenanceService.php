<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Logger;

class SchemaMaintenanceService
{
    public static function ensure(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;
        try {
            $db = Database::getInstance();
            self::addColumnIfMissing($db, 'cases', 'area', "VARCHAR(80) NULL AFTER assunto");
            self::addColumnIfMissing($db, 'tasks', 'hora', "TIME NULL AFTER prazo");
            self::addColumnIfMissing($db, 'tasks', 'local', "VARCHAR(255) NULL AFTER hora");
            self::addColumnIfMissing($db, 'cases', 'sistema', "VARCHAR(30) NULL AFTER tribunal");
            self::addColumnIfMissing($db, 'cases', 'fonte_importacao', "VARCHAR(50) NULL AFTER cnj_raw_data");
            self::addColumnIfMissing($db, 'cases', 'segredo_justica', "TINYINT(1) NOT NULL DEFAULT 0 AFTER fonte_importacao");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_nome', "VARCHAR(200) NULL AFTER segredo_justica");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_tipo_pessoa', "VARCHAR(20) NULL AFTER parte_contraria_nome");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_cpf_cnpj', "VARCHAR(30) NULL AFTER parte_contraria_tipo_pessoa");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_rg_ie', "VARCHAR(30) NULL AFTER parte_contraria_cpf_cnpj");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_email', "VARCHAR(190) NULL AFTER parte_contraria_rg_ie");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_telefone', "VARCHAR(30) NULL AFTER parte_contraria_email");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_endereco', "VARCHAR(255) NULL AFTER parte_contraria_telefone");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_numero', "VARCHAR(20) NULL AFTER parte_contraria_endereco");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_complemento', "VARCHAR(100) NULL AFTER parte_contraria_numero");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_bairro', "VARCHAR(100) NULL AFTER parte_contraria_complemento");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_cidade', "VARCHAR(100) NULL AFTER parte_contraria_bairro");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_estado', "CHAR(2) NULL AFTER parte_contraria_cidade");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_cep', "VARCHAR(10) NULL AFTER parte_contraria_estado");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_advogado', "VARCHAR(200) NULL AFTER parte_contraria_cep");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_advogado_oab', "VARCHAR(50) NULL AFTER parte_contraria_advogado");
            self::addColumnIfMissing($db, 'cases', 'parte_contraria_observacoes', "TEXT NULL AFTER parte_contraria_advogado_oab");
            self::addColumnIfMissing($db, 'case_movements', 'external_id', "VARCHAR(100) NULL AFTER cnj_id");
            self::addColumnIfMissing($db, 'case_movements', 'evento_numero', "VARCHAR(50) NULL AFTER external_id");
            self::addColumnIfMissing($db, 'case_movements', 'documento_url', "TEXT NULL AFTER descricao");
            self::addColumnIfMissing($db, 'case_movements', 'documento_tipo', "VARCHAR(100) NULL AFTER documento_url");
            self::addColumnIfMissing($db, 'case_movements', 'usuario_origem', "VARCHAR(150) NULL AFTER documento_tipo");
            self::addColumnIfMissing($db, 'case_movements', 'conteudo', "LONGTEXT NULL AFTER usuario_origem");
            try { $db->exec("ALTER TABLE `case_movements` MODIFY `fonte` VARCHAR(50) NOT NULL DEFAULT 'manual'"); } catch (\Throwable $e) { Logger::warning('Could not change case_movements.fonte: '.$e->getMessage()); }

            // Financeiro: vínculo entre cobrança principal e pagamentos/parcelas vinculadas
            self::addColumnIfMissing($db, 'financial_entries', 'parent_entry_id', "INT UNSIGNED NULL AFTER case_id");
            self::addColumnIfMissing($db, 'financial_entries', 'is_payment', "TINYINT(1) NOT NULL DEFAULT 0 AFTER parent_entry_id");
            try { $db->exec("CREATE INDEX idx_fin_parent ON financial_entries (parent_entry_id)"); } catch (\Throwable $e) {}
            try { $db->exec("ALTER TABLE `financial_entries` MODIFY `status` ENUM('pendente','pago','vencido','cancelado','parcial') NOT NULL DEFAULT 'pendente'"); } catch (\Throwable $e) { Logger::warning('Could not change financial_entries.status: '.$e->getMessage()); }


            $db->exec("CREATE TABLE IF NOT EXISTS timesheets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                case_id INT UNSIGNED NULL,
                client_id INT UNSIGNED NULL,
                user_id INT UNSIGNED NULL,
                data DATE NOT NULL,
                inicio TIME NULL,
                fim TIME NULL,
                minutos INT UNSIGNED NOT NULL DEFAULT 0,
                descricao TEXT NOT NULL,
                atividade VARCHAR(100) NULL,
                valor_hora DECIMAL(15,2) NULL DEFAULT 0.00,
                faturavel TINYINT(1) NOT NULL DEFAULT 1,
                faturado TINYINT(1) NOT NULL DEFAULT 0,
                financial_entry_id INT UNSIGNED NULL,
                deleted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_case_date (case_id, data),
                INDEX idx_client_date (client_id, data)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



            $db->exec("CREATE TABLE IF NOT EXISTS tribunal_connections (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                tribunal VARCHAR(30) NOT NULL DEFAULT 'tjsp',
                sistema VARCHAR(30) NOT NULL DEFAULT 'eproc',
                username VARCHAR(190) NULL,
                encrypted_password LONGTEXT NULL,
                encrypted_cookies LONGTEXT NULL,
                oab_number VARCHAR(30) NULL,
                oab_state VARCHAR(2) NULL,
                has_password TINYINT(1) NOT NULL DEFAULT 0,
                has_cookies TINYINT(1) NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL DEFAULT 'pendente',
                last_test_at DATETIME NULL,
                last_sync_at DATETIME NULL,
                last_error TEXT NULL,
                expires_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_user_tribunal_sistema (user_id, tribunal, sistema),
                INDEX idx_user_status (user_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $db->exec("CREATE TABLE IF NOT EXISTS publications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                case_id INT UNSIGNED NULL,
                numero_cnj VARCHAR(50) NULL,
                tribunal VARCHAR(20) NULL,
                diario VARCHAR(120) NULL,
                data_publicacao DATE NULL,
                titulo VARCHAR(255) NULL,
                texto LONGTEXT NOT NULL,
                fonte VARCHAR(50) NOT NULL DEFAULT 'manual',
                hash VARCHAR(64) NULL,
                lida TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_hash (hash),
                INDEX idx_numero (numero_cnj),
                INDEX idx_data (data_publicacao)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");


            // V30 - módulos operacionais completos
            $db->exec("CREATE TABLE IF NOT EXISTS case_contacts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                case_id INT UNSIGNED NOT NULL,
                nome VARCHAR(200) NOT NULL,
                tipo VARCHAR(60) NULL,
                telefone VARCHAR(40) NULL,
                email VARCHAR(190) NULL,
                documento VARCHAR(60) NULL,
                endereco TEXT NULL,
                observacoes TEXT NULL,
                deleted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_case (case_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS case_witnesses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                case_id INT UNSIGNED NOT NULL,
                nome VARCHAR(200) NOT NULL,
                telefone VARCHAR(40) NULL,
                email VARCHAR(190) NULL,
                documento VARCHAR(60) NULL,
                endereco TEXT NULL,
                resumo_depoimento TEXT NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'a_arrolar',
                deleted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_case (case_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS legal_templates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(200) NOT NULL,
                area VARCHAR(80) NULL,
                tipo VARCHAR(80) NULL,
                conteudo LONGTEXT NOT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT UNSIGNED NULL,
                deleted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_area_tipo (area, tipo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS client_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                client_id INT UNSIGNED NOT NULL,
                case_id INT UNSIGNED NULL,
                sender_type VARCHAR(20) NOT NULL DEFAULT 'office',
                sender_user_id INT UNSIGNED NULL,
                assunto VARCHAR(200) NULL,
                mensagem TEXT NOT NULL,
                lida TINYINT(1) NOT NULL DEFAULT 0,
                deleted_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_client_case (client_id, case_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS audit_trail (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                module VARCHAR(80) NOT NULL,
                entity_type VARCHAR(80) NULL,
                entity_id INT UNSIGNED NULL,
                action VARCHAR(80) NOT NULL,
                description TEXT NULL,
                ip VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_module_date (module, created_at),
                INDEX idx_entity (entity_type, entity_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $db->exec("CREATE TABLE IF NOT EXISTS backups (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                path VARCHAR(255) NOT NULL,
                size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Permissões adicionais
            try { $db->exec("INSERT IGNORE INTO permissions (name,label,module) VALUES
                ('backup.access','Acessar backup','backup'),
                ('templates.manage','Gerenciar modelos','templates'),
                ('diagnostics.access','Acessar diagnóstico','diagnostics'),
                ('portal.messages','Mensagens do portal','portal'),
                ('audit.view','Visualizar auditoria','audit')"); } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            Logger::error('Schema maintenance failed: '.$e->getMessage());
        }
    }

    private static function addColumnIfMissing($db, string $table, string $column, string $definition): void
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }
}
