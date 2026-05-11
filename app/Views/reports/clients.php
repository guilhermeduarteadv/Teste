<?php use App\Helpers\FormatHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Relatório de Clientes</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/reports">Relatórios</a></li>
            <li class="breadcrumb-item active">Clientes</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <a href="/reports/export/pdf?type=clients" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i>PDF</a>
        <a href="/reports/export/excel?type=clients" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i>Excel</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4"><?= count($clients) ?></div>
            <div class="small text-muted">Total Clientes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4"><?= array_sum(array_column($clients, 'total_processos')) ?></div>
            <div class="small text-muted">Processos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4 text-success"><?= FormatHelper::money(array_sum(array_column($clients, 'total_pago'))) ?></div>
            <div class="small text-muted">Total Pago</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4 text-warning"><?= FormatHelper::money(array_sum(array_column($clients, 'total_pendente'))) ?></div>
            <div class="small text-muted">Total Pendente</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>CPF/CNPJ</th>
                    <th>Processos</th>
                    <th>Total Pago</th>
                    <th>Total Pendente</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cliente cadastrado.</td></tr>
                <?php else: ?>
                <?php foreach ($clients as $cl): ?>
                <tr>
                    <td><a href="/clients/<?= $cl['id'] ?>" class="text-decoration-none small fw-semibold"><?= htmlspecialchars($cl['name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                    <td><span class="badge bg-<?= $cl['tipo_pessoa'] === 'fisica' ? 'info' : 'secondary' ?> bg-opacity-75"><?= $cl['tipo_pessoa'] === 'fisica' ? 'PF' : 'PJ' ?></span></td>
                    <td class="small"><?= htmlspecialchars($cl['cpf'] ?: $cl['cnpj'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-center"><span class="badge bg-primary"><?= $cl['total_processos'] ?></span></td>
                    <td class="small text-success"><?= FormatHelper::money((float)($cl['total_pago'] ?? 0)) ?></td>
                    <td class="small text-warning"><?= FormatHelper::money((float)($cl['total_pendente'] ?? 0)) ?></td>
                    <td><?= FormatHelper::statusBadge($cl['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
