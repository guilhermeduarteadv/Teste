-- v49: Tribunais selecionáveis, busca/vínculo de clientes e extrajudicial em cartórios

CREATE TABLE IF NOT EXISTS enabled_tribunals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

ALTER TABLE administrative_procedures ADD COLUMN natureza VARCHAR(50) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_nome VARCHAR(255) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_cnpj VARCHAR(30) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_oficial VARCHAR(255) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_livro VARCHAR(100) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_folha VARCHAR(100) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_matricula VARCHAR(100) NULL;
ALTER TABLE administrative_procedures ADD COLUMN cartorio_ato VARCHAR(150) NULL;
