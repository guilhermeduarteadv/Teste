<?php /** @var array $case @var array $parties */ ?>
<?php $base = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/'); ?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= $base ?>/cases/<?= (int)$case['id'] ?>" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h4 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Partes do Processo</h4>
        <span class="ms-2 text-muted small"><?= htmlspecialchars((string)$case['numero_cnj'], ENT_QUOTES) ?></span>
        <button class="btn btn-sm btn-info ms-auto me-2" id="btnSync">
            <i class="fas fa-sync me-1"></i>Sincronizar DataJud
        </button>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddParty">
            <i class="fas fa-plus me-1"></i>Adicionar parte
        </button>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tipo</th>
                        <th>Nome</th>
                        <th>CPF/CNPJ</th>
                        <th>Advogado</th>
                        <th>OAB</th>
                        <th>Polo</th>
                        <th>Obs.</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parties)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma parte cadastrada</td></tr>
                    <?php else: ?>
                    <?php foreach ($parties as $p): ?>
                    <tr>
                        <td>
                            <span class="badge <?= $p['tipo'] === 'autor' ? 'bg-success' : ($p['tipo'] === 'reu' ? 'bg-danger' : 'bg-secondary') ?>">
                                <?= htmlspecialchars((string)$p['tipo'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars((string)$p['nome'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)$p['cpf_cnpj'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)$p['advogado'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)$p['advogado_oab'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)$p['polo'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars((string)$p['observacoes'], ENT_QUOTES) ?></td>
                        <td>
                            <form method="POST" action="<?= $base ?>/case-parties/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('Remover?')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Adicionar Parte -->
<div class="modal fade" id="modalAddParty" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= $base ?>/cases/<?= (int)$case['id'] ?>/parties/store" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Parte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small">Tipo *</label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="autor">Autor</option>
                            <option value="reu" selected>Réu</option>
                            <option value="terceiro">Terceiro</option>
                            <option value="testemunha">Testemunha</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Polo</label>
                        <select name="polo" class="form-select form-select-sm">
                            <option value="ativo">Ativo</option>
                            <option value="passivo" selected>Passivo</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Nome *</label>
                        <input type="text" name="nome" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">CPF/CNPJ</label>
                        <input type="text" name="cpf_cnpj" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Advogado da parte</label>
                        <input type="text" name="advogado" class="form-control form-control-sm">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">OAB do advogado</label>
                        <input type="text" name="advogado_oab" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Observações</label>
                        <textarea name="observacoes" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
var base = '<?= $base ?>';
var caseId = '<?= (int)$case['id'] ?>';
document.getElementById('btnSync').addEventListener('click', function() {
    if (!confirm('Buscar partes no DataJud para este processo?')) return;
    var btn = this;
    btn.disabled = true;
    fetch(base + '/cases/' + caseId + '/parties/sync', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'csrf_token=' + encodeURIComponent('<?= $_SESSION['csrf_token'] ?? '' ?>')
    }).then(function(r){return r.json();}).then(function(d){
        alert(d.message);
        if (d.success) location.reload();
        btn.disabled = false;
    }).catch(function(){btn.disabled = false;});
});
</script>
