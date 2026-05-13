<?php
$moduleLabels = [
    'cases'      => 'Processos',
    'tasks'      => 'Tarefas',
    'documents'  => 'Documentos',
    'financial'  => 'Financeiro',
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Checklists Automáticos</h4>
        <p class="text-muted small mb-0">Templates de checklist por tipo de procedimento</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNewTemplate">
        <i class="fas fa-plus me-1"></i>Novo Template
    </button>
</div>

<?php if (empty($templates)): ?>
<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhum template cadastrado.</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nome do Template</th>
                    <th>Módulo</th>
                    <th>Tipo de Procedimento</th>
                    <th>Itens</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $tpl): ?>
                <?php
                $items = json_decode($tpl['items_json'] ?? '[]', true);
                $numItems = is_array($items) ? count($items) : 0;
                ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($tpl['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($moduleLabels[$tpl['module']] ?? $tpl['module'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $tpl['procedure_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-secondary"><?= $numItems ?> itens</span></td>
                    <td>
                        <?php if ($tpl['active']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" onclick="viewItems(<?= (int)$tpl['id'] ?>, <?= htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8') ?>)" title="Ver itens">
                            <i class="fas fa-eye"></i>
                        </button>
                        <form method="post" action="/checklists/<?= (int)$tpl['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Excluir template?')">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Novo Template -->
<div class="modal fade" id="modalNewTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Novo Template de Checklist</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/checklists/store">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome do Template <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Ex: Ação de Indenização">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Módulo</label>
                            <select name="module" class="form-select">
                                <option value="cases">Processos</option>
                                <option value="tasks">Tarefas</option>
                                <option value="documents">Documentos</option>
                                <option value="financial">Financeiro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de Procedimento</label>
                            <input type="text" name="procedure_type" class="form-control" placeholder="Ex: acao_indenizacao">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Itens do Checklist <small class="text-muted">(um por linha)</small></label>
                        <textarea name="items_raw" class="form-control" rows="10" placeholder="Exemplo:&#10;Reunir documentos pessoais&#10;Procuração&#10;Petição inicial"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Criar Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Ver Itens -->
<div class="modal fade" id="modalViewItems" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Itens do Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="itemListContent"></div>
        </div>
    </div>
</div>

<script>
function viewItems(id, items) {
    var html = '<ol class="mb-0">';
    if (items && items.length > 0) {
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            html += '<li class="mb-1">' + (item.title || '') + (item.required ? ' <span class="badge bg-danger ms-1">Obrigatório</span>' : '') + '</li>';
        }
    } else {
        html += '<li class="text-muted">Nenhum item.</li>';
    }
    html += '</ol>';
    document.getElementById('itemListContent').innerHTML = html;
    var modal = new bootstrap.Modal(document.getElementById('modalViewItems'));
    modal.show();
}
</script>
