<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Usuários</h4>
        <p class="text-muted small mb-0">Gestão de usuários e permissões</p>
    </div>
    <a href="/admin/users/create" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Novo Usuário</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>OAB</th>
                    <th>Status</th>
                    <th>Cadastrado</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($paginated['data'])): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
                <?php else: ?>
                <?php foreach ($paginated['data'] as $u): ?>
                <tr>
                    <td class="text-muted small"><?= $u['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm" style="width:32px;height:32px;border-radius:50%;background:#1a56db;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600;font-size:0.85rem;flex-shrink:0;">
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="small fw-semibold"><?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($u['cargo'])): ?>
                                <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($u['cargo'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="small"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-<?= $u['role_name'] === 'admin' ? 'danger' : 'primary' ?>"><?= htmlspecialchars($u['role_name'] ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="small"><?= !empty($u['oab_number']) ? htmlspecialchars($u['oab_number'], ENT_QUOTES, 'UTF-8') . '/' . $u['oab_state'] : '—' ?></td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                        <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= date('d/m/Y', strtotime($u['created_at'] ?? 'now')) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="/admin/users/<?= $u['id'] ?>/edit" class="btn btn-outline-secondary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-delete-user" data-id="<?= $u['id'] ?>"
                                    data-name="<?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>" title="Excluir">
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
    <?php if (($paginated['last_page'] ?? 1) > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Exibindo <?= count($paginated['data'] ?? []) ?> de <?= $paginated['total'] ?> usuários</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $paginated['last_page']; $i++): ?>
                <li class="page-item <?= $i === $paginated['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0"><h5 class="modal-title">Confirmar Exclusão</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Excluir o usuário <strong id="deleteUserName"></strong>?</div>
            <div class="modal-footer border-0">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-danger" id="confirmDeleteBtn">Excluir</button>
            </div>
        </div>
    </div>
</div>
<form id="deleteUserForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
</form>

<script>
let deleteUserId = null;
document.querySelectorAll('.btn-delete-user').forEach(btn => {
    btn.addEventListener('click', () => {
        deleteUserId = btn.dataset.id;
        document.getElementById('deleteUserName').textContent = btn.dataset.name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
    const form = document.getElementById('deleteUserForm');
    form.action = (window.APP_BASE_PATH || '') + `/admin/users/${deleteUserId}/delete`;
    form.submit();
});
</script>
