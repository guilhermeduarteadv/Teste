<?php use App\Helpers\FormatHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Editar Cliente</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/clients">Clientes</a></li>
            <li class="breadcrumb-item"><a href="/clients/<?= $client['id'] ?>"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol></nav>
    </div>
    <a href="/clients/<?= $client['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/clients/<?= $client['id'] ?>/update" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="mb-4">
                <label class="form-label fw-semibold">Tipo de Pessoa *</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="tipo_pessoa" id="tipo_fisica" value="fisica"
                               <?= $client['tipo_pessoa'] === 'fisica' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tipo_fisica">Pessoa Física</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="tipo_pessoa" id="tipo_juridica" value="juridica"
                               <?= $client['tipo_pessoa'] === 'juridica' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tipo_juridica">Pessoa Jurídica</label>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold">Nome / Razão Social *</label>
                    <input type="text" class="form-control" name="name" required maxlength="200"
                           value="<?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-2 pf-field">
                    <label class="form-label fw-semibold">CPF</label>
                    <input type="text" class="form-control" name="cpf" id="cpf" placeholder="000.000.000-00" maxlength="14"
                           value="<?= !empty($client['cpf']) ? FormatHelper::cpf($client['cpf']) : '' ?>">
                </div>
                <div class="col-6 col-md-2 pf-field">
                    <label class="form-label fw-semibold">RG</label>
                    <input type="text" class="form-control" name="rg" maxlength="20"
                           value="<?= htmlspecialchars($client['rg'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-4 pj-field">
                    <label class="form-label fw-semibold">CNPJ</label>
                    <input type="text" class="form-control" name="cnpj" id="cnpj" placeholder="00.000.000/0000-00" maxlength="18"
                           value="<?= !empty($client['cnpj']) ? FormatHelper::cnpj($client['cnpj']) : '' ?>">
                </div>
                <div class="col-6 col-md-3 pf-field">
                    <label class="form-label fw-semibold">Data Nascimento</label>
                    <input type="date" class="form-control" name="data_nascimento"
                           value="<?= htmlspecialchars($client['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3 pf-field">
                    <label class="form-label fw-semibold">Estado Civil</label>
                    <select class="form-select" name="estado_civil">
                        <option value="">Selecione...</option>
                        <?php foreach (['solteiro' => 'Solteiro(a)', 'casado' => 'Casado(a)', 'divorciado' => 'Divorciado(a)', 'viuvo' => 'Viúvo(a)', 'uniao_estavel' => 'União Estável', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($client['estado_civil'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3 pf-field">
                    <label class="form-label fw-semibold">Profissão</label>
                    <input type="text" class="form-control" name="profissao" maxlength="100"
                           value="<?= htmlspecialchars($client['profissao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="fw-semibold text-muted small text-uppercase">Contato</h6></div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">Telefone</label>
                    <input type="text" class="form-control" name="phone" id="phone" placeholder="(00) 00000-0000"
                           value="<?= !empty($client['phone']) ? FormatHelper::phone($client['phone']) : '' ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">WhatsApp</label>
                    <input type="text" class="form-control" name="whatsapp" id="whatsapp" placeholder="(00) 00000-0000"
                           value="<?= !empty($client['whatsapp']) ? FormatHelper::phone($client['whatsapp']) : '' ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">E-mail</label>
                    <input type="email" class="form-control" name="email"
                           value="<?= htmlspecialchars($client['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="fw-semibold text-muted small text-uppercase">Endereço</h6></div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">CEP</label>
                    <input type="text" class="form-control" name="cep" id="cep" placeholder="00000-000" maxlength="9"
                           value="<?= !empty($client['cep']) ? FormatHelper::cep($client['cep']) : '' ?>">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold">Endereço</label>
                    <input type="text" class="form-control" name="endereco" id="endereco" maxlength="255"
                           value="<?= htmlspecialchars($client['endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold">Número</label>
                    <input type="text" class="form-control" name="numero"
                           value="<?= htmlspecialchars($client['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">Complemento</label>
                    <input type="text" class="form-control" name="complemento"
                           value="<?= htmlspecialchars($client['complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label fw-semibold">Bairro</label>
                    <input type="text" class="form-control" name="bairro" id="bairro"
                           value="<?= htmlspecialchars($client['bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-8 col-md-5">
                    <label class="form-label fw-semibold">Cidade</label>
                    <input type="text" class="form-control" name="cidade" id="cidade"
                           value="<?= htmlspecialchars($client['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-4 col-md-3">
                    <label class="form-label fw-semibold">Estado</label>
                    <select class="form-select" name="estado" id="estado">
                        <option value="">UF</option>
                        <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
                        <option value="<?= $uf ?>" <?= ($client['estado'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= $client['status'] === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= $client['status'] === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Outras Informações</label>
                    <textarea class="form-control" name="outras_informacoes" rows="3"><?= htmlspecialchars($client['outras_informacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                <a href="/clients/<?= $client['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[name="tipo_pessoa"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        const isPF = this.value === 'fisica';
        document.querySelectorAll('.pf-field').forEach(el => el.style.display = isPF ? '' : 'none');
        document.querySelectorAll('.pj-field').forEach(el => el.style.display = isPF ? 'none' : '');
    });
});
document.querySelector('input[name="tipo_pessoa"]:checked')?.dispatchEvent(new Event('change'));
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
</script>
