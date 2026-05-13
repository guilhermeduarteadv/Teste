-- v59: compatibilidade prazos/dashboard
ALTER TABLE case_deadlines ADD COLUMN tipo VARCHAR(100) NULL;
ALTER TABLE case_deadlines ADD COLUMN prazo DATE NULL;
UPDATE case_deadlines SET prazo = data_final WHERE prazo IS NULL AND data_final IS NOT NULL;
UPDATE case_deadlines SET data_final = prazo WHERE data_final IS NULL AND prazo IS NOT NULL;
UPDATE case_deadlines SET tipo = title WHERE tipo IS NULL AND title IS NOT NULL;
