<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Estatísticas Dinâmicas do Escritório</h4>
        <p class="text-muted small mb-0">Escolha a base, métrica, agrupamento, período e ordenação.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="/stats" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Base de dados</label>
                <select name="dataset" class="form-select">
                    <?php foreach([
                        'financial'=>'Financeiro pago',
                        'financial_open'=>'Financeiro em aberto',
                        'clients'=>'Clientes',
                        'cases'=>'Judicial',
                        'administrative'=>'Administrativo/Prefeitura',
                        'consultancies'=>'Consultorias',
                        'portfolio'=>'Judicial x Extrajudicial'
                    ] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($filters['dataset'] ?? '')===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Métrica</label>
                <select name="metric" class="form-select">
                    <option value="sum" <?= ($filters['metric'] ?? '')==='sum'?'selected':'' ?>>Faturamento / soma</option>
                    <option value="count" <?= ($filters['metric'] ?? '')==='count'?'selected':'' ?>>Quantidade</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Agrupar por</label>
                <select name="group_by" class="form-select">
                    <?php foreach([
                        'client'=>'Cliente',
                        'area'=>'Área',
                        'origin'=>'Origem',
                        'status'=>'Status',
                        'month'=>'Mês',
                        'year'=>'Ano',
                        'day'=>'Dia',
                        'comarca'=>'Comarca',
                        'tribunal'=>'Tribunal',
                        'fase'=>'Fase',
                        'prefeitura'=>'Prefeitura',
                        'payment_method'=>'Forma de pagamento'
                    ] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($filters['group_by'] ?? '')===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Ordenar por</label>
                <select name="order_by" class="form-select">
                    <option value="total" <?= ($filters['order_by'] ?? '')==='total'?'selected':'' ?>>Valor/quantidade</option>
                    <option value="label" <?= ($filters['order_by'] ?? '')==='label'?'selected':'' ?>>Nome/período</option>
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label">Ordem</label>
                <select name="direction" class="form-select">
                    <option value="desc" <?= ($filters['direction'] ?? '')==='desc'?'selected':'' ?>>Desc</option>
                    <option value="asc" <?= ($filters['direction'] ?? '')==='asc'?'selected':'' ?>>Asc</option>
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label">De</label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>

            <div class="col-md-1">
                <label class="form-label">Até</label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>

            <div class="col-12 text-end">
                <button class="btn btn-primary"><i class="fas fa-chart-bar me-1"></i>Gerar estatística</button>
            </div>
        </form>
    </div>
</div>

<?php
$rows = $dynamicRows ?? [];
$max = 1;
foreach ($rows as $r) {
    if (isset($r['total']) && is_numeric($r['total'])) {
        $max = max($max, (float)$r['total']);
    }
}
$isMoney = in_array(($filters['dataset'] ?? ''), ['financial','financial_open'], true) && (($filters['metric'] ?? '') !== 'count');
?>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong>Resultado</strong>
        <span class="text-muted small"><?= count($rows) ?> registros</span>
    </div>
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <p class="text-muted mb-0">Sem dados para os filtros selecionados.</p>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <?php
                    $label = $row['label'] ?? '-';
                    $total = (float)($row['total'] ?? 0);
                    $pct = $max > 0 ? min(100, round(($total / $max) * 100)) : 0;
                    $display = $isMoney ? 'R$ ' . number_format($total, 2, ',', '.') : number_format($total, 0, ',', '.');
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <strong><?= htmlspecialchars((string)$label) ?></strong>
                        <span><?= $display ?></span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar" style="width: <?= $pct ?>%;"></div>
                    </div>
                    <?php if (!empty($row['erro'])): ?>
                        <div class="text-danger small mt-1"><?= htmlspecialchars($row['erro']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><strong>Sugestões de análise</strong></div>
    <div class="card-body small text-muted">
        <p class="mb-1">• Faturamento pago agrupado por cliente.</p>
        <p class="mb-1">• Faturamento pago agrupado por área.</p>
        <p class="mb-1">• Quantidade de clientes por mês ou ano.</p>
        <p class="mb-1">• Judicial x Extrajudicial por origem.</p>
        <p class="mb-0">• Financeiro em aberto agrupado por cliente.</p>
    </div>
</div>
