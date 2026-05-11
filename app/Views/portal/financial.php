<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Financeiro</h4>
        <p class="text-muted small mb-0">Seus lançamentos e pagamentos</p>
    </div>
</div>

<!-- Summary -->
<?php if (!empty($summary)): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card text-center p-3">
            <div class="fw-bold text-success"><?= FormatHelper::money((float)($summary['total_pago'] ?? 0)) ?></div>
            <div class="small text-muted">Pago</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center p-3">
            <div class="fw-bold text-warning"><?= FormatHelper::money((float)($summary['total_pendente'] ?? 0)) ?></div>
            <div class="small text-muted">Pendente</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card text-center p-3">
            <div class="fw-bold text-danger"><?= FormatHelper::money((float)($summary['total_vencido'] ?? 0)) ?></div>
            <div class="small text-muted">Vencido</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($entries)): ?>
<div class="card text-center py-5">
    <div class="text-muted">
        <i class="fas fa-dollar-sign fa-3x mb-3"></i>
        <p>Nenhum lançamento disponível.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Processo</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): ?>
                <?php $isOverdue = ($e['status'] === 'pendente') && DateHelper::isOverdue($e['vencimento']); ?>
                <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                    <td class="small"><?= htmlspecialchars($e['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($e['numero_cnj'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small fw-semibold"><?= FormatHelper::money((float)$e['valor']) ?></td>
                    <td class="small <?= $isOverdue ? 'text-danger fw-bold' : '' ?>"><?= DateHelper::formatBr($e['vencimento']) ?></td>
                    <td><?= FormatHelper::statusBadge($e['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
