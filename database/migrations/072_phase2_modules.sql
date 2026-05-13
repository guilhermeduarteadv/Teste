-- =============================================================================
-- Migration 072: Phase 2 Modules
-- Busca Global (2.14), Gerador de Documentos (2.2),
-- Histórico / Timeline do Cliente (2.3), Agenda Jurídica (2.4)
-- =============================================================================

-- 2.14 Busca Global
CREATE TABLE IF NOT EXISTS `search_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `query` varchar(500) NOT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_search_logs_user` (`user_id`),
  KEY `idx_search_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.2 Gerador de Documentos: tabela de documentos gerados
CREATE TABLE IF NOT EXISTS `generated_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `output_format` varchar(20) DEFAULT 'html',
  `file_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gen_docs_template` (`template_id`),
  KEY `idx_gen_docs_client` (`client_id`),
  KEY `idx_gen_docs_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.2 Gerador: colunas adicionais no legal_templates (use SchemaGuard em produção)
-- ALTER TABLE `legal_templates` ADD COLUMN IF NOT EXISTS `variables_json` TEXT NULL;
-- ALTER TABLE `legal_templates` ADD COLUMN IF NOT EXISTS `active` TINYINT(1) DEFAULT 1;

-- 2.3 Histórico / Timeline do Cliente
CREATE TABLE IF NOT EXISTS `client_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `event_date` datetime NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `source` varchar(50) DEFAULT 'manual',
  `source_id` int(11) DEFAULT NULL,
  `visible_client` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timeline_client` (`client_id`),
  KEY `idx_timeline_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `client_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `visibility` varchar(20) DEFAULT 'internal',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notes_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.4 Agenda Juridica Unificada
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT 'outro',
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `responsible_id` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pendente',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cal_events_start` (`start_at`),
  KEY `idx_cal_events_client` (`client_id`),
  KEY `idx_cal_events_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schema_version` (`version`) VALUES ('v40-search-templates-timeline-calendar');
