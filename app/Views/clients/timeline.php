<?php
/** @var array $client */
/** @var array $events */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>Timeline do Cliente</h4>
        <div class="text-muted small"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="/clients/<?= (int)$client['id'] ?>/notes" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-sticky-note me-1"></i>Anotações
        </a>
        <a href="/clients/<?= (int)$client['id'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Voltar ao Cliente
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Events timeline -->
        <?php if (empty($events)): ?>
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-history fa-3x mb-3"></i>
                <div>Nenhum evento registrado na timeline deste cliente.</div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-stream me-1"></i>Histórico de Atendimento</span>
                <span class="badge bg-primary"><?= count($events) ?> evento(s)</span>
            </div>
            <div class="card-body p-0">
                <?php
                $sourceIcons = [
                    'manual'    => ['icon' => 'fas fa-edit',         'color' => '#0891b2'],
                    'system'    => ['icon' => 'fas fa-cog',           'color' => '#6c757d'],
                    'document'  => ['icon' => 'fas fa-file-alt',      'color' => '#4f46e5'],
                    'task'      => ['icon' => 'fas fa-tasks',         'color' => '#d97706'],
                    'financial' => ['icon' => 'fas fa-dollar-sign',   'color' => '#059669'],
                    'case'      => ['icon' => 'fas fa-gavel',         'color' => '#dc2626'],
                ];
                ?>
                <div class="timeline px-4 pt-3 pb-2">
                    <?php foreach ($events as $ev): ?>
                    <?php
                        $src   = $ev['source'] ?? 'manual';
                        $icon  = $sourceIcons[$src]['icon']  ?? 'fas fa-circle';
                        $color = $sourceIcons[$src]['color'] ?? '#6c757d';
                        $visible = !empty($ev['visible_client']);
                        $dt = $ev['event_date'] ?? $ev['created_at'] ?? '';
                        $dtFormatted = '';
                        if ($dt) {
                            try { $dtFormatted = (new DateTime($dt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $dtFormatted = $dt; }
                        }
                    ?>
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0 d-flex flex-column align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:36px;height:36px;background:<?= $color ?>20;border:2px solid <?= $color ?>;">
                                <i class="<?= $icon ?>" style="color:<?= $color ?>;font-size:0.8rem;"></i>
                            </div>
                            <div class="flex-grow-1 border-start" style="width:1px;min-height:20px;margin-top:4px;border-color:#dee2e6!important;"></div>
                        </div>
                        <div class="flex-grow-1 pb-2">
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-1">
                                <div class="fw-semibold"><?= htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <?php if ($visible): ?>
                                    <span class="badge text-bg-success" style="font-size:0.65rem;">
                                        <i class="fas fa-eye me-1"></i>Visível ao cliente
                                    </span>
                                    <?php else: ?>
                                    <span class="badge text-bg-secondary" style="font-size:0.65rem;">
                                        <i class="fas fa-lock me-1"></i>Interno
                                    </span>
                                    <?php endif; ?>
                                    <span class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($dtFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <?php if (!empty($ev['description'])): ?>
                            <div class="text-muted small mt-1"><?= nl2br(htmlspecialchars($ev['description'], ENT_QUOTES, 'UTF-8')) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($ev['author_name'])): ?>
                            <div class="mt-1" style="font-size:0.72rem;color:#999;">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($ev['author_name'], ENT_QUOTES, 'UTF-8') ?>
                                &nbsp;&bull;&nbsp;<?= htmlspecialchars(ucfirst($src), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Add event form -->
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="fas fa-plus-circle me-1 text-primary"></i>Adicionar Evento Manual
            </div>
            <div class="card-body">
                <form method="POST" action="/clients/<?= (int)$client['id'] ?>/timeline/store">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm"
                               placeholder="Ex: Reunião presencial" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descrição</label>
                        <textarea name="description" class="form-control form-control-sm" rows="3"
                                  placeholder="Detalhes do evento..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Visibilidade</label>
                        <select name="visible_client" class="form-select form-select-sm">
                            <option value="1">Visível ao cliente</option>
                            <option value="0">Apenas interno</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-plus me-1"></i>Adicionar Evento
                    </button>
                </form>
            </div>
        </div>

        <!-- Client info card -->
        <div class="card mt-3">
            <div class="card-header fw-semibold small">Dados do Cliente</div>
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Nome:</span>
                    <span class="fw-semibold"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php if (!empty($client['email'])): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">E-mail:</span>
                    <span><?= htmlspecialchars($client['email'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($client['phone'])): ?>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Telefone:</span>
                    <span><?= htmlspecialchars($client['phone'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
