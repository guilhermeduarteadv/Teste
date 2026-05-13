<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Tribunais habilitados</h4>
        <p class="text-muted small mb-0">Escolha quais tribunais aparecerão no cadastro/edição de processos.</p>
    </div>
</div>

<form method="POST" action="/admin/tribunals/update">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Tribunais disponíveis</strong>
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleTribunais(true)">Selecionar todos</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleTribunais(false)">Limpar</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($knownTribunals as $tribunal): ?>
                <div class="col-6 col-md-3 col-lg-2 mb-2">
                    <label class="form-check border rounded p-2 ps-4 h-100">
                        <input class="form-check-input tribunal-check" type="checkbox" name="tribunais[]" value="<?= htmlspecialchars($tribunal) ?>"
                               <?= in_array($tribunal, $enabledTribunals ?? [], true) ? 'checked' : '' ?>>
                        <span class="form-check-label"><?= htmlspecialchars($tribunal) ?></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Salvar tribunais</button>
        </div>
    </div>
</form>

<script>
function toggleTribunais(checked) {
    document.querySelectorAll('.tribunal-check').forEach(cb => cb.checked = checked);
}
</script>
