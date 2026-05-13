<?php
/** @var string $query */
/** @var array  $results */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-search me-2 text-primary"></i>Busca Global</h4>
        <div class="text-muted small">Pesquise clientes, processos, tarefas, documentos e mais</div>
    </div>
</div>

<!-- Search form -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form action="/search" method="GET">
            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input
                    type="text"
                    name="q"
                    class="form-control border-start-0 ps-0"
                    placeholder="Digite o que deseja buscar..."
                    value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                    autofocus
                >
                <button class="btn btn-primary px-4" type="submit">Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($query !== '' && strlen($query) < 2): ?>
<div class="alert alert-warning">
    <i class="fas fa-info-circle me-2"></i>Digite pelo menos 2 caracteres para realizar a busca.
</div>
<?php elseif ($query !== '' && empty($results)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nenhum resultado para "<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"</h5>
        <p class="text-muted small mb-0">Tente outros termos ou verifique a ortografia.</p>
    </div>
</div>
<?php elseif (!empty($results)): ?>

<!-- Total count -->
<?php
$total = 0;
foreach ($results as $group) {
    $total += count($group['items']);
}
?>
<div class="mb-3 text-muted small">
    <i class="fas fa-info-circle me-1"></i>
    <?= $total ?> resultado(s) encontrado(s) para "<strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong>"
</div>

<?php foreach ($results as $typeKey => $group): ?>
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="<?= htmlspecialchars($group['icon'], ENT_QUOTES, 'UTF-8') ?> text-primary"></i>
        <span class="fw-semibold"><?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="badge bg-secondary ms-auto"><?= count($group['items']) ?></span>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($group['items'] as $item): ?>
        <a href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3">
            <div class="flex-shrink-0 text-center" style="width:36px;height:36px;background:var(--sidebar-active);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> text-primary"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold text-truncate">
                    <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php if (!empty($item['subtitle'])): ?>
                <div class="small text-muted text-truncate">
                    <?= htmlspecialchars($item['subtitle'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-chevron-right text-muted small"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>
