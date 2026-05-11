<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Relatório de Tarefas</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/reports">Relatórios</a></li>
            <li class="breadcrumb-item active">Tarefas</li>
        </ol></nav>
    </div>
    <a href="/reports/export/excel?type=tasks" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/reports/tasks" class="row g-2 align-items-end">
            <div class="col-6 col-md-3"><label class="form-label small mb-1">Data Início</label>
                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3"><label class="form-label small mb-1">Data Fim</label>
                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3"><label class="form-label small mb-1">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos</option>
                    <?php foreach (['pendente' => 'Pendente', 'em_andamento' => 'Em Andamento', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada'] as $v => $l): ?>
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

<!-- Summary by status -->
<?php if (!empty($byStatus)): ?>
<div class="row g-3 mb-3">
    <?php
    $statusColors = ['pendente' => 'warning', 'em_andamento' => 'primary', 'concluida' => 'success', 'cancelada' => 'secondary'];
    $statusLabels = ['pendente' => 'Pendentes', 'em_andamento' => 'Em Andamento', 'concluida' => 'Concluídas', 'cancelada' => 'Canceladas'];
    foreach ($byStatus as $st => $cnt):
    ?>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3 border-<?= $statusColors[$st] ?? 'secondary' ?>">
            <div class="fw-bold fs-4 text-<?= $statusColors[$st] ?? 'secondary' ?>"><?= $cnt ?></div>
            <div class="small text-muted"><?= $statusLabels[$st] ?? ucfirst($st) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header small text-muted"><?= count($tasks) ?> tarefa(s)</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Tarefa</th>
                    <th>Responsável</th>
                    <th>Processo</th>
                    <th>Cliente</th>
                    <th>Prazo</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma tarefa no período.</td></tr>
                <?php else: ?>
                <?php foreach ($tasks as $t): ?>
                <?php $isOverdue = !empty($t['prazo']) && DateHelper::isOverdue($t['prazo']) && $t['status'] !== 'concluida'; ?>
                <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                    <td class="small fw-semibold"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($t['responsavel_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($t['numero_cnj'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($t['client_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small <?= $isOverdue ? 'text-danger fw-bold' : '' ?>"><?= !empty($t['prazo']) ? DateHelper::formatBr($t['prazo']) : '—' ?></td>
                    <td><?= FormatHelper::priorityBadge($t['prioridade']) ?></td>
                    <td><?= FormatHelper::statusBadge($t['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
