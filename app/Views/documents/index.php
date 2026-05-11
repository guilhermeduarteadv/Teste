<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Documentos</h4>
        <p class="text-muted small mb-0">Gerenciamento de arquivos e documentos</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="fas fa-upload me-2"></i>Enviar Documento
    </button>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/documents" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Buscar documento..."
                       value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" name="categoria">
                    <option value="">Todas Categorias</option>
                    <?php foreach (['contrato' => 'Contrato', 'peticao' => 'Petição', 'decisao' => 'Decisão', 'procuracao' => 'Procuração', 'comprovante' => 'Comprovante', 'outros' => 'Outros'] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= ($filters['categoria'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" name="entity_type">
                    <option value="">Todos Tipos</option>
                    <option value="case" <?= ($filters['entity_type'] ?? '') === 'case' ? 'selected' : '' ?>>Processo</option>
                    <option value="client" <?= ($filters['entity_type'] ?? '') === 'client' ? 'selected' : '' ?>>Cliente</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <a href="/documents" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Document Grid -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Categoria</th>
                    <th>Vinculado a</th>
                    <th>Tamanho</th>
                    <th>Enviado em</th>
                    <th>Portal</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documents)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>Nenhum documento encontrado.
                </td></tr>
                <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php
                            $iconMap = ['pdf' => 'fa-file-pdf text-danger', 'doc' => 'fa-file-word text-primary', 'docx' => 'fa-file-word text-primary', 'xls' => 'fa-file-excel text-success', 'xlsx' => 'fa-file-excel text-success', 'jpg' => 'fa-file-image text-info', 'jpeg' => 'fa-file-image text-info', 'png' => 'fa-file-image text-info'];
                            $icon = $iconMap[$doc['extension']] ?? 'fa-file text-secondary';
                            ?>
                            <i class="fas <?= $icon ?> fa-lg"></i>
                            <div>
                                <div class="small fw-semibold"><?= htmlspecialchars($doc['original_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($doc['descricao']) && $doc['descricao'] !== $doc['original_name']): ?>
                                <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($doc['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-secondary"><?= ucfirst($doc['categoria']) ?></span></td>
                    <td class="small text-muted"><?= ucfirst($doc['entity_type']) ?> #<?= $doc['entity_id'] ?></td>
                    <td class="small"><?= FormatHelper::fileSize($doc['size'] ?? 0) ?></td>
                    <td class="small"><?= DateHelper::formatBr($doc['created_at'] ?? '') ?></td>
                    <td>
                        <?php if ($doc['visivel_cliente']): ?>
                        <span class="badge bg-success">Sim</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Não</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if (in_array($doc['extension'], ['pdf', 'jpg', 'jpeg', 'png'])): ?>
                            <a href="/documents/<?= $doc['id'] ?>/preview" class="btn btn-outline-info" title="Visualizar" target="_blank">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                            <a href="/documents/<?= $doc['id'] ?>/download" class="btn btn-outline-primary" title="Baixar">
                                <i class="fas fa-download"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-delete-doc" data-id="<?= $doc['id'] ?>" title="Excluir">
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
        <small class="text-muted">Exibindo <?= count($documents) ?> de <?= $pagination['total'] ?> documentos</small>
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

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-upload me-2"></i>Enviar Documento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="/documents/upload" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Arquivo *</label>
                        <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" required>
                        <div class="form-text">Formatos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX. Máx. 20MB.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="descricao" maxlength="255">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" name="categoria">
                                <option value="outros">Outros</option>
                                <option value="contrato">Contrato</option>
                                <option value="peticao">Petição</option>
                                <option value="decisao">Decisão</option>
                                <option value="procuracao">Procuração</option>
                                <option value="comprovante">Comprovante</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Vinculado a</label>
                            <select class="form-select" name="entity_type">
                                <option value="client">Cliente</option>
                                <option value="case">Processo</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">ID do Registro</label>
                        <input type="number" class="form-control" name="entity_id" min="1">
                    </div>
                    <div class="mt-3 form-check">
                        <input type="checkbox" class="form-check-input" name="visivel_cliente" id="docVisible">
                        <label class="form-check-label" for="docVisible">Visível no portal do cliente</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Enviar</button></div>
            </form>
        </div>
    </div>
</div>

<form id="deleteDocForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
</form>

<script>
document.querySelectorAll('.btn-delete-doc').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Excluir este documento?')) return;
        const form = document.getElementById('deleteDocForm');
        form.action = `/documents/${btn.dataset.id}/delete`;
        form.submit();
    });
});
</script>
