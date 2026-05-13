-- Migration 071: Phase 3+5 modules
-- Módulos: Provas (2.11), Estratégia Processual (2.12), Prazos com Cálculo (2.5), Jurisprudência (2.18)
-- Criado em: 2026-05-13

-- 2.11 Módulo de Provas
CREATE TABLE IF NOT EXISTS `case_evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `document_id` int(11) DEFAULT NULL,
  `evidence_type` varchar(50) DEFAULT 'documento',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `probative_strength` varchar(20) DEFAULT 'media',
  `legal_note` varchar(500) DEFAULT NULL,
  `visible_client` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_evidence_case` (`case_id`),
  KEY `idx_evidence_type` (`evidence_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.12 Estratégia Processual
CREATE TABLE IF NOT EXISTS `process_strategy_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `tese_principal` text DEFAULT NULL,
  `tese_subsidiaria` text DEFAULT NULL,
  `riscos` text DEFAULT NULL,
  `provas_favoraveis` text DEFAULT NULL,
  `provas_desfavoraveis` text DEFAULT NULL,
  `proximos_passos` text DEFAULT NULL,
  `valor_provavel` decimal(15,2) DEFAULT NULL,
  `chance_acordo` tinyint(3) DEFAULT NULL,
  `valor_minimo_acordo` decimal(15,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `internal_only` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_strategy_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.5 Feriados
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(200) NOT NULL,
  `scope` varchar(20) DEFAULT 'nacional',
  `state` char(2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_holiday_date` (`date`),
  KEY `idx_holiday_scope` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.5 Suspensões de Prazo
CREATE TABLE IF NOT EXISTS `deadline_suspensions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `scope` varchar(20) DEFAULT 'nacional',
  `state` char(2) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_suspension_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.5 Novas colunas em case_deadlines (SchemaGuard pattern — só adicionadas se ausentes)
-- ALTER TABLE `case_deadlines` ADD COLUMN IF NOT EXISTS NÃO é usado.
-- A adição é gerenciada pelo SchemaGuardService::ensureDeadlinePhase3Columns()

-- 2.18 Jurisprudência Interna
CREATE TABLE IF NOT EXISTS `jurisprudence_library` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `court` varchar(100) DEFAULT NULL,
  `area` varchar(80) DEFAULT NULL,
  `theme` varchar(150) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `ementa` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `decision_date` date DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `used_in_case_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_juris_area` (`area`),
  KEY `idx_juris_court` (`court`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2.18 Banco de Teses
CREATE TABLE IF NOT EXISTS `legal_theses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `area` varchar(80) DEFAULT NULL,
  `thesis_text` text DEFAULT NULL,
  `legal_basis` varchar(500) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_thesis_area` (`area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schema_version` (`version`) VALUES ('v39-evidence-strategy-deadlines-knowledge');
