-- =============================================================================
-- JurisControl — Seed mínimo para instalação limpa
-- =============================================================================
SET NAMES utf8mb4;

-- Roles base
INSERT IGNORE INTO `roles` (`id`, `name`, `label`, `description`, `created_at`) VALUES
(1, 'admin',     'Administrador',  'Acesso total ao sistema', NOW()),
(2, 'advogado',  'Advogado(a)',    'Advogado com acesso aos casos atribuídos', NOW()),
(3, 'estagiario','Estagiário(a)',  'Acesso restrito de leitura/escrita', NOW()),
(4, 'cliente',   'Cliente',        'Portal do cliente', NOW());

-- Settings padrão
INSERT IGNORE INTO `settings` (`chave`, `valor`, `tipo`) VALUES
('app_name',         'JurisControl',           'string'),
('office_name',      'Escritório de Advocacia','string'),
('timezone',         'America/Sao_Paulo',      'string'),
('date_format',      'd/m/Y',                  'string'),
('items_per_page',   '20',                     'int'),
('session_lifetime', '120',                    'int'),
('app_version',      'v37',                    'string');

-- Tipos de tribunal padrão (se a tabela existir)
-- (mantida vazia por padrão; popular conforme necessidade)
