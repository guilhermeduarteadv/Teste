<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Tarefas</h4>
        <p class="text-muted small mb-0">Gestão de tarefas e atividades</p>
    </div>
    <a href="/tasks/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nova Tarefa</a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4 text-warning"><?= $stats['pendentes'] ?? 0 ?></div>
            <div class="small text-muted">Pendentes</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4 text-primary"><?= $stats['em_andamento'] ?? 0 ?></div>
            <div class="small text-muted">Em Andamento</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4 text-danger"><?= $stats['atrasadas'] ?? 0 ?></div>
            <div class="small text-muted">Atrasadas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fw-bold fs-4 text-success"><?= $stats['concluidas'] ?? 0 ?></div>
            <div class="small text-muted">Concluídas</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/tasks" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Buscar tarefa..."
                       value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos Status</option>
                    <?php foreach (['pendente' => 'Pendente', 'em_andamento' => 'Em Andamento', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" name="prioridade">
                    <option value="">Todas Prioridades</option>
                    <?php foreach (['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['prioridade'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" name="responsavel_id">
                    <option value="">Todos Responsáveis</option>
                    <?php foreach ($users ?? [] as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (int)($filters['responsavel_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <a href="/tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Tarefa</th>
                    <th>Tipo</th>
                    <th>Responsável</th>
                    <th>Prazo</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tasks)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-tasks fa-2x mb-2 d-block"></i>Nenhuma tarefa encontrada.
                </td></tr>
                <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                <?php
                $isOverdue = !empty($task['prazo']) && DateHelper::isOverdue($task['prazo']) && $task['status'] !== 'concluida';
                ?>
                <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                    <td>
                        <div class="small fw-semibold"><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!empty($task['numero_cnj'])): ?>
                        <div class="text-muted" style="font-size:0.72rem;">
                            <i class="fas fa-gavel me-1"></i><?= htmlspecialchars($task['numero_cnj'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php elseif (!empty($task['client_name'])): ?>
                        <div class="text-muted" style="font-size:0.72rem;">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($task['client_name'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark"><?= ucfirst($task['tipo']) ?></span></td>
                    <td class="small"><?= htmlspecialchars($task['responsavel_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small <?= $isOverdue ? 'text-danger fw-bold' : '' ?>">
                        <?= !empty($task['prazo']) ? DateHelper::formatBr($task['prazo']) : '—' ?>
                    </td>
                    <td><?= FormatHelper::priorityBadge($task['prioridade']) ?></td>
                    <td><?= FormatHelper::statusBadge($task['status']) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if (!in_array($task['status'], ['concluida', 'cancelada'])): ?>
                            <button class="btn btn-outline-success btn-complete-task" data-id="<?= $task['id'] ?>" title="Concluir">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <a href="/tasks/<?= $task['id'] ?>/edit" class="btn btn-outline-secondary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-delete-task" data-id="<?= $task['id'] ?>" title="Excluir">
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
        <small class="text-muted">Exibindo <?= count($tasks) ?> de <?= $pagination['total'] ?> tarefas</small>
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

<form id="taskActionForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
</form>

<script>
document.querySelectorAll('.btn-complete-task').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Marcar tarefa como concluída?')) return;
        const form = document.getElementById('taskActionForm');
        form.action = `/tasks/${btn.dataset.id}/complete`;
        form.submit();
    });
});
document.querySelectorAll('.btn-delete-task').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Excluir esta tarefa?')) return;
        const form = document.getElementById('taskActionForm');
        form.action = `/tasks/${btn.dataset.id}/delete`;
        form.submit();
    });
});
</script>
