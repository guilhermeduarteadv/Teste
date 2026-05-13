-- v64: compatibilidade tasks legado/novo
-- Em MySQL antigo, rode apenas os ALTERs das colunas que não existirem.

ALTER TABLE tasks ADD COLUMN due_date DATE NULL;
ALTER TABLE tasks ADD COLUMN due_time TIME NULL;
ALTER TABLE tasks ADD COLUMN priority VARCHAR(50) DEFAULT 'media';
ALTER TABLE tasks ADD COLUMN responsible_id INT NULL;
ALTER TABLE tasks ADD COLUMN prazo DATE NULL;
ALTER TABLE tasks ADD COLUMN hora TIME NULL;
ALTER TABLE tasks ADD COLUMN prioridade VARCHAR(50) DEFAULT 'media';
ALTER TABLE tasks ADD COLUMN responsavel_id INT NULL;

UPDATE tasks SET due_date = prazo WHERE due_date IS NULL AND prazo IS NOT NULL;
UPDATE tasks SET prazo = due_date WHERE prazo IS NULL AND due_date IS NOT NULL;
UPDATE tasks SET due_time = hora WHERE due_time IS NULL AND hora IS NOT NULL;
UPDATE tasks SET hora = due_time WHERE hora IS NULL AND due_time IS NOT NULL;
UPDATE tasks SET priority = prioridade WHERE priority IS NULL AND prioridade IS NOT NULL;
UPDATE tasks SET prioridade = priority WHERE prioridade IS NULL AND priority IS NOT NULL;
UPDATE tasks SET responsible_id = responsavel_id WHERE responsible_id IS NULL AND responsavel_id IS NOT NULL;
UPDATE tasks SET responsavel_id = responsible_id WHERE responsavel_id IS NULL AND responsible_id IS NOT NULL;
