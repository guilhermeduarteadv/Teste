<?php /** @var array $repases @var array $clients @var array $cases */ ?>
<?php $base = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/'); ?>
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fas fa-hand-holding-usd me-2 text-success"></i>Repasses ao Cliente</h4>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoRepasse">
            <i class="fas fa-plus me-1"></i>Novo Repasse
        </button>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Processo</th>
                        <th>Vl. Recebido</th>
                        <th>Honorários</th>
                        <th>Vl. Repasse</th>
                        <th>Dt. Recebimento</th>
                        <th>Dt. Repasse</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($repases)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Nenhum repasse cadastrado</td></tr>
                    <?php else: ?>
                    <?php foreach ($repases as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$r['client_nome'], ENT_QUOTES) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars((string)$r['case_titulo'], ENT_QUOTES) ?></td>
                        <td>R$ <?= number_format((float)$r['valor_recebido'], 2, ',', '.') ?></td>
                        <td><?= number_format((float)$r['percentual_honorarios'], 1, ',', '.') ?>%</td>
                        <td class="fw-semibold">R$ <?= number_format((float)$r['valor_repassado'], 2, ',', '.') ?></td>
                        <td><?= $r['data_recebimento'] ? date('d/m/Y', strtotime($r['data_recebimento'])) : '—' ?></td>
                        <td><?= $r['data_repasse'] ? date('d/m/Y', strtotime($r['data_repasse'])) : '—' ?></td>
                        <td>
                            <span class="badge <?= $r['status'] === 'repassado' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= $r['status'] === 'repassado' ? 'Repassado' : 'Pendente' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['status'] !== 'repassado'): ?>
                            <button class="btn btn-sm btn-outline-success btn-confirm" data-id="<?= (int)$r['id'] ?>">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-outline-danger btn-del" data-id="<?= (int)$r['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Novo Repasse -->
<div class="modal fade" id="modalNovoRepasse" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= $base ?>/financial/repases/store" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header">
                <h5 class="modal-title">Novo Repasse ao Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small">Cliente *</label>
                        <select name="client_id" class="form-select form-select-sm" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['nome'], ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Processo</label>
                        <select name="case_id" class="form-select form-select-sm">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($cases as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars((string)$c['numero_cnj'] . ' — ' . $c['assunto'], ENT_QUOTES) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Valor recebido (R$) *</label>
                        <input type="text" name="valor_recebido" class="form-control form-control-sm" required placeholder="0,00" id="valRecebido">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Honorários (%)</label>
                        <input type="text" name="percentual_honorarios" class="form-control form-control-sm" placeholder="0" id="pctHon" value="0">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Valor a repassar (R$)</label>
                        <input type="text" name="valor_repassado" class="form-control form-control-sm" id="valRepasse" placeholder="0,00">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Data recebimento *</label>
                        <input type="date" name="data_recebimento" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Descrição</label>
                        <textarea name="descricao" class="form-control form-control-sm" rows="2" placeholder="Condenação, acordo, etc."></textarea>
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
var csrf = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>';

// Auto-calculate repasse
function calcRepasse() {
    var recv = parseFloat(document.getElementById('valRecebido').value.replace(',','.')) || 0;
    var pct  = parseFloat(document.getElementById('pctHon').value.replace(',','.')) || 0;
    var rep  = recv - (recv * pct / 100);
    document.getElementById('valRepasse').value = rep.toFixed(2).replace('.',',');
}
document.getElementById('valRecebido').addEventListener('input', calcRepasse);
document.getElementById('pctHon').addEventListener('input', calcRepasse);

// Confirm repasse
document.querySelectorAll('.btn-confirm').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        var dt = prompt('Data do repasse (YYYY-MM-DD):', new Date().toISOString().slice(0,10));
        if (!dt) return;
        fetch(base + '/financial/repases/' + id + '/confirm', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'csrf_token=' + encodeURIComponent(csrf) + '&data_repasse=' + encodeURIComponent(dt)
        }).then(function(r){return r.json();}).then(function(d){if(d.success) location.reload();});
    });
});

// Delete
document.querySelectorAll('.btn-del').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('Excluir repasse?')) return;
        var id = this.dataset.id;
        fetch(base + '/financial/repases/' + id + '/delete', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'csrf_token=' + encodeURIComponent(csrf)
        }).then(function(r){return r.json();}).then(function(d){if(d.success) location.reload();});
    });
});
</script>
