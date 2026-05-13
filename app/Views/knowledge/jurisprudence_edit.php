<?php
/**
 * View: Editar Jurisprudência
 * @var array  $item
 * @var array  $areas
 * @var string $csrf_token
 */
$commonAreas = ['Civil','Trabalhista','Previdenciário','Tributário','Criminal','Administrativo','Ambiental','Empresarial','Constitucional','Consumidor'];
?>
<div class="page-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-pencil-alt me-2 text-primary"></i>Editar Jurisprudência</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="/knowledge/jurisprudence">Jurisprudência</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <a href="/knowledge/jurisprudence" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-book-open me-2 text-primary"></i>Dados da Jurisprudência
        </div>
        <div class="card-body">
            <form action="/knowledge/jurisprudence/<?= (int)$item['id'] ?>/update" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="255"
                               value="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tribunal</label>
                        <input type="text" name="court" class="form-control" maxlength="100"
                               value="<?= htmlspecialchars($item['court'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Área</label>
                        <select name="area" class="form-select">
                            <option value="">— Selecione —</option>
                            <?php foreach ($commonAreas as $a): ?>
                            <option value="<?= $a ?>" <?= ($item['area'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endforeach; ?>
                            <?php foreach ($areas as $a): ?>
                                <?php if (!in_array($a, $commonAreas)): ?>
                                <option value="<?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>" <?= ($item['area'] ?? '') === $a ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Tema</label>
                        <input type="text" name="theme" class="form-control" maxlength="150"
                               value="<?= htmlspecialchars($item['theme'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Data da Decisão</label>
                        <input type="date" name="decision_date" class="form-control"
                               value="<?= htmlspecialchars($item['decision_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Resumo</label>
                        <textarea name="summary" class="form-control" rows="3"><?= htmlspecialchars($item['summary'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Ementa</label>
                        <textarea name="ementa" class="form-control" rows="5"><?= htmlspecialchars($item['ementa'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Link / Fonte</label>
                        <input type="url" name="link" class="form-control"
                               value="<?= htmlspecialchars($item['link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tags</label>
                        <input type="text" name="tags" class="form-control" maxlength="500"
                               value="<?= htmlspecialchars($item['tags'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text">Separe por vírgula</div>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                        <a href="/knowledge/jurisprudence" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
