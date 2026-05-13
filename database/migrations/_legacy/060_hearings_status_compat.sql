-- v60: compatibilidade audiências/dashboard
ALTER TABLE case_hearings ADD COLUMN status VARCHAR(50) DEFAULT 'agendada';
UPDATE case_hearings SET status = 'agendada' WHERE status IS NULL OR status = '';
