<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Auditoria e LGPD</h4>
        <p class="text-muted small mb-0">Trilha de auditoria e conformidade com a LGPD</p>
    </div>
    <a href="/admin/audit/export?date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-file-export me-1"></i>Exportar JSON
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="/admin/audit" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Usuário</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Todos os usuários</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Módulo</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($modules as $m): ?>
                    <option value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>" <?= $filter_module === $m ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">De</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Até</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i></button>
            </div>
            <div class="col-md-2">
                <a href="/admin/audit" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-undo me-1"></i>Limpar</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de logs -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
        <span><i class="fas fa-list me-2"></i>Logs de Auditoria</span>
        <span class="badge bg-secondary"><?= count($logs) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Módulo</th>
                    <th>Ação</th>
                    <th>Entidade</th>
                    <th>Descrição</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Nenhum log encontrado para os filtros selecionados.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-muted small text-nowrap">
                        <?= htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="fw-semibold small">
                        <?= htmlspecialchars($log['user_name'] ?? '#' . ($log['user_id'] ?? '?'), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($log['module'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <?php
                        $action = $log['action'] ?? '';
                        $badgeClass = 'secondary';
                        if (in_array($action, ['create', 'store', 'insert'])) $badgeClass = 'success';
                        if (in_array($action, ['update', 'edit']))            $badgeClass = 'warning';
                        if (in_array($action, ['delete', 'destroy']))         $badgeClass = 'danger';
                        if (in_array($action, ['view', 'show', 'index']))     $badgeClass = 'info';
                        ?>
                        <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="small text-muted">
                        <?php if (!empty($log['entity_type'])): ?>
                            <?= htmlspecialchars($log['entity_type'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($log['entity_id'])): ?>#<?= (int)$log['entity_id'] ?><?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="small" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($log['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="small text-muted text-nowrap">
                        <?= htmlspecialchars($log['ip'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (count($logs) >= 500): ?>
    <div class="card-footer text-muted small">
        <i class="fas fa-info-circle me-1"></i>Exibindo os 500 registros mais recentes. Refine os filtros para ver resultados específicos.
    </div>
    <?php endif; ?>
</div>
