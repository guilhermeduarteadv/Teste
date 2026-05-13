<?php
/** @var array $availableVars */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Novo Modelo de Documento</h4>
        <div class="text-muted small">Crie minutas e textos-base com variáveis dinâmicas</div>
    </div>
    <a href="/templates" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold">Dados do Modelo</div>
            <div class="card-body">
                <form method="POST" action="/templates/store">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" class="form-control"
                                   value="<?= htmlspecialchars($_old['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Ex: Procuração Ad Judicia" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select name="tipo" class="form-select">
                                <?php foreach (['procuracao' => 'Procuração', 'contrato' => 'Contrato', 'recibo' => 'Recibo', 'declaracao' => 'Declaração', 'relatorio' => 'Relatório', 'outro' => 'Outro'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= (($_old['tipo'] ?? 'outro') === $val) ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Área</label>
                            <input type="text" name="area" class="form-control"
                                   value="<?= htmlspecialchars($_old['area'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Cível, Família...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Conteúdo <span class="text-danger">*</span></label>
                        <p class="small text-muted mb-1">Use as variáveis listadas à direita para inserir dados dinâmicos.</p>
                        <textarea name="conteudo" class="form-control font-monospace" rows="18"
                                  placeholder="Digite o conteúdo do modelo aqui..."><?= htmlspecialchars($_old['conteudo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="active" id="active" value="1"
                               <?= (($_old['active'] ?? '1') === '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active">Modelo ativo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Modelo
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
                <p class="small text-muted px-2">Clique para copiar e colar no conteúdo:</p>
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
        var ta = document.querySelector('textarea[name="conteudo"]');
        if (ta) {
            var s = ta.selectionStart, e = ta.selectionEnd;
            ta.value = ta.value.substring(0,s) + v + ta.value.substring(e);
            ta.selectionStart = ta.selectionEnd = s + v.length;
            ta.focus();
        }
    });
});
</script>
