<?php $old = $old ?? []; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Novo Cliente</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/clients">Clientes</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol></nav>
    </div>
    <a href="/clients" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/clients/store" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <!-- Tipo Pessoa -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Tipo de Pessoa *</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="tipo_pessoa" id="tipo_fisica" value="fisica"
                               <?= ($old['tipo_pessoa'] ?? 'fisica') === 'fisica' ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="tipo_fisica">Pessoa Física</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="tipo_pessoa" id="tipo_juridica" value="juridica"
                               <?= ($old['tipo_pessoa'] ?? '') === 'juridica' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tipo_juridica">Pessoa Jurídica</label>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Nome -->
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">Nome Completo / Razão Social *</label>
                    <input type="text" class="form-control" name="name" required
                           value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="200">
                </div>

                <!-- Campos PF -->
                <div class="col-6 col-md-2 pf-field">
                    <label class="form-label fw-semibold">CPF</label>
                    <input type="text" class="form-control" name="cpf" id="cpf" placeholder="000.000.000-00" maxlength="14"
                           value="<?= htmlspecialchars($old['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-2 pf-field">
                    <label class="form-label fw-semibold">RG</label>
                    <input type="text" class="form-control" name="rg" maxlength="20"
                           value="<?= htmlspecialchars($old['rg'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Campos PJ -->
                <div class="col-6 col-md-4 pj-field" style="display:none;">
                    <label class="form-label fw-semibold">CNPJ</label>
                    <input type="text" class="form-control" name="cnpj" id="cnpj" placeholder="00.000.000/0000-00" maxlength="18"
                           value="<?= htmlspecialchars($old['cnpj'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- PF Only -->
                <div class="col-6 col-md-3 pf-field">
                    <label class="form-label fw-semibold">Data Nascimento</label>
                    <input type="date" class="form-control" name="data_nascimento"
                           value="<?= htmlspecialchars($old['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3 pf-field">
                    <label class="form-label fw-semibold">Estado Civil</label>
                    <select class="form-select" name="estado_civil">
                        <option value="">Selecione...</option>
                        <?php foreach (['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viuvo' => 'Viúvo(a)', 'uniao_estavel' => 'União Estável', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['estado_civil'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 pf-field">
                    <label class="form-label fw-semibold">Profissão</label>
                    <input type="text" class="form-control" name="profissao" maxlength="100"
                           value="<?= htmlspecialchars($old['profissao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Contato -->
                <div class="col-12"><hr class="my-1"><h6 class="fw-semibold text-muted small text-uppercase">Contato</h6></div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">Telefone</label>
                    <input type="text" class="form-control" name="phone" id="phone" placeholder="(00) 00000-0000"
                           value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">WhatsApp</label>
                    <input type="text" class="form-control" name="whatsapp" id="whatsapp" placeholder="(00) 00000-0000"
                           value="<?= htmlspecialchars($old['whatsapp'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">E-mail</label>
                    <input type="email" class="form-control" name="email" placeholder="exemplo@email.com"
                           value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <!-- Endereço -->
                <div class="col-12"><hr class="my-1"><h6 class="fw-semibold text-muted small text-uppercase">Endereço</h6></div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">CEP</label>
                    <input type="text" class="form-control" name="cep" id="cep" placeholder="00000-000" maxlength="9"
                           value="<?= htmlspecialchars($old['cep'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold">Endereço</label>
                    <input type="text" class="form-control" name="endereco" id="endereco" maxlength="255"
                           value="<?= htmlspecialchars($old['endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">Número</label>
                    <input type="text" class="form-control" name="numero" maxlength="20"
                           value="<?= htmlspecialchars($old['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">Complemento</label>
                    <input type="text" class="form-control" name="complemento" maxlength="100"
                           value="<?= htmlspecialchars($old['complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold">Bairro</label>
                    <input type="text" class="form-control" name="bairro" id="bairro" maxlength="100"
                           value="<?= htmlspecialchars($old['bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-8 col-md-5">
                    <label class="form-label fw-semibold">Cidade</label>
                    <input type="text" class="form-control" name="cidade" id="cidade" maxlength="100"
                           value="<?= htmlspecialchars($old['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-4 col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">UF</option>
                        <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
                        <option value="<?= $uf ?>" <?= ($old['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Observações -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Outras Informações</label>
                    <textarea class="form-control" name="outras_informacoes" rows="3"
                              placeholder="Observações adicionais..."><?= htmlspecialchars($old['outras_informacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Cliente</button>
                <a href="/clients" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle PF/PJ fields
document.querySelectorAll('input[name="tipo_pessoa"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const isPF = this.value === 'fisica';
        document.querySelectorAll('.pf-field').forEach(el => el.style.display = isPF ? '' : 'none');
        document.querySelectorAll('.pj-field').forEach(el => el.style.display = isPF ? 'none' : '');
    });
});
// Set initial state
document.querySelector('input[name="tipo_pessoa"]:checked')?.dispatchEvent(new Event('change'));

// CEP auto-fill
document.getElementById('cep')?.addEventListener('blur', async function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        try {
            const resp = await fetch(APP_BASE_PATH + '/api/cep/' + cep);
            const json = await resp.json();
            if (json.success) {
                document.getElementById('endereco').value = json.logradouro || '';
                document.getElementById('bairro').value = json.bairro || '';
                document.getElementById('cidade').value = json.localidade || '';
                document.getElementById('estado').value = json.uf || '';
            }
        } catch(e) {}
    }
});

// Phone mask
function maskPhone(el) {
    el.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length > 6) v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        else if (v.length > 2) v = v.replace(/^(\d{2})(\d+)/, '($1) $2');
        this.value = v;
    });
}
maskPhone(document.getElementById('phone'));
maskPhone(document.getElementById('whatsapp'));

// CPF mask
document.getElementById('cpf')?.addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').slice(0, 11);
    v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
    this.value = v;
});

// CNPJ mask
document.getElementById('cnpj')?.addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').slice(0, 14);
    v = v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2})/, '$1.$2.$3/$4-$5');
    this.value = v;
});
</script>
