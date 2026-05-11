<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Relatório de Processos</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/reports">Relatórios</a></li>
            <li class="breadcrumb-item active">Processos</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <a href="/reports/export/pdf?type=cases&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i>PDF</a>
        <a href="/reports/export/excel?type=cases&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/reports/cases" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Data Início</label>
                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Data Fim</label>
                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos</option>
                    <?php foreach (['ativo' => 'Ativo', 'aguardando' => 'Aguardando', 'suspenso' => 'Suspenso', 'arquivado' => 'Arquivado', 'encerrado' => 'Encerrado'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filter_status ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4"><?= count($cases) ?></div>
            <div class="small text-muted">Total Processos</div>
        </div>
    </div>
    <?php foreach ($statusSummary as $st => $cnt): ?>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4"><?= $cnt ?></div>
            <div class="small text-muted"><?= ucfirst($st) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header small text-muted">
        <?= count($cases) ?> processo(s) no período de <?= DateHelper::formatBr($date_from) ?> a <?= DateHelper::formatBr($date_to) ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Número CNJ</th>
                    <th>Assunto</th>
                    <th>Tribunal</th>
                    <th>Responsável</th>
                    <th>Clientes</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Cadastrado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cases)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum processo no período selecionado.</td></tr>
                <?php else: ?>
                <?php foreach ($cases as $c): ?>
                <tr>
                    <td class="small"><a href="/cases/<?= $c['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($c['numero_cnj'] ?: '—', ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td class="small"><?= htmlspecialchars($c['assunto'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($c['tribunal'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($c['responsavel_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($c['clientes'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= FormatHelper::money((float)($c['valor_causa'] ?? 0)) ?></td>
                    <td><?= FormatHelper::statusBadge($c['status']) ?></td>
                    <td class="small"><?= DateHelper::formatBr($c['created_at'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
