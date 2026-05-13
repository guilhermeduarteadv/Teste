<?php
$minToHours = function($min) {
    $h = floor($min / 60);
    $m = $min % 60;
    return $h . 'h' . ($m > 0 ? sprintf('%02dm', $m) : '');
};
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Produtividade e Timesheet</h4>
        <p class="text-muted small mb-0">Métricas de produtividade da equipe por período</p>
    </div>
</div>

<!-- Filtro de período -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="/productivity" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Início</label>
                <input type="date" name="period_start" class="form-control form-control-sm" value="<?= htmlspecialchars($periodStart, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Fim</label>
                <input type="date" name="period_end" class="form-control form-control-sm" value="<?= htmlspecialchars($periodEnd, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
            <div class="col-md-2">
                <a href="/productivity" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo me-1"></i>Limpar</a>
            </div>
        </form>
    </div>
</div>

<!-- Cards de resumo geral -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="stat-card bg-primary">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-value"><?= $minToHours((int)($stats['summary']['total_min'] ?? 0)) ?></div>
            <div class="stat-label">Total de horas registradas</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card bg-success">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-value"><?= $minToHours((int)($stats['summary']['fat_min'] ?? 0)) ?></div>
            <div class="stat-label">Horas faturáveis</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card bg-warning text-dark">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-value">R$ <?= number_format((float)($stats['summary']['valor_fat'] ?? 0), 2, ',', '.') ?></div>
            <div class="stat-label">Valor faturável estimado</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Tarefas por usuário -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="fas fa-tasks me-2 text-primary"></i>Tarefas por Usuário</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Total</th>
                            <th>Concluídas</th>
                            <th>Taxa</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['tasks_by_user'])): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sem dados no período.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['tasks_by_user'] as $u): ?>
                        <?php
                        $total = (int)$u['total_tasks'];
                        $done  = (int)$u['done_tasks'];
                        $taxa  = $total > 0 ? round(($done / $total) * 100) : 0;
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $total ?></td>
                            <td><?= $done ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;">
                                        <div class="progress-bar bg-success" style="width:<?= $taxa ?>%"></div>
                                    </div>
                                    <span class="small"><?= $taxa ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Horas por usuário -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="fas fa-stopwatch me-2 text-success"></i>Horas por Usuário</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Total</th>
                            <th>Faturável</th>
                            <th>Valor Fat.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['hours_by_user'])): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sem dados no período.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['hours_by_user'] as $u): ?>
                        <?php if ((int)$u['total_minutos'] === 0) continue; ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $minToHours((int)$u['total_minutos']) ?></td>
                            <td><span class="badge bg-success"><?= $minToHours((int)$u['minutos_faturavel']) ?></span></td>
                            <td>R$ <?= number_format((float)$u['valor_faturavel'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Horas por cliente -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="fas fa-users me-2 text-info"></i>Horas por Cliente</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Faturável</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['hours_by_client'])): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Sem dados no período.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['hours_by_client'] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['client_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $minToHours((int)$row['total_minutos']) ?></td>
                            <td><span class="badge bg-success"><?= $minToHours((int)$row['minutos_faturavel']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Horas por processo -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="fas fa-gavel me-2 text-warning"></i>Horas por Processo</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Processo</th>
                            <th>Horas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['hours_by_case'])): ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">Sem dados no período.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stats['hours_by_case'] as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold small"><?= htmlspecialchars($row['numero_cnj'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($row['titulo'])): ?>
                                    <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($row['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= $minToHours((int)$row['total_minutos']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Prazos -->
    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fas fa-calendar-check me-2 text-danger"></i>Prazos no Período</div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-success bg-opacity-10 rounded">
                            <div class="fs-3 fw-bold text-success"><?= (int)($stats['deadlines']['cumpridos'] ?? 0) ?></div>
                            <div class="small text-muted">Cumpridos</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-danger bg-opacity-10 rounded">
                            <div class="fs-3 fw-bold text-danger"><?= (int)($stats['deadlines']['vencidos'] ?? 0) ?></div>
                            <div class="small text-muted">Vencidos</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-secondary bg-opacity-10 rounded">
                            <div class="fs-3 fw-bold text-secondary"><?= (int)($stats['deadlines']['total'] ?? 0) ?></div>
                            <div class="small text-muted">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
