-- v61: compatibilidade audiências/dashboard - client_id
ALTER TABLE case_hearings ADD COLUMN client_id INT NULL;

UPDATE case_hearings h
LEFT JOIN case_clients cc ON cc.case_id = h.case_id
SET h.client_id = cc.client_id
WHERE h.client_id IS NULL AND cc.client_id IS NOT NULL;
