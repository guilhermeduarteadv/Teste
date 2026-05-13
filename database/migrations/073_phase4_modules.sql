-- Migration 073: Phase 4 modules
-- 2.7 DataJud parte contrária, 2.9 Financeiro avançado, 2.17 Checklists items,
-- 2.19 Produtividade, 2.13 Relatórios, 2.16 Auditoria, 2.10 Portal mensagens

CREATE TABLE IF NOT EXISTS `case_parties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT 'reu',
  `polo` varchar(20) DEFAULT 'passivo',
  `nome` varchar(255) NOT NULL,
  `cpf_cnpj` varchar(30) DEFAULT NULL,
  `advogado` varchar(255) DEFAULT NULL,
  `advogado_oab` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `source` varchar(30) DEFAULT 'manual',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parties_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `client_repasses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `valor_recebido` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_repassado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `percentual_honorarios` decimal(5,2) NOT NULL DEFAULT 0.00,
  `data_recebimento` date DEFAULT NULL,
  `data_repasse` date DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pendente',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_repasses_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `financial_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `financial_entry_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_receipts_entry` (`financial_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `case_checklist_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `item_text` varchar(500) NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checklist_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `productivity_snapshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `snapshot_date` date NOT NULL,
  `tasks_completed` int(11) DEFAULT 0,
  `tasks_overdue` int(11) DEFAULT 0,
  `hours_logged` decimal(6,2) DEFAULT 0.00,
  `cases_active` int(11) DEFAULT 0,
  `deadlines_met` int(11) DEFAULT 0,
  `deadlines_missed` int(11) DEFAULT 0,
  `data_json` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prod_snap_date` (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `generated_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `output_format` varchar(20) DEFAULT 'html',
  `params_json` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `document_access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT 'view',
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `accessed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_access_date` (`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `data_exports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `portal_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `sender_type` varchar(20) DEFAULT 'client',
  `sender_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_portal_msg_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `schema_version` (`version`) VALUES ('v41-phase4-parties-repasses-checklists');
