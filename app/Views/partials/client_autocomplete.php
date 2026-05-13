<?php
/**
 * Componente de autocomplete de cliente.
 *
 * Variáveis esperadas:
 * - $clientFieldName: nome do campo hidden. Ex.: client_id ou clients[]
 * - $clientMultiple: bool
 * - $selectedClients: array de clientes já vinculados
 * - $selectedClient: cliente único já selecionado
 * - $roleField: bool — exibe papel processual
 */
$clientFieldName = $clientFieldName ?? 'client_id';
$clientMultiple = $clientMultiple ?? false;
$selectedClients = $selectedClients ?? [];
$selectedClient = $selectedClient ?? null;
$roleField = $roleField ?? false;
$componentId = 'client_auto_' . substr(md5($clientFieldName . uniqid('', true)), 0, 8);

if (!$clientMultiple && $selectedClient) {
    $selectedClients = [$selectedClient];
}
?>

<div class="client-autocomplete-component" id="<?= $componentId ?>" data-multiple="<?= $clientMultiple ? '1' : '0' ?>" data-field="<?= htmlspecialchars($clientFieldName, ENT_QUOTES, 'UTF-8') ?>" data-role="<?= $roleField ? '1' : '0' ?>">
    <label class="form-label fw-semibold">Cliente</label>
    <input type="text" class="form-control client-search-input" placeholder="Digite nome, CPF/CNPJ, e-mail ou telefone para buscar cliente cadastrado" autocomplete="off">
    <div class="list-group client-search-results mt-1 shadow-sm" style="position:relative; z-index:20;"></div>

    <div class="selected-clients mt-2">
        <?php foreach ($selectedClients as $cl): ?>
            <?php
                $cid = (int)($cl['id'] ?? 0);
                if (!$cid) continue;
                $doc = $cl['cpf_cnpj'] ?? $cl['cpf'] ?? $cl['cnpj'] ?? '';
                $nome = $cl['name'] ?? $cl['nome'] ?? ('Cliente #' . $cid);
                $participacao = $cl['participacao'] ?? $cl['tipo'] ?? 'autor';
            ?>
            <div class="selected-client border rounded p-2 mb-2 bg-light d-flex justify-content-between align-items-center" data-id="<?= $cid ?>">
                <div>
                    <strong><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($doc): ?><small class="text-muted ms-1"><?= htmlspecialchars($doc, ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($roleField): ?>
                    <select name="client_tipos[<?= $cid ?>]" class="form-select form-select-sm" style="width:160px">
                        <?php foreach(['autor'=>'Autor','reu'=>'Réu','exequente'=>'Exequente','executado'=>'Executado','requerente'=>'Requerente','requerido'=>'Requerido','assistente'=>'Assistente','terceiro'=>'Terceiro interessado','litisconsorte'=>'Litisconsorte','outro'=>'Outro'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $participacao===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <input type="hidden" name="<?= htmlspecialchars($clientFieldName, ENT_QUOTES, 'UTF-8') ?>" value="<?= $cid ?>">
                    <button type="button" class="btn btn-sm btn-outline-danger client-remove-btn">Remover</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function(){
    const root = document.getElementById(<?= json_encode($componentId) ?>);
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    const input = root.querySelector('.client-search-input');
    const results = root.querySelector('.client-search-results');
    const selectedBox = root.querySelector('.selected-clients');
    const multiple = root.dataset.multiple === '1';
    const fieldName = root.dataset.field || 'client_id';
    const withRole = root.dataset.role === '1';
    const selected = new Set(Array.from(root.querySelectorAll('.selected-client')).map(el => String(el.dataset.id)));
    let timer = null;

    function esc(str) {
        return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    function basePath() {
        return window.APP_BASE_PATH || '';
    }

    async function search(q) {
        if (q.length < 2) {
            results.innerHTML = '';
            return;
        }

        results.innerHTML = '<div class="list-group-item small text-muted">Buscando...</div>';

        try {
            const response = await fetch(basePath() + '/api/clients/search?q=' + encodeURIComponent(q));
            const data = await response.json();

            if (!data.success || !data.clients || data.clients.length === 0) {
                results.innerHTML = '<div class="list-group-item small text-muted">Nenhum cliente cadastrado encontrado.</div>';
                return;
            }

            results.innerHTML = data.clients.map(c => {
                const doc = c.cpf_cnpj || c.cpf || c.cnpj || '';
                const email = c.email || '';
                const tel = c.phone || c.telefone || '';
                return `<button type="button" class="list-group-item list-group-item-action client-result" data-client='${esc(JSON.stringify(c))}'>
                    <div class="d-flex justify-content-between">
                        <strong>${esc(c.name || c.nome || ('Cliente #' + c.id))}</strong>
                        <span class="badge bg-primary">Vincular</span>
                    </div>
                    <div class="small text-muted">${esc(doc)} ${esc(email)} ${esc(tel)}</div>
                </button>`;
            }).join('');
        } catch (e) {
            results.innerHTML = '<div class="list-group-item small text-danger">Erro ao buscar clientes: ' + esc(e.message) + '</div>';
        }
    }

    function roleSelect(id) {
        if (!withRole) return '';
        return `<select name="client_tipos[${id}]" class="form-select form-select-sm" style="width:160px">
            <option value="autor">Autor</option>
            <option value="reu">Réu</option>
            <option value="exequente">Exequente</option>
            <option value="executado">Executado</option>
            <option value="requerente">Requerente</option>
            <option value="requerido">Requerido</option>
            <option value="assistente">Assistente</option>
            <option value="terceiro">Terceiro interessado</option>
            <option value="litisconsorte">Litisconsorte</option>
            <option value="outro">Outro</option>
        </select>`;
    }

    function addClient(c) {
        const id = String(c.id || '');
        if (!id) return;

        if (!multiple) {
            selected.clear();
            selectedBox.innerHTML = '';
        }

        if (selected.has(id)) {
            results.innerHTML = '';
            input.value = '';
            return;
        }

        selected.add(id);
        const doc = c.cpf_cnpj || c.cpf || c.cnpj || '';
        const name = c.name || c.nome || ('Cliente #' + id);

        const div = document.createElement('div');
        div.className = 'selected-client border rounded p-2 mb-2 bg-light d-flex justify-content-between align-items-center';
        div.dataset.id = id;
        div.innerHTML = `<div>
                <strong>${esc(name)}</strong>
                ${doc ? `<small class="text-muted ms-1">${esc(doc)}</small>` : ''}
            </div>
            <div class="d-flex gap-2 align-items-center">
                ${roleSelect(id)}
                <input type="hidden" name="${esc(fieldName)}" value="${esc(id)}">
                <button type="button" class="btn btn-sm btn-outline-danger client-remove-btn">Remover</button>
            </div>`;

        selectedBox.appendChild(div);
        input.value = '';
        results.innerHTML = '';
    }

    input.addEventListener('input', function(){
        clearTimeout(timer);
        const q = this.value.trim();
        timer = setTimeout(() => search(q), 300);
    });

    results.addEventListener('click', function(e){
        const btn = e.target.closest('.client-result');
        if (!btn) return;
        try {
            addClient(JSON.parse(btn.dataset.client || '{}'));
        } catch (e) {}
    });

    selectedBox.addEventListener('click', function(e){
        const btn = e.target.closest('.client-remove-btn');
        if (!btn) return;
        const item = btn.closest('.selected-client');
        if (item) {
            selected.delete(String(item.dataset.id));
            item.remove();
        }
    });

    document.addEventListener('click', function(e){
        if (!root.contains(e.target)) {
            results.innerHTML = '';
        }
    });
})();
</script>
