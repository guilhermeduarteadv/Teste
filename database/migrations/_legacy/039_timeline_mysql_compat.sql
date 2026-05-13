-- v39: compatibilidade Timeline x MySQL/AppServ
-- Este patch corrige o código para NÃO depender de deleted_at em case_timeline.
-- Opcionalmente, se desejar soft delete na timeline, rode:
-- ALTER TABLE case_timeline ADD COLUMN deleted_at DATETIME NULL;

-- Nenhum comando obrigatório nesta migração.
