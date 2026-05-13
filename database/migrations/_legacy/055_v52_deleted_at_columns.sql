-- v55: compatibilidade das tabelas v52 com Core\Model::findAll()
-- Execute apenas comandos de colunas que ainda não existem.

ALTER TABLE checklist_templates ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE client_requests ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE task_comments ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE task_checklist_items ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE procedural_deadline_rules ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE hearing_preparations ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE document_categories ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE module_permissions ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE schema_migrations_log ADD COLUMN deleted_at DATETIME NULL;
ALTER TABLE error_logs ADD COLUMN deleted_at DATETIME NULL;
