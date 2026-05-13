<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Checklist do Processo</h4>
        <p class="text-muted small mb-0">
            Processo: <strong><?= htmlspecialchars($case['numero_cnj'] ?? $case['titulo'] ?? '#'.(int)$case['id'], ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
    </div>
    <a href="/cases/<?= (int)$case['id'] ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao Processo
    </a>
</div>

<!-- Progresso -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold">Progresso do Checklist</span>
            <span class="fw-bold text-primary"><?= $progress ?>% (<?= $done ?>/<?= $total ?>)</span>
        </div>
        <div class="progress" style="height: 18px;">
            <div class="progress-bar <?= $progress >= 100 ? 'bg-success' : 'bg-primary' ?>"
                 role="progressbar" style="width: <?= $progress ?>%;"
                 aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                <?= $progress ?>%
            </div>
        </div>
    </div>
</div>

<!-- Aplicar Template -->
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="fas fa-plus-circle me-2 text-success"></i>Aplicar Template</div>
    <div class="card-body">
        <?php if (empty($templates)): ?>
            <p class="text-muted mb-0">Nenhum template disponível. <a href="/checklists">Criar templates</a>.</p>
        <?php else: ?>
        <form id="applyTemplateForm" class="d-flex gap-2 align-items-end flex-wrap">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="flex-grow-1">
                <label class="form-label small fw-semibold mb-1">Selecionar Template</label>
                <select name="template_id" id="templateSelect" class="form-select">
                    <option value="">-- Escolha um template --</option>
                    <?php foreach ($templates as $tpl): ?>
                        <?php $alreadyApplied = in_array((string)$tpl['id'], $appliedTemplates) || in_array((int)$tpl['id'], $appliedTemplates); ?>
                        <option value="<?= (int)$tpl['id'] ?>" <?= $alreadyApplied ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($tpl['name'], ENT_QUOTES, 'UTF-8') ?>
                            <?= $alreadyApplied ? ' (já aplicado)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn btn-success btn-sm" onclick="applyTemplate()">
                <i class="fas fa-check me-1"></i>Aplicar
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Lista de Itens -->
<div class="card">
    <div class="card-header fw-semibold"><i class="fas fa-list-check me-2"></i>Itens do Checklist</div>
    <?php if (empty($checklistItems)): ?>
    <div class="card-body text-center text-muted py-4">
        <i class="fas fa-clipboard fa-2x mb-2"></i>
        <p class="mb-0">Nenhum item. Aplique um template ou os itens serão exibidos aqui.</p>
    </div>
    <?php else: ?>
    <ul class="list-group list-group-flush" id="checklistList">
        <?php foreach ($checklistItems as $item): ?>
        <li class="list-group-item d-flex align-items-start gap-3" id="item-row-<?= (int)$item['id'] ?>">
            <div class="mt-1">
                <input type="checkbox"
                       class="form-check-input"
                       style="width:1.2rem;height:1.2rem;cursor:pointer;"
                       <?= $item['done'] ? 'checked' : '' ?>
                       onchange="toggleItem(<?= (int)$item['id'] ?>, this)">
            </div>
            <div class="flex-grow-1">
                <span class="fw-semibold <?= $item['done'] ? 'text-decoration-line-through text-muted' : '' ?>" id="item-title-<?= (int)$item['id'] ?>">
                    <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($item['required']): ?>
                        <span class="badge bg-danger ms-1 small">Obrigatório</span>
                    <?php endif; ?>
                </span>
                <?php if (!empty($item['description'])): ?>
                    <div class="text-muted small"><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($item['done'] && $item['done_at']): ?>
                    <div class="text-success small mt-1">
                        <i class="fas fa-check-circle me-1"></i>Concluído em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['done_at'])), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($item['done_by_name'])): ?>
                            por <?= htmlspecialchars($item['done_by_name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>

<script>
var CSRF_TOKEN = '<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>';

function toggleItem(id, checkbox) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/case-checklist-items/' + id + '/toggle');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
    xhr.onload = function() {
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
                var title = document.getElementById('item-title-' + id);
                if (data.done) {
                    title && title.classList.add('text-decoration-line-through', 'text-muted');
                } else {
                    title && title.classList.remove('text-decoration-line-through', 'text-muted');
                }
            } else {
                alert(data.message || 'Erro ao atualizar item.');
                checkbox.checked = !checkbox.checked;
            }
        } catch(e) {
            checkbox.checked = !checkbox.checked;
        }
    };
    xhr.onerror = function() { checkbox.checked = !checkbox.checked; };
    xhr.send('_csrf_token=' + encodeURIComponent(CSRF_TOKEN));
}

function applyTemplate() {
    var select = document.getElementById('templateSelect');
    var templateId = select ? select.value : '';
    if (!templateId) { alert('Selecione um template.'); return; }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/cases/<?= (int)$case['id'] ?>/checklist/apply');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao aplicar template.');
            }
        } catch(e) { location.reload(); }
    };
    xhr.send('_csrf_token=' + encodeURIComponent(CSRF_TOKEN) + '&template_id=' + encodeURIComponent(templateId));
}
</script>
