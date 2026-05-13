<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Meus Processos</h4>
        <p class="text-muted small mb-0">Olá, <?= htmlspecialchars($client['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>. Veja seus processos abaixo.</p>
    </div>
</div>

<?php if (empty($cases)): ?>
<div class="card text-center py-5">
    <div class="text-muted">
        <i class="fas fa-gavel fa-3x mb-3"></i>
        <p>Você não possui processos cadastrados ainda.</p>
    </div>
</div>
<?php else: ?>

<?php foreach ($cases as $case): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-start">
        <div>
            <div class="fw-semibold"><?= htmlspecialchars($case['numero_cnj'] ?: 'Processo #' . $case['id'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($case['assunto'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?= FormatHelper::statusBadge($case['status']) ?>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="text-muted small">Tribunal</div>
                <div class="small fw-semibold"><?= htmlspecialchars($case['tribunal'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Comarca</div>
                <div class="small fw-semibold"><?= htmlspecialchars($case['comarca'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Fase</div>
                <div class="small fw-semibold"><?= htmlspecialchars($case['fase_processual'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted small">Responsável</div>
                <div class="small fw-semibold"><?= htmlspecialchars($case['responsavel_name'] ?: '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <?php if (!empty($case['proxima_providencia'])): ?>
        <div class="alert alert-info py-2 small mb-3">
            <strong>Próxima Providência:</strong> <?= htmlspecialchars($case['proxima_providencia'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($case['last_movement'])): ?>
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted mb-1"><i class="fas fa-history me-1"></i>Última movimentação:</div>
            <div class="small"><?= nl2br(htmlspecialchars($case['last_movement']['descricao'] ?? '', ENT_QUOTES, 'UTF-8')) ?></div>
            <div class="text-muted" style="font-size:0.72rem;"><?= DateHelper::formatBrDateTime($case['last_movement']['data_movimento'] ?? '') ?></div>
        </div>
        <?php endif; ?>
        <div class="mt-3 timeline-inline-v41">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong class="small"><i class="fas fa-stream me-1"></i>Linha do tempo</strong>
                <span class="badge bg-primary"><?= (int)($case['timeline_progress'] ?? 0) ?>%</span>
            </div>

            <div class="progress mb-3" style="height: 8px;">
                <div class="progress-bar" style="width: <?= (int)($case['timeline_progress'] ?? 0) ?>%;"></div>
            </div>

            <?php if (empty($case['timeline_preview'])): ?>
                <div class="alert alert-light border small mb-2">
                    Ainda não há eventos de linha do tempo disponíveis para este processo.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush border rounded">
                    <?php foreach ($case['timeline_preview'] as $event): ?>
                        <div class="list-group-item px-2 py-2">
                            <div class="d-flex justify-content-between">
                                <strong class="small"><?= htmlspecialchars($event['title'] ?? 'Evento', ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="text-muted" style="font-size:0.72rem;">
                                    <?= !empty($event['event_date']) ? date('d/m/Y', strtotime($event['event_date'])) : '' ?>
                                </span>
                            </div>
                            <div class="small text-muted">
                                <?= htmlspecialchars($event['client_description'] ?? $event['description'] ?? 'Atualização processual.', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-2">
                <a href="/portal/cases/<?= $case['id'] ?>/timeline" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-stream me-1"></i>Ver linha do tempo completa
                </a>
            </div>
        </div>

    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
