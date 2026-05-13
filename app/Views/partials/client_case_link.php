<?php
$clientFieldName = $clientFieldName ?? 'client_id';
$caseFieldName = $caseFieldName ?? 'case_id';
$selectedClient = $selectedClient ?? null;
$selectedCase = $selectedCase ?? null;
$labelCliente = $labelCliente ?? 'Cliente';
$labelProcesso = $labelProcesso ?? 'Processo';
$componentId = 'client_case_' . substr(md5($clientFieldName . $caseFieldName . uniqid('', true)), 0, 8);
?>

<div class="client-case-link-component row g-3" id="<?= $componentId ?>">
    <div class="col-md-6">
        <?php
        $clientFieldNameOriginal = $clientFieldName;
        $clientFieldName = $clientFieldNameOriginal;
        $clientMultiple = false;
        $roleField = false;
        include ROOT_PATH . '/app/Views/partials/client_autocomplete.php';
        $clientFieldName = $clientFieldNameOriginal;
        ?>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold"><?= htmlspecialchars($labelProcesso, ENT_QUOTES, 'UTF-8') ?></label>
        <select class="form-select dependent-case-select" name="<?= htmlspecialchars($caseFieldName, ENT_QUOTES, 'UTF-8') ?>">
            <option value="">Selecione primeiro um cliente...</option>
            <?php if (!empty($selectedCase)): ?>
                <option value="<?= (int)$selectedCase['id'] ?>" selected>
                    <?= htmlspecialchars(($selectedCase['numero_cnj'] ?? '') . ' - ' . ($selectedCase['assunto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endif; ?>
        </select>
        <div class="form-text">A lista de processos será filtrada pelo cliente selecionado.</div>
    </div>
</div>

<script>
(function(){
    const root = document.getElementById(<?= json_encode($componentId) ?>);
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    const caseSelect = root.querySelector('.dependent-case-select');

    function basePath() {
        return window.APP_BASE_PATH || '';
    }

    function currentClientId() {
        const hidden = root.querySelector('input[name="<?= htmlspecialchars($clientFieldName, ENT_QUOTES, 'UTF-8') ?>"]');
        return hidden ? hidden.value : '';
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    async function loadCases(clientId) {
        if (!clientId) {
            caseSelect.innerHTML = '<option value="">Selecione primeiro um cliente...</option>';
            return;
        }

        caseSelect.innerHTML = '<option value="">Carregando processos...</option>';

        try {
            const response = await fetch(basePath() + '/api/cases/by-client?client_id=' + encodeURIComponent(clientId));
            const data = await response.json();

            if (!data.success || !data.cases || data.cases.length === 0) {
                caseSelect.innerHTML = '<option value="">Nenhum processo vinculado a este cliente</option>';
                return;
            }

            caseSelect.innerHTML = '<option value="">Selecione...</option>' + data.cases.map(c => {
                const label = (c.numero_cnj || 'Sem número') + (c.assunto ? ' - ' + c.assunto : (c.classe ? ' - ' + c.classe : ''));
                return `<option value="${escapeHtml(c.id)}">${escapeHtml(label)}</option>`;
            }).join('');
        } catch (e) {
            caseSelect.innerHTML = '<option value="">Erro ao carregar processos</option>';
        }
    }

    const selectedBox = root.querySelector('.selected-clients');
    if (selectedBox) {
        const observer = new MutationObserver(() => loadCases(currentClientId()));
        observer.observe(selectedBox, {childList: true, subtree: true});
    }

    const initialClientId = currentClientId();
    if (initialClientId) {
        loadCases(initialClientId);
    }
})();
</script>
