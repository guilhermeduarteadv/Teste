<?php
/** @var array $client */
/** @var array $notes */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-sticky-note me-2 text-warning"></i>Anotações Internas</h4>
        <div class="text-muted small"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="d-flex gap-2">
        <a href="/clients/<?= (int)$client['id'] ?>/timeline" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-history me-1"></i>Timeline
        </a>
        <a href="/clients/<?= (int)$client['id'] ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Voltar ao Cliente
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if (empty($notes)): ?>
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-sticky-note fa-3x mb-3 text-warning opacity-50"></i>
                <div>Nenhuma anotação registrada para este cliente.</div>
            </div>
        </div>
        <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($notes as $note): ?>
            <?php
                $dtFormatted = '';
                $dt = $note['created_at'] ?? '';
                if ($dt) {
                    try { $dtFormatted = (new DateTime($dt))->format('d/m/Y H:i'); } catch (\Throwable $e) { $dtFormatted = $dt; }
                }
                $isClient = ($note['visibility'] ?? 'internal') === 'client';
            ?>
            <div class="card <?= $isClient ? 'border-success' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($isClient): ?>
                            <span class="badge text-bg-success" style="font-size:0.65rem;">
                                <i class="fas fa-eye me-1"></i>Visível ao cliente
                            </span>
                            <?php else: ?>
                            <span class="badge text-bg-secondary" style="font-size:0.65rem;">
                                <i class="fas fa-lock me-1"></i>Somente interno
                            </span>
                            <?php endif; ?>
                            <span class="small text-muted">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($note['author_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <span class="small text-muted"><?= htmlspecialchars($dtFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div style="white-space:pre-wrap;"><?= htmlspecialchars($note['note'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="fas fa-plus me-1 text-primary"></i>Nova Anotação
            </div>
            <div class="card-body">
                <form method="POST" action="/clients/<?= (int)$client['id'] ?>/notes/store">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Anotação <span class="text-danger">*</span></label>
                        <textarea name="note" class="form-control form-control-sm" rows="6"
                                  placeholder="Escreva a anotação..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Visibilidade</label>
                        <select name="visibility" class="form-select form-select-sm">
                            <option value="internal" selected>Somente interno</option>
                            <option value="client">Visível ao cliente</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-save me-1"></i>Salvar Anotação
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
