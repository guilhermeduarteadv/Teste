<?php /** @var array $publication @var array $cases */ ?>
<?php $base = rtrim($_ENV['APP_BASE_PATH'] ?? '', '/'); ?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center mb-3">
        <a href="<?= $base ?>/publications" class="btn btn-sm btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <h4 class="mb-0"><i class="fas fa-newspaper me-2 text-primary"></i>Publicação #<?= htmlspecialchars((string)$publication['id'], ENT_QUOTES) ?></h4>
        <span class="ms-3 badge <?= $publication['status'] === 'reviewed' ? 'bg-success' : ($publication['status'] === 'ignored' ? 'bg-secondary' : ($publication['status'] === 'linked' ? 'bg-info' : 'bg-warning text-dark')) ?>">
            <?= htmlspecialchars((string)($publication['status'] ?? 'pending'), ENT_QUOTES) ?>
        </span>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header fw-semibold">Conteúdo</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Diário</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars((string)$publication['diario'], ENT_QUOTES) ?></dd>
                        <dt class="col-sm-3">Data</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars((string)$publication['data_publicacao'], ENT_QUOTES) ?></dd>
                        <dt class="col-sm-3">Tribunal</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars((string)$publication['tribunal'], ENT_QUOTES) ?></dd>
                        <dt class="col-sm-3">Nº CNJ</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars((string)$publication['numero_cnj'], ENT_QUOTES) ?></dd>
                        <dt class="col-sm-3">Processo</dt>
                        <dd class="col-sm-9">
                            <?php if ($publication['case_id']): ?>
                                <a href="<?= $base ?>/cases/<?= (int)$publication['case_id'] ?>">
                                    <?= htmlspecialchars((string)$publication['case_titulo'], ENT_QUOTES) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Não vinculado</span>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-sm-3">Título</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars((string)$publication['titulo'], ENT_QUOTES) ?></dd>
                        <dt class="col-sm-3">Fonte</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars((string)$publication['fonte'], ENT_QUOTES) ?></dd>
                    </dl>
                    <hr>
                    <div class="bg-light p-3 rounded" style="white-space:pre-wrap;font-size:.9rem;max-height:400px;overflow-y:auto">
                        <?= htmlspecialchars((string)$publication['texto'], ENT_QUOTES) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Vincular processo -->
            <div class="card mb-3">
                <div class="card-header fw-semibold">Vincular Processo</div>
                <div class="card-body">
                    <form id="formLink">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <select name="case_id" class="form-select mb-2">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($cases as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $c['id'] == $publication['case_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$c['numero_cnj'] . ' — ' . $c['titulo'], ENT_QUOTES) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Salvar vínculo</button>
                    </form>
                </div>
            </div>

            <!-- Criar prazo -->
            <div class="card mb-3">
                <div class="card-header fw-semibold">Criar Prazo</div>
                <div class="card-body">
                    <form id="formDeadline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="mb-2">
                            <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Título do prazo" value="Prazo de publicação">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Data base</label>
                            <input type="date" name="data_base" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$publication['data_publicacao'], ENT_QUOTES) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Dias corridos</label>
                            <input type="number" name="dias" class="form-control form-control-sm" value="15" min="1">
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm w-100">Criar prazo</button>
                    </form>
                </div>
            </div>

            <!-- Atualizar status -->
            <div class="card">
                <div class="card-header fw-semibold">Status</div>
                <div class="card-body">
                    <form id="formStatus">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <select name="status" class="form-select form-select-sm mb-2">
                            <?php foreach (['pending' => 'Pendente', 'reviewed' => 'Revisado', 'linked' => 'Vinculado', 'ignored' => 'Ignorado'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($publication['status'] ?? 'pending') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm w-100">Salvar status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var base = '<?= $base ?>';
var pubId = '<?= (int)$publication['id'] ?>';

document.getElementById('formLink').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch(base + '/publications/' + pubId + '/link-case', {method:'POST', body: new URLSearchParams(fd)})
        .then(function(r){return r.json();})
        .then(function(d){alert(d.message);});
});

document.getElementById('formDeadline').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch(base + '/publications/' + pubId + '/create-deadline', {method:'POST', body: new URLSearchParams(fd)})
        .then(function(r){return r.json();})
        .then(function(d){alert(d.message);});
});

document.getElementById('formStatus').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch(base + '/publications/' + pubId + '/status', {method:'POST', body: new URLSearchParams(fd)})
        .then(function(r){return r.json();})
        .then(function(d){alert(d.message);if(d.success)location.reload();});
});
</script>
