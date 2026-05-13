<?php
$evidenceTypes = [
    'documento'   => 'Documento',
    'print'       => 'Print',
    'audio'       => 'Áudio',
    'video'       => 'Vídeo',
    'testemunha'  => 'Testemunha',
    'protocolo'   => 'Protocolo',
    'email'       => 'E-mail',
    'comprovante' => 'Comprovante',
    'laudo'       => 'Laudo',
    'outro'       => 'Outro',
];
$strengthLabels = [
    'fraca'      => ['label' => 'Fraca',     'badge' => 'secondary'],
    'media'      => ['label' => 'Média',     'badge' => 'warning'],
    'forte'      => ['label' => 'Forte',     'badge' => 'primary'],
    'essencial'  => ['label' => 'Essencial', 'badge' => 'danger'],
];
?>
<div class="page-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-balance-scale me-2 text-primary"></i>Provas do Processo</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="/cases">Processos</a></li>
                    <li class="breadcrumb-item"><a href="/cases/<?= (int)$case['id'] ?>"><?= htmlspecialchars($case['numero_cnj'] ?: $case['assunto'], ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="breadcrumb-item active">Provas</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddEvidence">
            <i class="fas fa-plus me-1"></i>Adicionar Prova
        </button>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="fas fa-list me-2 text-primary"></i>
            <span>Rol de Provas</span>
            <span class="badge bg-secondary ms-2"><?= count($evidences) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($evidences)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-balance-scale fa-3x mb-3 opacity-25"></i>
                <p>Nenhuma prova cadastrada para este processo.</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddEvidence">
                    <i class="fas fa-plus me-1"></i>Adicionar primeira prova
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th>Força Probatória</th>
                            <th>Nota Jurídica</th>
                            <th>Visível</th>
                            <th>Adicionado por</th>
                            <th>Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($evidences as $ev): ?>
                        <tr>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= htmlspecialchars($evidenceTypes[$ev['evidence_type']] ?? $ev['evidence_type'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($ev['description'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($ev['description'], 0, 80), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($ev['description']) > 80 ? '…' : '' ?></small>
                                <?php endif; ?>
                                <?php if (!empty($ev['document_name'])): ?>
                                <br><small class="text-info"><i class="fas fa-paperclip me-1"></i><?= htmlspecialchars($ev['document_name'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $str = $strengthLabels[$ev['probative_strength']] ?? ['label' => $ev['probative_strength'], 'badge' => 'secondary']; ?>
                                <span class="badge bg-<?= $str['badge'] ?>"><?= $str['label'] ?></span>
                            </td>
                            <td>
                                <?php if (!empty($ev['legal_note'])): ?>
                                <span class="badge bg-dark" title="<?= htmlspecialchars($ev['legal_note'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-lock me-1"></i>Interno
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ev['visible_client']): ?>
                                <span class="badge bg-success"><i class="fas fa-eye me-1"></i>Sim</span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-eye-slash me-1"></i>Não</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($ev['created_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small text-nowrap">
                                <?= !empty($ev['created_at']) ? date('d/m/Y', strtotime($ev['created_at'])) : '—' ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="/evidence/<?= (int)$ev['id'] ?>/edit" class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <form action="/evidence/<?= (int)$ev['id'] ?>/delete" method="POST" class="d-inline"
                                      onsubmit="return confirm('Confirma a exclusão desta prova?');">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Adicionar Prova -->
<div class="modal fade" id="modalAddEvidence" tabindex="-1" aria-labelledby="modalAddEvidenceLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="/cases/<?= (int)$case['id'] ?>/evidence/store" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddEvidenceLabel">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Adicionar Prova
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="255"
                                   placeholder="Descreva sucintamente a prova">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select name="evidence_type" class="form-select">
                                <?php foreach ($evidenceTypes as $val => $lbl): ?>
                                <option value="<?= $val ?>"><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Força Probatória</label>
                            <select name="probative_strength" class="form-select">
                                <option value="fraca">Fraca</option>
                                <option value="media" selected>Média</option>
                                <option value="forte">Forte</option>
                                <option value="essencial">Essencial</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="Detalhamento da prova, contexto, relevância..."></textarea>
                        </div>
                        <?php if (!empty($documents)): ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Documento vinculado</label>
                            <select name="document_id" class="form-select">
                                <option value="">— Nenhum —</option>
                                <?php foreach ($documents as $doc): ?>
                                <option value="<?= (int)$doc['id'] ?>">
                                    <?= htmlspecialchars($doc['titulo'] ?: $doc['original_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nota Jurídica (Interna)</label>
                            <input type="text" name="legal_note" class="form-control" maxlength="500"
                                   placeholder="Anotação interna de uso restrito">
                            <div class="form-text"><i class="fas fa-lock me-1"></i>Não visível ao cliente</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="visible_client" id="visible_client_add" value="1">
                                <label class="form-check-label" for="visible_client_add">
                                    Visível no portal do cliente
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Prova
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
