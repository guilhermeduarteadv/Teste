<?php
/** @var array $documents */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-file-medical me-2 text-primary"></i>Documentos Gerados</h4>
        <div class="text-muted small">Histórico de documentos gerados a partir de modelos</div>
    </div>
    <a href="/templates" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Modelos
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Modelo</th>
                        <th>Entidade</th>
                        <th>Criado por</th>
                        <th>Data</th>
                        <th>Formato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-file fa-2x mb-2 d-block"></i>
                            Nenhum documento gerado ainda.
                            <br><a href="/templates" class="btn btn-primary btn-sm mt-2">Ver Modelos</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td class="text-muted small">#<?= (int)$doc['id'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($doc['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($doc['template_titulo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small">
                            <?php if ($doc['entity_type'] === 'client' && $doc['client_id']): ?>
                                <a href="/clients/<?= (int)$doc['client_id'] ?>"><i class="fas fa-user me-1"></i>Cliente #<?= (int)$doc['client_id'] ?></a>
                            <?php elseif ($doc['entity_type'] === 'case' && $doc['case_id']): ?>
                                <a href="/cases/<?= (int)$doc['case_id'] ?>"><i class="fas fa-gavel me-1"></i>Processo #<?= (int)$doc['case_id'] ?></a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($doc['created_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-muted">
                            <?php
                            $dt = $doc['created_at'] ?? '';
                            if ($dt) {
                                try {
                                    $d = new DateTime($dt);
                                    echo $d->format('d/m/Y H:i');
                                } catch (\Throwable $e) {
                                    echo htmlspecialchars($dt, ENT_QUOTES, 'UTF-8');
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= htmlspecialchars(strtoupper($doc['output_format'] ?? 'HTML'), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
