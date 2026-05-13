<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : '/public';
$caseTitle = $case['numero_cnj'] ?? ('Processo #' . ($case['id'] ?? ''));
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Linha do tempo processual</h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($caseTitle) ?></p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="rebuildTimeline()">Reconstruir timeline</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <strong>Andamento estimado</strong>
                <strong><?= (int)$progress ?>%</strong>
            </div>
            <div class="progress" style="height: 18px;">
                <div class="progress-bar" role="progressbar" style="width: <?= (int)$progress ?>%;"
                     aria-valuenow="<?= (int)$progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted">Estimativa automática baseada nos eventos relevantes da timeline.</small>
        </div>
    </div>

    <style>
        .timeline-wrap { position: relative; margin-left: 18px; padding-left: 28px; }
        .timeline-wrap:before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #dee2e6; border-radius: 8px; }
        .timeline-item { position: relative; margin-bottom: 22px; }
        .timeline-dot { position: absolute; left: -36px; top: 4px; width: 18px; height: 18px; background: #0d6efd; border: 3px solid #fff; box-shadow: 0 0 0 2px #0d6efd33; border-radius: 50%; }
        .timeline-dot.important { background: #dc3545; box-shadow: 0 0 0 2px #dc354533; }
        .timeline-card { border: 1px solid #e9ecef; border-radius: 14px; padding: 16px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
        .timeline-date { font-size: .85rem; color: #6c757d; }
        .timeline-badge { font-size: .75rem; }
    </style>

    <div class="timeline-wrap">
        <?php if (empty($timeline)): ?>
            <div class="alert alert-info">Nenhum evento encontrado. Clique em “Reconstruir timeline” após sincronizar movimentações.</div>
        <?php endif; ?>

        <?php foreach ($timeline as $event): ?>
            <div class="timeline-item">
                <span class="timeline-dot <?= !empty($event['is_important']) ? 'important' : '' ?>"></span>
                <div class="timeline-card">
                    <div class="d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($event['title'] ?? 'Evento') ?></h5>
                            <div class="timeline-date">
                                <?= !empty($event['event_date']) ? date('d/m/Y H:i', strtotime($event['event_date'])) : '' ?>
                            </div>
                        </div>
                        <div>
                            <?php if (!empty($event['is_important'])): ?>
                                <span class="badge bg-danger timeline-badge">Importante</span>
                            <?php endif; ?>
                            <?php if (!empty($event['visible_client'])): ?>
                                <span class="badge bg-success timeline-badge">Visível ao cliente</span>
                            <?php else: ?>
                                <span class="badge bg-secondary timeline-badge">Interno</span>
                            <?php endif; ?>
                            <?php if (!empty($event['event_type'])): ?>
                                <span class="badge bg-light text-dark timeline-badge"><?= htmlspecialchars($event['event_type']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($event['description'])): ?>
                        <p class="mt-3 mb-2"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($event['client_description'])): ?>
                        <div class="alert alert-light border mb-0">
                            <strong>Texto para cliente:</strong>
                            <?= nl2br(htmlspecialchars($event['client_description'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function rebuildTimeline() {
    fetch('<?= $baseUrl ?>/cases/<?= (int)($case['id'] ?? 0) ?>/timeline/rebuild', {method: 'POST'})
        .then(r => r.json())
        .then(data => {
            alert(data.message || 'Timeline atualizada.');
            location.reload();
        })
        .catch(err => alert('Erro ao reconstruir timeline: ' + err.message));
}
</script>
