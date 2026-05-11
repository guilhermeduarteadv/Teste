<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Financeiro</h4>
        <p class="text-muted small mb-0">Gestão de receitas e despesas</p>
    </div>
    <a href="/financial/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Novo Lançamento</a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#059669,#047857);">
            <div class="stat-value"><?= FormatHelper::money((float)($summary['total_recebido'] ?? 0)) ?></div>
            <div class="stat-label">Recebido</div>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#b45309);">
            <div class="stat-value"><?= FormatHelper::money((float)($summary['total_pendente'] ?? 0)) ?></div>
            <div class="stat-label">Pendente</div>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#dc2626,#b91c1c);">
            <div class="stat-value"><?= FormatHelper::money((float)($summary['total_vencido'] ?? 0)) ?></div>
            <div class="stat-label">Vencido</div>
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
            <div class="stat-value"><?= number_format($summary['total_count'] ?? 0) ?></div>
            <div class="stat-label">Lançamentos</div>
            <div class="stat-icon"><i class="fas fa-list"></i></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/financial" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Buscar descrição..."
                       value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos Status</option>
                    <?php foreach (['pendente' => 'Pendente', 'pago' => 'Pago', 'vencido' => 'Vencido', 'cancelado' => 'Cancelado'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="tipo">
                    <option value="">Todos Tipos</option>
                    <?php foreach (['honorario' => 'Honorário', 'custas' => 'Custas', 'despesa' => 'Despesa', 'reembolso' => 'Reembolso', 'outros' => 'Outros'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['tipo'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" class="form-control form-control-sm" name="data_inicio"
                       value="<?= htmlspecialchars($filters['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" class="form-control form-control-sm" name="data_fim"
                       value="<?= htmlspecialchars($filters['data_fim'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <a href="/financial" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Processo</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Nenhum lançamento encontrado.</td></tr>
                <?php else: ?>
                <?php foreach ($entries as $e): ?>
                <?php
                $isOverdue = ($e['status'] === 'pendente') && DateHelper::isOverdue($e['vencimento']);
                ?>
                <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                    <td class="text-muted small"><?= $e['id'] ?></td>
                    <td>
                        <div class="small fw-semibold"><?= htmlspecialchars($e['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($e['parcela_numero'] && $e['parcela_total']): ?>
                        <div class="text-muted" style="font-size:0.72rem;">Parcela <?= $e['parcela_numero'] ?>/<?= $e['parcela_total'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary"><?= ucfirst($e['tipo']) ?></span></td>
                    <td class="small"><?= htmlspecialchars($e['client_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($e['numero_cnj'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small fw-semibold"><?= FormatHelper::money((float)$e['valor']) ?></td>
                    <td class="small <?= $isOverdue ? 'text-danger fw-bold' : '' ?>"><?= DateHelper::formatBr($e['vencimento']) ?></td>
                    <td><?= FormatHelper::statusBadge($e['status']) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if ($e['status'] === 'pendente'): ?>
                            <button class="btn btn-outline-success btn-pay" data-id="<?= $e['id'] ?>" title="Marcar como pago">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <a href="/financial/<?= $e['id'] ?>/edit" class="btn btn-outline-secondary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-delete-financial" data-id="<?= $e['id'] ?>" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Exibindo <?= count($entries) ?> de <?= $pagination['total'] ?> registros</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Pay Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Registrar Pagamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="payForm" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Data do Pagamento</label>
                        <input type="date" class="form-control" name="data_pagamento" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Forma de Pagamento</label>
                        <select class="form-select" name="forma_pagamento">
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência</option>
                            <option value="boleto">Boleto</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success btn-sm">Confirmar</button></div>
            </form>
        </div>
    </div>
</div>

<form id="deleteFinancialForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
</form>

<script>
document.querySelectorAll('.btn-pay').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('payForm').action = `/financial/${btn.dataset.id}/pay`;
        new bootstrap.Modal(document.getElementById('payModal')).show();
    });
});
document.querySelectorAll('.btn-delete-financial').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Excluir este lançamento?')) return;
        const form = document.getElementById('deleteFinancialForm');
        form.action = `/financial/${btn.dataset.id}/delete`;
        form.submit();
    });
});
</script>
