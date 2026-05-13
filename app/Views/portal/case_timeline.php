<?php
$caseTitle = $case['numero_cnj'] ?? ('Processo #' . ($case['id'] ?? ''));
$progress = (int)($progress ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Linha do tempo do processo</h4>
        <p class="text-muted small mb-0"><?= htmlspecialchars($caseTitle, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/portal/cases" class="btn btn-sm btn-outline-secondary">Voltar</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <strong>Andamento estimado</strong>
            <strong><?= $progress ?>%</strong>
        </div>
        <div class="progress" style="height: 16px;">
            <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
        </div>
    </div>
</div>

<style>
    .client-timeline { border-left: 3px solid #0d6efd; margin-left: 12px; padding-left: 24px; }
    .client-event { position: relative; margin-bottom: 18px; padding: 14px; border: 1px solid #eee; border-radius: 12px; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.03); }
    .client-event:before { content: ""; position: absolute; left: -34px; top: 18px; width: 16px; height: 16px; border-radius: 50%; background: #0d6efd; border: 3px solid #fff; box-shadow: 0 0 0 2px #0d6efd33; }
    .client-date { font-size: .8rem; color: #6c757d; }
</style>

<div class="client-timeline">
    <?php if (empty($timeline)): ?>
        <div class="alert alert-info">Ainda não há eventos disponíveis para visualização.</div>
    <?php endif; ?>

    <?php foreach ($timeline as $event): ?>
        <div class="client-event">
            <strong><?= htmlspecialchars($event['title'] ?? 'Evento', ENT_QUOTES, 'UTF-8') ?></strong>
            <div class="client-date">
                <?= !empty($event['event_date']) ? date('d/m/Y H:i', strtotime($event['event_date'])) : '' ?>
            </div>
            <p class="mb-0 mt-2">
                <?= nl2br(htmlspecialchars($event['client_description'] ?? $event['description'] ?? 'Atualização processual.', ENT_QUOTES, 'UTF-8')) ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>
