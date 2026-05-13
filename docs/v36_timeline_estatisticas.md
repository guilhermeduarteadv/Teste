# Sistema v36 — Próximos passos implementados

## 1. Gráfico de processos por mês
Foi criado `DashboardStatsService` e endpoint `/api/stats/dashboard`.

Dados usados:
- `cases.data_distribuicao` para novos processos por mês.
- `financial_entries.data_pagamento` ou `vencimento` para recebidos por mês.
- `cases.area` para atuação por área.
- `cases.comarca` para distribuição por comarca.

## 2. Timeline visual do processo
Nova tela:
- `/cases/{id}/timeline`

A timeline é reconstruída a partir de:
- data de distribuição;
- movimentações;
- prazos;
- audiências;
- financeiro.

## 3. Percentual automático do processo
A timeline calcula o maior percentual de andamento entre os eventos relevantes:
- distribuição 10%;
- contestação 25%;
- réplica 35%;
- audiência 45%;
- decisão/despacho 50-60%;
- sentença 70%;
- recurso 82%;
- acórdão 88%;
- cumprimento 92%;
- pagamento/repasse 96-98%;
- baixa 100%.

## 4. Portal do cliente
Foi criada view de timeline do cliente:
- `app/Views/portal/case_timeline.php`

Os eventos respeitam o campo:
- `visible_client`.

## 5. Vínculo automático movimentação → timeline
Serviço:
- `app/Services/TimelineService.php`

Ele identifica eventos importantes por palavras-chave:
- sentença;
- decisão;
- despacho;
- audiência;
- perícia;
- contestação;
- réplica;
- recurso;
- cumprimento;
- pagamento;
- baixa;
- publicação.

## 6. Migração
Rodar:
- `database/migrations/038_dashboard_timeline_visual.sql`
