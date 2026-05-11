<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Relatório Financeiro</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/reports">Relatórios</a></li>
            <li class="breadcrumb-item active">Financeiro</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <a href="/reports/export/pdf?type=financial&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i>PDF</a>
        <a href="/reports/export/excel?type=financial" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/reports/financial" class="row g-2 align-items-end">
            <div class="col-6 col-md-2"><label class="form-label small mb-1">Data Início</label>
                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2"><label class="form-label small mb-1">Data Fim</label>
                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2"><label class="form-label small mb-1">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos</option>
                    <?php foreach (['pendente' => 'Pendente', 'pago' => 'Pago', 'vencido' => 'Vencido'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filter_status ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2"><label class="form-label small mb-1">Tipo</label>
                <select class="form-select form-select-sm" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach (['honorario' => 'Honorário', 'custas' => 'Custas', 'despesa' => 'Despesa', 'reembolso' => 'Reembolso'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filter_tipo ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#059669,#047857);">
            <div class="stat-value"><?= FormatHelper::money((float)$totalRecebido) ?></div>
            <div class="stat-label">Total Recebido</div>
            <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#b45309);">
            <div class="stat-value"><?= FormatHelper::money((float)$totalPendente) ?></div>
            <div class="stat-label">Pendente</div>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
            <div class="stat-value"><?= FormatHelper::money((float)$totalVencido) ?></div>
            <div class="stat-label">Vencido</div>
            <div class="stat-icon"><i class="fas fa-exclamation"></i></div>
        </div>
    </div>
</div>

<?php if (!empty($byTipo)): ?>
<div class="card mb-3">
    <div class="card-header small"><i class="fas fa-chart-bar me-2"></i>Por Tipo</div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($byTipo as $tipo => $total): ?>
            <div class="col-6 col-md-3 text-center">
                <div class="fw-bold text-primary"><?= FormatHelper::money((float)$total) ?></div>
                <div class="small text-muted"><?= ucfirst($tipo) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header small text-muted"><?= count($entries) ?> lançamento(s)</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Processo</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum lançamento no período.</td></tr>
                <?php else: ?>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td class="small"><?= htmlspecialchars($e['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($e['tipo']) ?></span></td>
                    <td class="small"><?= htmlspecialchars($e['client_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($e['numero_cnj'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small fw-semibold"><?= FormatHelper::money((float)$e['valor']) ?></td>
                    <td class="small"><?= DateHelper::formatBr($e['vencimento']) ?></td>
                    <td class="small"><?= !empty($e['data_pagamento']) ? DateHelper::formatBr($e['data_pagamento']) : '—' ?></td>
                    <td><?= FormatHelper::statusBadge($e['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($entries)): ?>
            <tfoot class="table-light">
                <tr>
                    <td colspan="4" class="text-end small fw-bold">Total do período:</td>
                    <td class="small fw-bold"><?= FormatHelper::money($totalRecebido + $totalPendente + $totalVencido) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
