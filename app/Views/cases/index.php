<?php use App\Helpers\FormatHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Processos</h4>
        <p class="text-muted small mb-0">Total: <?= number_format($pagination['total'] ?? 0) ?> processos</p>
    </div>
    <a href="/cases/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Novo Processo</a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/cases" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <input type="text" class="form-control form-control-sm" name="search"
                       placeholder="Buscar por número CNJ, assunto, comarca..."
                       value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos os status</option>
                    <?php foreach (['ativo' => 'Ativo', 'arquivado' => 'Arquivado', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado', 'aguardando' => 'Aguardando'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrar</button>
            </div>
            <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
            <div class="col-12 col-md-2">
                <a href="/cases" class="btn btn-sm btn-outline-secondary w-100">Limpar</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Número CNJ</th>
                    <th>Assunto</th>
                    <th>Clientes</th>
                    <th>Tribunal</th>
                    <th>Responsável</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cases)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-gavel fa-2x mb-2 d-block"></i>Nenhum processo encontrado.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($cases as $case): ?>
                <tr>
                    <td>
                        <a href="/cases/<?= $case['id'] ?>" class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($case['numero_cnj'] ?? 'Sem número', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php if (!empty($case['risco_processual'])): ?>
                        <br><?= FormatHelper::riskBadge($case['risco_processual']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?= htmlspecialchars(\App\Helpers\FormatHelper::truncate($case['assunto'] ?? '', 60), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($case['classe'])): ?>
                        <br><span class="text-muted"><?= htmlspecialchars($case['classe'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars(\App\Helpers\FormatHelper::truncate($case['clientes'] ?? '-', 40), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($case['tribunal'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small"><?= htmlspecialchars($case['responsavel_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= FormatHelper::statusBadge($case['status']) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="/cases/<?= $case['id'] ?>" class="btn btn-outline-primary" title="Ver"><i class="fas fa-eye"></i></a>
                            <a href="/cases/<?= $case['id'] ?>/edit" class="btn btn-outline-secondary" title="Editar"><i class="fas fa-edit"></i></a>
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
        <small class="text-muted">Exibindo <?= count($cases ?? []) ?> de <?= $pagination['total'] ?> registros</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['status']) ? '&status=' . urlencode($filters['status']) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
