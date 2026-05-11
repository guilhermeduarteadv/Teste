<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Logs do Sistema</h4>
        <p class="text-muted small mb-0">Auditoria de ações realizadas no sistema</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/admin/logs" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" name="module">
                    <option value="">Todos Módulos</option>
                    <?php foreach (['cases' => 'Processos', 'clients' => 'Clientes', 'financial' => 'Financeiro', 'documents' => 'Documentos', 'tasks' => 'Tarefas', 'users' => 'Usuários', 'settings' => 'Configurações'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['module'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" name="user_id">
                    <option value="">Todos Usuários</option>
                    <?php foreach ($users ?? [] as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (int)($filters['user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <a href="/admin/logs" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Módulo</th>
                    <th>Ação</th>
                    <th>Registro</th>
                    <th>Descrição</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paginated['data'])): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum log encontrado.</td></tr>
                <?php else: ?>
                <?php
                $actionColors = ['create' => 'success', 'update' => 'primary', 'delete' => 'danger', 'login' => 'info', 'upload' => 'warning', 'download' => 'secondary'];
                foreach ($paginated['data'] as $log):
                ?>
                <tr>
                    <td class="small text-muted"><?= date('d/m/Y H:i:s', strtotime($log['created_at'] ?? 'now')) ?></td>
                    <td class="small"><?= htmlspecialchars($log['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($log['module'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                        <?php $ac = $log['action'] ?? 'info'; ?>
                        <span class="badge bg-<?= $actionColors[$ac] ?? 'secondary' ?>"><?= ucfirst($ac) ?></span>
                    </td>
                    <td class="small"><?= htmlspecialchars($log['entity_type'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= $log['entity_id'] ? '#' . $log['entity_id'] : '' ?></td>
                    <td class="small"><?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (($paginated['last_page'] ?? 1) > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Exibindo <?= count($paginated['data'] ?? []) ?> de <?= $paginated['total'] ?> registros</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = max(1, ($paginated['current_page'] ?? 1) - 2); $i <= min($paginated['last_page'], ($paginated['current_page'] ?? 1) + 2); $i++): ?>
                <li class="page-item <?= $i === $paginated['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($filters['module']) ? '&module=' . urlencode($filters['module']) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
