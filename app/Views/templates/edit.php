<?php
/** @var array $template */
/** @var array $availableVars */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-edit me-2 text-primary"></i>Editar Modelo</h4>
        <div class="text-muted small"><?= htmlspecialchars($template['titulo'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="/templates/<?= $template['id'] ?>/generate-form" class="btn btn-success btn-sm">
            <i class="fas fa-magic me-1"></i>Gerar Documento
        </a>
        <a href="/templates" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold">Dados do Modelo</div>
            <div class="card-body">
                <form method="POST" action="/templates/<?= $template['id'] ?>/update">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control"
                                   value="<?= htmlspecialchars($template['titulo'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select name="tipo" class="form-select">
                                <?php foreach (['procuracao' => 'Procuração', 'contrato' => 'Contrato', 'recibo' => 'Recibo', 'declaracao' => 'Declaração', 'relatorio' => 'Relatório', 'outro' => 'Outro'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= (($template['tipo'] ?? 'outro') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Área</label>
                            <input type="text" name="area" class="form-control"
                                   value="<?= htmlspecialchars($template['area'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Conteúdo <span class="text-danger">*</span></label>
                        <textarea name="conteudo" id="conteudo" class="form-control font-monospace" rows="18"><?= htmlspecialchars($template['conteudo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Variáveis encontradas no conteúdo</label>
                        <input type="text" class="form-control form-control-sm font-monospace bg-light"
                               id="varsFound" readonly placeholder="Nenhuma variável detectada">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="active" id="active" value="1"
                               <?= !empty($template['active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Modelo ativo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="/templates" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card sticky-top" style="top:80px">
            <div class="card-header fw-semibold">
                <i class="fas fa-code me-1 text-primary"></i>Variáveis Disponíveis
            </div>
            <div class="card-body p-2">
                <p class="small text-muted px-2">Clique para inserir no conteúdo:</p>
                <div class="d-flex flex-wrap gap-1 px-2">
                    <?php foreach ($availableVars as $var): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm var-btn"
                            data-var="<?= htmlspecialchars($var, ENT_QUOTES, 'UTF-8') ?>"
                            style="font-size:0.7rem;font-family:monospace;">
                        <?= htmlspecialchars($var, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.var-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var v = this.getAttribute('data-var');
        var ta = document.getElementById('conteudo');
        if (ta) {
            var s = ta.selectionStart, e = ta.selectionEnd;
            ta.value = ta.value.substring(0,s) + v + ta.value.substring(e);
            ta.selectionStart = ta.selectionEnd = s + v.length;
            ta.focus();
        }
        updateVarsFound();
    });
});

function updateVarsFound() {
    var ta = document.getElementById('conteudo');
    var inp = document.getElementById('varsFound');
    if (!ta || !inp) return;
    var matches = ta.value.match(/\{[a-z_]+\}/g);
    var unique = matches ? [...new Set(matches)] : [];
    inp.value = unique.length ? unique.join(', ') : 'Nenhuma variável detectada';
}

document.getElementById('conteudo').addEventListener('input', updateVarsFound);
updateVarsFound();
</script>
