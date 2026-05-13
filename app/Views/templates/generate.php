<?php
/** @var array $template */
/** @var array|null $result */
/** @var array $clients */
/** @var array $cases */
/** @var string $entityType */
/** @var int $entityId */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-magic me-2 text-success"></i>Gerar Documento</h4>
        <div class="text-muted small">Modelo: <?= htmlspecialchars($template['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="/templates/<?= $template['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-edit me-1"></i>Editar Modelo
        </a>
        <a href="/templates" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<?php if (empty($result)): ?>
<!-- Selection form -->
<div class="card mb-4">
    <div class="card-header fw-semibold">Selecionar Entidade</div>
    <div class="card-body">
        <form method="POST" action="/templates/<?= $template['id'] ?>/generate">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tipo de Entidade</label>
                    <select name="entity_type" class="form-select" id="entityTypeSelect">
                        <option value="client">Cliente</option>
                        <option value="case">Processo</option>
                    </select>
                </div>
                <div class="col-md-8" id="clientSection">
                    <label class="form-label fw-semibold">Selecionar Cliente</label>
                    <select name="entity_id" class="form-select" id="clientSelect">
                        <option value="">-- Selecione um cliente --</option>
                        <?php foreach (($clients ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 d-none" id="caseSection">
                    <label class="form-label fw-semibold">Selecionar Processo</label>
                    <select name="entity_id_case" class="form-select" id="caseSelect">
                        <option value="">-- Selecione um processo --</option>
                        <?php foreach (($cases ?? []) as $c): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= htmlspecialchars(($c['titulo'] ?: $c['numero_cnj'] ?: 'Processo #' . $c['id']), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-magic me-1"></i>Gerar Documento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Template preview -->
<div class="card">
    <div class="card-header fw-semibold">Prévia do Modelo (sem substituição)</div>
    <div class="card-body">
        <div class="border rounded p-3 bg-light" style="white-space:pre-wrap;font-family:serif;line-height:1.8;">
            <?= htmlspecialchars($template['conteudo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </div>
    </div>
</div>

<script>
var sel = document.getElementById('entityTypeSelect');
if (sel) {
    sel.addEventListener('change', function() {
        var clientSec = document.getElementById('clientSection');
        var caseSec   = document.getElementById('caseSection');
        if (this.value === 'case') {
            clientSec.classList.add('d-none');
            caseSec.classList.remove('d-none');
        } else {
            clientSec.classList.remove('d-none');
            caseSec.classList.add('d-none');
        }
    });
}
// Make sure we submit the right entity_id
document.querySelector('form').addEventListener('submit', function(e) {
    var type = document.getElementById('entityTypeSelect').value;
    var hiddenId = document.createElement('input');
    hiddenId.type = 'hidden';
    hiddenId.name = 'entity_id';
    if (type === 'case') {
        hiddenId.value = document.getElementById('caseSelect').value;
        // Remove the incorrectly-named input to avoid duplicate
        document.querySelector('[name="entity_id"]').remove();
    } else {
        // entity_id is already set correctly (clientSelect)
        return;
    }
    this.appendChild(hiddenId);
});
</script>

<?php else: ?>
<!-- Generated document result -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold"><i class="fas fa-file-alt me-1 text-success"></i>Documento Gerado</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Imprimir
                    </button>
                    <a href="/generated-documents" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-list me-1"></i>Ver Todos
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="border rounded p-4 bg-white" id="docContent"
                     style="white-space:pre-wrap;font-family:serif;line-height:1.9;font-size:1rem;">
                    <?= htmlspecialchars($result['content'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">Informações</div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="small text-muted">Modelo utilizado</dt>
                    <dd class="mb-2"><?= htmlspecialchars($template['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="small text-muted">ID do documento</dt>
                    <dd class="mb-2">#<?= (int)($result['doc_id'] ?? 0) ?></dd>
                    <dt class="small text-muted">Título</dt>
                    <dd class="mb-2"><?= htmlspecialchars($result['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="small text-muted">Tipo de entidade</dt>
                    <dd class="mb-2"><?= htmlspecialchars($entityType ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
                <hr>
                <a href="/templates/<?= $template['id'] ?>/generate-form" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="fas fa-redo me-1"></i>Gerar Outro
                </a>
                <a href="/templates/<?= $template['id'] ?>/edit" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-edit me-1"></i>Editar Modelo
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
