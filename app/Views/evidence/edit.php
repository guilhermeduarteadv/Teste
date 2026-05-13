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
?>
<div class="page-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-pencil-alt me-2 text-primary"></i>Editar Prova</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="/cases">Processos</a></li>
                    <?php if (!empty($case)): ?>
                    <li class="breadcrumb-item"><a href="/cases/<?= (int)$case['id'] ?>"><?= htmlspecialchars($case['numero_cnj'] ?: $case['assunto'], ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="breadcrumb-item"><a href="/cases/<?= (int)$case['id'] ?>/evidence">Provas</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <?php if (!empty($case)): ?>
        <a href="/cases/<?= (int)$case['id'] ?>/evidence" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-balance-scale me-2 text-primary"></i>Dados da Prova
        </div>
        <div class="card-body">
            <form action="/evidence/<?= (int)$evidence['id'] ?>/update" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="255"
                               value="<?= htmlspecialchars($evidence['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select name="evidence_type" class="form-select">
                            <?php foreach ($evidenceTypes as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $evidence['evidence_type'] === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Força Probatória</label>
                        <select name="probative_strength" class="form-select">
                            <?php foreach (['fraca' => 'Fraca', 'media' => 'Média', 'forte' => 'Forte', 'essencial' => 'Essencial'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $evidence['probative_strength'] === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Descrição</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Detalhamento da prova, contexto, relevância..."><?= htmlspecialchars($evidence['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <?php if (!empty($documents)): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Documento vinculado</label>
                        <select name="document_id" class="form-select">
                            <option value="">— Nenhum —</option>
                            <?php foreach ($documents as $doc): ?>
                            <option value="<?= (int)$doc['id'] ?>"
                                <?= ((int)($evidence['document_id'] ?? 0) === (int)$doc['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($doc['titulo'] ?: $doc['original_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nota Jurídica (Interna)</label>
                        <input type="text" name="legal_note" class="form-control" maxlength="500"
                               value="<?= htmlspecialchars($evidence['legal_note'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Anotação interna de uso restrito">
                        <div class="form-text"><i class="fas fa-lock me-1"></i>Não visível ao cliente</div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="visible_client" id="visible_client_edit" value="1"
                                <?= !empty($evidence['visible_client']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="visible_client_edit">
                                Visível no portal do cliente
                            </label>
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <?php if (!empty($case)): ?>
                        <a href="/cases/<?= (int)$case['id'] ?>/evidence" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
