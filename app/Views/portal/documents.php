
<?php
$usedBytes = (int)($usedBytes ?? 0);
$limitBytes = (int)($limitBytes ?? (10 * 1024 * 1024));
$percent = $limitBytes > 0 ? min(100, round(($usedBytes / $limitBytes) * 100)) : 0;
?>

<div class="card mb-4">
    <div class="card-header"><strong>Enviar documentos</strong></div>
    <div class="card-body">
        <div class="mb-3">
            <div class="d-flex justify-content-between small">
                <span>Uso do limite de upload</span>
                <strong><?= number_format($usedBytes / 1024 / 1024, 2, ',', '.') ?> MB / 10 MB</strong>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar" style="width: <?= $percent ?>%;"></div>
            </div>
            <small class="text-muted">Arquivos maiores devem ser enviados por e-mail.</small>
        </div>

        <form method="POST" action="/portal/documents/upload" enctype="multipart/form-data" class="row g-2">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select name="categoria" class="form-select">
                    <option value="procuracao">Procuração</option>
                    <option value="declaracao">Declaração</option>
                    <option value="identidade">Documento de identidade</option>
                    <option value="comprovante_endereco">Comprovante de endereço</option>
                    <option value="contrato">Contrato</option>
                    <option value="outros">Outros</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control" placeholder="Ex.: RG, procuração assinada...">
            </div>
            <div class="col-md-4">
                <label class="form-label">Arquivo</label>
                <input type="file" name="document" class="form-control" required>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary">Enviar documento</button>
            </div>
        </form>
    </div>
</div>

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
