# Sistema v33 — Data de Distribuição e Estatísticas

## O que esta versão adiciona

1. Campo `data_distribuicao` na tabela `cases`.
2. Campos auxiliares para estatísticas:
   - `valor_causa`
   - `comarca`
   - `vara`
3. Índices para relatórios por mês, área e comarca.
4. Base para gráficos:
   - novos processos por mês;
   - processos por área;
   - processos por comarca;
   - tempo médio de tramitação.

## SQL incluído

Arquivo:

`database/migrations/033_data_distribuicao_estatisticas.sql`

Execute essa migração no banco caso o instalador do sistema não rode automaticamente.

## Consulta para gráfico de novos processos por mês

```sql
SELECT
    DATE_FORMAT(data_distribuicao, '%Y-%m') AS mes,
    COUNT(*) AS total
FROM cases
WHERE data_distribuicao IS NOT NULL
GROUP BY DATE_FORMAT(data_distribuicao, '%Y-%m')
ORDER BY mes ASC;
```

## Consulta para processos por área

```sql
SELECT
    COALESCE(area, 'Não informada') AS area,
    COUNT(*) AS total
FROM cases
WHERE deleted_at IS NULL
GROUP BY COALESCE(area, 'Não informada')
ORDER BY total DESC;
```

## Consulta para processos por comarca

```sql
SELECT
    COALESCE(comarca, 'Não informada') AS comarca,
    COUNT(*) AS total
FROM cases
WHERE deleted_at IS NULL
GROUP BY COALESCE(comarca, 'Não informada')
ORDER BY total DESC;
```

## Observação importante

`created_at` indica quando o processo foi cadastrado no sistema.
`data_distribuicao` indica a data real em que o processo foi distribuído no tribunal.
Para estatísticas jurídicas, use sempre `data_distribuicao`.
