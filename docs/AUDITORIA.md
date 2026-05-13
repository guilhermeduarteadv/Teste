# JurisControl — Relatório de Auditoria v37 (instalação limpa)

Data: 2026-05-13
PHP alvo: **7.3.10**
MySQL/MariaDB alvo: **5.7+ / 10.3+**

## 1. Sumário executivo

| Item | Antes | Depois |
|---|---|---|
| Arquivos PHP | 159 | 158 (–1 backup) |
| Erros de sintaxe (php -l) | 0 | 0 |
| Construções incompatíveis com PHP 7.3 | 0 | 0 |
| Migrations SQL | 28 (acumuladas, com remendos) | 0 (consolidadas em 1 schema) |
| Tabelas no schema | 45 | 32 (+1 `schema_version`) |
| Tabelas mortas removidas | — | 12 |
| Arquivos backup no repo | 1 | 0 |

## 2. Compatibilidade PHP 7.3.10

Varredura completa em todos os 158 arquivos `.php`:

- ✅ Nenhum operador nullsafe `?->` (PHP 8.0+)
- ✅ Nenhum `match()` (PHP 8.0+)
- ✅ Nenhum `str_contains/starts_with/ends_with` (PHP 8.0+)
- ✅ Nenhum atributo PHP `#[...]`, `enum`, `readonly`, promotion de construtor
- ✅ Nenhuma arrow function `fn()` problemática
- ✅ `php -l` aprova 100% dos arquivos

O sistema é compatível com PHP 7.3.10 sem alterações.

> **Observação:** PHP 7.3 está EOL desde dez/2021 (sem patches de segurança).
> Se o servidor permitir, recomenda-se 7.4 LTS ou 8.1+.

## 3. Banco de dados — consolidação

As 28 migrations (`001_initial_schema.sql` … `065_settings_backup_php_compat.sql`)
foram aplicadas sequencialmente em um MySQL temporário, dump consolidado, e
arquivadas em `database/migrations/_legacy/`.

Resultado: **um único `install/schema.sql`** + `install/seed.sql` mínimo.

### Tabelas mortas removidas
Detectadas porque foram criadas pela migration `052_expansao_completa.sql` mas
nunca referenciadas pelo código PHP:

- `office_stats_cache`
- `schema_migrations_log`
- `legal_fee_contracts`
- `leads`
- `task_comments`
- `task_checklist_items`
- `process_strategy_notes`
- `procedural_deadline_rules`
- `hearing_preparations`
- `checklist_templates`
- `document_categories`
- `module_permissions`

Se algum recurso planejado dependia delas, recriar conforme necessário.

### Camadas de "compat" eliminadas
As migrations 058–065 foram remendos em runtime que adicionavam colunas
duplicadas para satisfazer queries legadas. No schema consolidado, ambos os
nomes coexistem **fisicamente** (para não quebrar código), mas estão
documentadas como duplicações a serem unificadas:

| Tabela | Duplicação | Recomendação |
|---|---|---|
| `tasks` | `prazo` + `due_date` | Unificar em `due_date` (padrão internacional) |
| `tasks` | `descricao` + `description` + `observacoes` | Unificar em `descricao` + `observacoes` |
| `case_deadlines` | `descricao` + `observacoes` | Manter ambos (campos distintos) |
| `case_hearings` | `cliente_id` + `client_id` | Unificar em `client_id` |
| `clients` | `estado` + `status` | Mantém: `estado` = UF, `status` = ativo/inativo |
| `financial_entries` | `descricao` + `observacoes` | Manter (distintos) |
| `legal_consultancies` | `descricao` + `observacoes` | Manter (distintos) |

## 4. Duplicação no código (Models paralelos)

O diretório `app/Models/` contém **pares paralelos** com escopos sobrepostos
mas conjuntos de métodos diferentes:

```
User.php           ↔ UserModel.php
Client.php         ↔ ClientModel.php
LegalCase.php      ↔ CaseModel.php
Task.php           ↔ TaskModel.php
Document.php       ↔ DocumentModel.php
FinancialEntry.php ↔ FinancialModel.php
Setting.php        ↔ SettingModel.php
SystemLog.php      ↔ SystemLogModel.php
```

Diferentes Controllers usam um ou outro. Mesclá-los **com segurança**
exige refactor manual extenso (cada Controller precisa ser revisitado para
escolher um único Model). Está fora do escopo desta auditoria automatizada.

**Plano sugerido para refactor manual** (1 dia de dev por par):

1. Para cada par, escolher o nome canônico (recomendo o sem sufixo: `User`, `Client`, etc.).
2. Mesclar todos os métodos no canônico.
3. Substituir `XModel` por `X` em todos os Controllers (`grep -rln "Models\\\\XModel" app/`).
4. Deletar o `XModel.php`.

## 5. Queries duplicadas (>=3 ocorrências)

Identificadas e listadas em `audit/AUDITORIA.md`. As mais críticas:

- `SELECT COUNT(*) FROM cases WHERE status=?` (4×)
- `UPDATE cases SET cnj_raw_data = ?, last_movement_hash = ?, ...` (4×)
- `SELECT id FROM case_movements WHERE case_id = ? AND hash = ?` (4×)
- `SHOW COLUMNS FROM users LIKE ?` (3×)
- `INSERT IGNORE INTO roles ...` (3×)

A maioria está repetida nas duplas Model/XModel — **resolvendo a duplicação
de Models, essas queries deixam de existir em duplicidade**.

## 6. Arquivos removidos

- `app/Services/CNJService - Copia.php` (cópia de backup vazada para o repo)

## 7. Instalação limpa

Validada em MariaDB 11.4. Fluxo:

1. `install/index.php` valida requisitos (PHP 7.3.10+, extensões).
2. Cria a base de dados se não existir.
3. Aplica `install/schema.sql` (1 arquivo, 32 tabelas, charset utf8mb4, InnoDB).
4. Aplica `install/seed.sql` (4 roles + 7 settings padrão).
5. Cria primeiro usuário administrador.
6. Grava `config/installed.php` e `storage/installed.lock`.

## 8. Próximos passos recomendados (não automatizados)

1. **Refactor dos Models duplicados** (alta prioridade — fonte das queries duplicadas).
2. **Unificar `tasks.prazo` ↔ `due_date`** após escolher um nome.
3. **Adicionar índices** em colunas de `WHERE`/`JOIN` que ainda não os têm
   (rodar `EXPLAIN` em queries lentas).
4. **Remover services de auto-reparo** (`SchemaGuardService`, `SchemaMaintenanceService`,
   `install/repairs/`) — só fazem sentido em sistemas sem instalador limpo.
5. **Migrar para PHP 7.4+** (versão atual está EOL há 4+ anos).
