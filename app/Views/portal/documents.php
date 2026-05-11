<?php use App\Helpers\FormatHelper; use App\Helpers\DateHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Documentos</h4>
        <p class="text-muted small mb-0">Documentos disponibilizados pelo escritório</p>
    </div>
</div>

<?php if (empty($documents)): ?>
<div class="card text-center py-5">
    <div class="text-muted">
        <i class="fas fa-folder-open fa-3x mb-3"></i>
        <p>Nenhum documento disponível no momento.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="list-group list-group-flush">
        <?php foreach ($documents as $doc): ?>
        <?php
        $iconMap = ['pdf' => 'fa-file-pdf text-danger', 'doc' => 'fa-file-word text-primary', 'docx' => 'fa-file-word text-primary', 'xls' => 'fa-file-excel text-success', 'xlsx' => 'fa-file-excel text-success', 'jpg' => 'fa-file-image text-info', 'jpeg' => 'fa-file-image text-info', 'png' => 'fa-file-image text-info'];
        $icon = $iconMap[$doc['extension'] ?? ''] ?? 'fa-file text-secondary';
        ?>
        <div class="list-group-item border-0 d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center gap-3">
                <i class="fas <?= $icon ?> fa-lg"></i>
                <div>
                    <div class="small fw-semibold"><?= htmlspecialchars($doc['original_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-muted" style="font-size:0.72rem;">
                        <?= FormatHelper::fileSize($doc['size'] ?? 0) ?> &bull;
                        Enviado em <?= DateHelper::formatBr($doc['created_at'] ?? '') ?>
                        <?php if (!empty($doc['uploaded_by_name'])): ?>
                        por <?= htmlspecialchars($doc['uploaded_by_name'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="/portal/documents/<?= $doc['id'] ?>/download" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-download me-1"></i>Baixar
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
