-- =============================================================================
-- JurisControl — Schema Consolidado v37 (instalação limpa)
-- Substitui: install/schema.sql (v1) + database/migrations/001-065
-- Charset: utf8mb4 / Engine: InnoDB
-- Compatível com PHP 7.3.10 + MySQL 5.7+ / MariaDB 10.3+
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

/*M!999999\- enable the sandbox mode */ 
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `administrative_procedures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `natureza` varchar(50) DEFAULT 'administracao_publica',
  `titulo` varchar(255) NOT NULL,
  `numero_processo` varchar(100) DEFAULT NULL,
  `prefeitura` varchar(255) DEFAULT NULL,
  `secretaria` varchar(255) DEFAULT NULL,
  `setor` varchar(255) DEFAULT NULL,
  `tipo_procedimento` varchar(150) DEFAULT NULL,
  `assunto` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ativo',
  `data_protocolo` date DEFAULT NULL,
  `prazo_resposta` date DEFAULT NULL,
  `valor_estimado` decimal(15,2) DEFAULT NULL,
  `portal_url` varchar(500) DEFAULT NULL,
  `portal_login` varchar(255) DEFAULT NULL,
  `portal_password_encrypted` text DEFAULT NULL,
  `cartorio_nome` varchar(255) DEFAULT NULL,
  `cartorio_cnpj` varchar(30) DEFAULT NULL,
  `cartorio_oficial` varchar(255) DEFAULT NULL,
  `cartorio_livro` varchar(100) DEFAULT NULL,
  `cartorio_folha` varchar(100) DEFAULT NULL,
  `cartorio_matricula` varchar(100) DEFAULT NULL,
  `cartorio_ato` varchar(150) DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_client` (`client_id`),
  KEY `idx_admin_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(100) DEFAULT NULL,
  `endpoint` varchar(500) DEFAULT NULL,
  `method` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `request_data` longtext DEFAULT NULL,
  `response_body` longtext DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip` varchar(100) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) DEFAULT NULL,
  `path` varchar(500) DEFAULT NULL,
  `size_bytes` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `tipo` varchar(80) DEFAULT 'autor',
  `participacao` varchar(80) DEFAULT 'autor',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_case_client` (`case_id`,`client_id`),
  KEY `idx_case_clients_case` (`case_id`),
  KEY `idx_case_clients_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `nome` varchar(255) NOT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` varchar(500) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_deadlines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `prazo_dias` int(11) DEFAULT NULL,
  `prazo` date DEFAULT NULL,
  `data_final` date DEFAULT NULL,
  `data_final_calculada` date DEFAULT NULL,
  `confirmado` tinyint(1) DEFAULT 0,
  `confirmado_por` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `prioridade` varchar(50) DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `visivel_cliente` tinyint(1) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dead_case` (`case_id`),
  KEY `idx_dead_data` (`data_final`),
  KEY `idx_dead_prazo` (`prazo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_hearings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `local` varchar(255) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `modalidade` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'agendado',
  `observacoes` text DEFAULT NULL,
  `visivel_cliente` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hearing_case` (`case_id`),
  KEY `idx_hearing_client` (`client_id`),
  KEY `idx_hearing_data` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `data_movimento` datetime DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `conteudo` text DEFAULT NULL,
  `fonte` varchar(100) DEFAULT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `cnj_id` varchar(255) DEFAULT NULL,
  `evento_numero` varchar(100) DEFAULT NULL,
  `documento_tipo` varchar(150) DEFAULT NULL,
  `documento_url` varchar(500) DEFAULT NULL,
  `usuario_origem` varchar(255) DEFAULT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `visivel_cliente` tinyint(1) DEFAULT 0,
  `importante` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mov_case` (`case_id`),
  KEY `idx_mov_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `client_description` text DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `progress_percent` int(11) DEFAULT 0,
  `is_important` tinyint(1) DEFAULT 0,
  `visible_client` tinyint(1) DEFAULT 1,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timeline_case` (`case_id`),
  KEY `idx_timeline_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_witnesses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `endereco` varchar(500) DEFAULT NULL,
  `resumo_depoimento` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ativa',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_cnj` varchar(50) DEFAULT NULL,
  `tribunal` varchar(50) DEFAULT NULL,
  `sistema` varchar(80) DEFAULT NULL,
  `comarca` varchar(255) DEFAULT NULL,
  `vara` varchar(255) DEFAULT NULL,
  `classe` varchar(255) DEFAULT NULL,
  `classe_processual` varchar(255) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `assunto` varchar(255) DEFAULT NULL,
  `fase_processual` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ativo',
  `risco_processual` varchar(50) DEFAULT NULL,
  `probabilidade_exito` varchar(50) DEFAULT NULL,
  `valor_causa` decimal(15,2) DEFAULT NULL,
  `data_distribuicao` date DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `cnj_raw_data` longtext DEFAULT NULL,
  `fonte_importacao` varchar(100) DEFAULT NULL,
  `segredo_justica` tinyint(1) DEFAULT 0,
  `last_sync_at` datetime DEFAULT NULL,
  `last_movement_hash` varchar(100) DEFAULT NULL,
  `parte_contraria_nome` varchar(255) DEFAULT NULL,
  `parte_contraria_tipo_pessoa` varchar(50) DEFAULT NULL,
  `parte_contraria_cpf_cnpj` varchar(30) DEFAULT NULL,
  `parte_contraria_rg_ie` varchar(50) DEFAULT NULL,
  `parte_contraria_telefone` varchar(50) DEFAULT NULL,
  `parte_contraria_email` varchar(255) DEFAULT NULL,
  `parte_contraria_endereco` varchar(255) DEFAULT NULL,
  `parte_contraria_numero` varchar(50) DEFAULT NULL,
  `parte_contraria_cep` varchar(20) DEFAULT NULL,
  `parte_contraria_complemento` varchar(255) DEFAULT NULL,
  `parte_contraria_bairro` varchar(255) DEFAULT NULL,
  `parte_contraria_cidade` varchar(255) DEFAULT NULL,
  `parte_contraria_estado` varchar(2) DEFAULT NULL,
  `parte_contraria_advogado` varchar(255) DEFAULT NULL,
  `parte_contraria_oab` varchar(50) DEFAULT NULL,
  `parte_contraria_advogado_oab` varchar(50) DEFAULT NULL,
  `parte_contraria_observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cases_numero` (`numero_cnj`),
  KEY `idx_cases_status` (`status`),
  KEY `idx_cases_area` (`area`),
  KEY `idx_cases_resp` (`responsavel_id`),
  KEY `idx_cases_data_distribuicao` (`data_distribuicao`),
  KEY `idx_cases_comarca` (`comarca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `request_type` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `due_date` date DEFAULT NULL,
  `visible_client` tinyint(1) DEFAULT 1,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `tipo_pessoa` varchar(30) DEFAULT NULL,
  `cpf_cnpj` varchar(30) DEFAULT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `cnpj` varchar(25) DEFAULT NULL,
  `rg_ie` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(50) DEFAULT NULL,
  `cep` varchar(20) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `cidade` varchar(255) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_clients_name` (`name`),
  KEY `idx_clients_doc` (`cpf_cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `path` varchar(500) DEFAULT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `tamanho_bytes` int(11) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `entity_type` varchar(80) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_by_client_id` int(11) DEFAULT NULL,
  `origem_upload` varchar(50) DEFAULT NULL,
  `visivel_cliente` tinyint(1) DEFAULT 0,
  `confirmado_por` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `imported` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doc_entity` (`entity_type`,`entity_id`),
  KEY `idx_doc_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `enabled_tribunals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `name` varchar(120) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` varchar(30) DEFAULT 'error',
  `message` text NOT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `route` varchar(500) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `context` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `administrative_procedure_id` int(11) DEFAULT NULL,
  `consultancy_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `parent_entry_id` int(11) DEFAULT NULL,
  `is_payment` tinyint(1) DEFAULT 0,
  `tipo` varchar(50) DEFAULT 'receita',
  `tipo_operacao` varchar(50) DEFAULT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_bruto` decimal(15,2) DEFAULT NULL,
  `honorario_percent` decimal(5,2) DEFAULT NULL,
  `honorario_valor` decimal(15,2) DEFAULT NULL,
  `valor_liquido` decimal(15,2) DEFAULT NULL,
  `vencimento` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `forma_pagamento` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `comprovante_path` varchar(500) DEFAULT NULL,
  `visivel_cliente` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fin_client` (`client_id`),
  KEY `idx_fin_case` (`case_id`),
  KEY `idx_fin_status` (`status`),
  KEY `idx_fin_parent` (`parent_entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `install_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `installed` tinyint(1) DEFAULT 1,
  `admin_email` varchar(255) DEFAULT NULL,
  `installed_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_consultancies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `tipo_consultoria` varchar(150) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'em_andamento',
  `data_inicio` date DEFAULT NULL,
  `data_conclusao` date DEFAULT NULL,
  `valor_estimado` decimal(15,2) DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `conteudo` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payable_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `payment_method` varchar(100) DEFAULT NULL,
  `receipt_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) DEFAULT NULL,
  `numero_cnj` varchar(50) DEFAULT NULL,
  `tribunal` varchar(50) DEFAULT NULL,
  `diario` varchar(150) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `texto` longtext DEFAULT NULL,
  `data_publicacao` date DEFAULT NULL,
  `fonte` varchar(150) DEFAULT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pub_case` (`case_id`),
  KEY `idx_pub_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(150) NOT NULL,
  `valor` longtext DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'string',
  `descricao` text DEFAULT NULL,
  `grupo` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `tipo` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pendente',
  `priority` varchar(50) DEFAULT 'media',
  `prioridade` varchar(50) DEFAULT 'media',
  `due_date` date DEFAULT NULL,
  `prazo` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `local` varchar(255) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `responsible_id` int(11) DEFAULT NULL,
  `responsavel_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tasks_case` (`case_id`),
  KEY `idx_tasks_client` (`client_id`),
  KEY `idx_tasks_prazo` (`prazo`),
  KEY `idx_tasks_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `timesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `inicio` time DEFAULT NULL,
  `fim` time DEFAULT NULL,
  `minutos` int(11) DEFAULT NULL,
  `atividade` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `faturavel` tinyint(1) DEFAULT 1,
  `valor_hora` decimal(15,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tribunal_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `tribunal` varchar(50) DEFAULT NULL,
  `sistema` varchar(100) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `encrypted_password` text DEFAULT NULL,
  `encrypted_cookies` longtext DEFAULT NULL,
  `has_password` tinyint(1) DEFAULT 0,
  `has_cookies` tinyint(1) DEFAULT 0,
  `oab_number` varchar(50) DEFAULT NULL,
  `oab_state` varchar(2) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'ativo',
  `last_test_at` datetime DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trib_user` (`user_id`),
  KEY `idx_trib_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `granted` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_permission` (`user_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `role_id` int(11) DEFAULT NULL,
  `cargo` varchar(150) DEFAULT NULL,
  `oab_number` varchar(30) DEFAULT NULL,
  `oab_state` varchar(2) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `blocked_until` datetime DEFAULT NULL,
  `last_login_ip` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `last_sync_at` datetime DEFAULT NULL,
  `last_test_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

SET FOREIGN_KEY_CHECKS = 1;

-- Marca instalação consolidada
CREATE TABLE IF NOT EXISTS `schema_version` (
  `version` VARCHAR(20) NOT NULL PRIMARY KEY,
  `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `schema_version` (`version`) VALUES ('v37-consolidated');

-- System Check module (2.1)
CREATE TABLE IF NOT EXISTS `system_check_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(20) DEFAULT 'ok',
  `started_at` datetime NOT NULL,
  `finished_at` datetime NOT NULL,
  `total_errors` int(11) DEFAULT 0,
  `total_warnings` int(11) DEFAULT 0,
  `result_json` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `system_check_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `run_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `expected` varchar(255) DEFAULT NULL,
  `actual` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ok',
  `repair_action` varchar(500) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sci_run` (`run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notifications module (2.15)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `priority` varchar(20) DEFAULT 'normal',
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dashboard strategic tables (2.8)
CREATE TABLE IF NOT EXISTS `dashboard_widgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `widget_key` varchar(100) NOT NULL,
  `position` int(11) DEFAULT 0,
  `visible` tinyint(1) DEFAULT 1,
  `config_json` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_widget_user` (`user_id`,`widget_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `dashboard_snapshots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `snapshot_date` date NOT NULL,
  `data_json` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_snap_date` (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schema_version` (`version`) VALUES ('v38-system-check-notifications');
INSERT IGNORE INTO `schema_version` (`version`) VALUES ('v38-dashboard-widgets');
