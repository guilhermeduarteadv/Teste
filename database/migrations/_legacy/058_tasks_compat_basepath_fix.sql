-- v58: compatibilidade instalação limpa
-- Execute apenas se necessário.

ALTER TABLE tasks ADD COLUMN prazo DATE NULL;
ALTER TABLE tasks ADD COLUMN hora TIME NULL;
ALTER TABLE tasks ADD COLUMN prioridade VARCHAR(50) DEFAULT 'media';
ALTER TABLE tasks ADD COLUMN responsavel_id INT NULL;

UPDATE tasks SET prazo = due_date WHERE prazo IS NULL AND due_date IS NOT NULL;
UPDATE tasks SET hora = due_time WHERE hora IS NULL AND due_time IS NOT NULL;
UPDATE tasks SET prioridade = priority WHERE prioridade IS NULL AND priority IS NOT NULL;
UPDATE tasks SET responsavel_id = responsible_id WHERE responsavel_id IS NULL AND responsible_id IS NOT NULL;
