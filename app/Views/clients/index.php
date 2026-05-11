<?php use App\Helpers\FormatHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Clientes</h4>
        <p class="text-muted small mb-0">Total: <?= number_format($pagination['total'] ?? 0) ?> clientes</p>
    </div>
    <a href="/clients/create" class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Novo Cliente</a>
</div>

<!-- Search -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/clients" class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" name="search"
                   placeholder="Buscar por nome, CPF, CNPJ, e-mail..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            <?php if (!empty($search)): ?>
            <a href="/clients" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            <?php endif; ?>
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
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>CPF/CNPJ</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th>Portal</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-2x mb-2 d-block"></i>Nenhum cliente encontrado.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($clients as $client): ?>
                <tr>
                    <td class="text-muted small"><?= $client['id'] ?></td>
                    <td>
                        <a href="/clients/<?= $client['id'] ?>" class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-<?= $client['tipo_pessoa'] === 'fisica' ? 'info' : 'secondary' ?> bg-opacity-75">
                            <?= $client['tipo_pessoa'] === 'fisica' ? 'Física' : 'Jurídica' ?>
                        </span>
                    </td>
                    <td class="small">
                        <?php if ($client['tipo_pessoa'] === 'fisica' && !empty($client['cpf'])): ?>
                            <?= FormatHelper::cpf($client['cpf']) ?>
                        <?php elseif (!empty($client['cnpj'])): ?>
                            <?= FormatHelper::cnpj($client['cnpj']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= !empty($client['phone']) ? FormatHelper::phone($client['phone']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="small"><?= !empty($client['email']) ? htmlspecialchars($client['email'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>' ?></td>
                    <td><?= FormatHelper::statusBadge($client['status']) ?></td>
                    <td>
                        <?php if ($client['portal_access']): ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-times"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="/clients/<?= $client['id'] ?>" class="btn btn-outline-primary" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn-outline-secondary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Excluir"
                                    onclick="confirmDelete('/clients/<?= $client['id'] ?>/delete', '<?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>')">
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

    <!-- Pagination -->
    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Exibindo <?= count($clients ?? []) ?> de <?= $pagination['total'] ?> registros
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Modal -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
</form>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Deseja excluir o cliente <strong id="deleteClientName"></strong>?
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-danger" id="confirmDeleteBtn">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteUrl = '';
function confirmDelete(url, name) {
    deleteUrl = url;
    document.getElementById('deleteClientName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
    const form = document.getElementById('deleteForm');
    form.action = deleteUrl;
    form.method = 'POST';
    form.submit();
});
</script>
