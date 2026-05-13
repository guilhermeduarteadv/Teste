<?php
/**
 * View: Banco de Teses Jurídicas
 * @var array  $items
 * @var array  $areas
 * @var string $area
 * @var string $tag
 * @var string $search
 * @var string $csrf_token
 */
$commonAreas = ['Civil','Trabalhista','Previdenciário','Tributário','Criminal','Administrativo','Ambiental','Empresarial','Constitucional','Consumidor'];
?>
<div class="page-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-scroll me-2 text-primary"></i>Banco de Teses</h4>
            <p class="text-muted mb-0 small">Teses jurídicas reutilizáveis para fundamentar petições e argumentos</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddThesis">
            <i class="fas fa-plus me-1"></i>Nova Tese
        </button>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" action="/knowledge/theses" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Título, texto, fundamento..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Área</label>
                    <select name="area" class="form-select form-select-sm">
                        <option value="">— Todas —</option>
                        <?php foreach ($commonAreas as $a): ?>
                        <option value="<?= $a ?>" <?= $area === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                        <?php foreach ($areas as $a): ?>
                            <?php if (!in_array($a, $commonAreas)): ?>
                            <option value="<?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>" <?= $area === $a ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Tag</label>
                    <input type="text" name="tag" class="form-control form-control-sm"
                           placeholder="tag..." value="<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="/knowledge/theses" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="fas fa-scroll me-2 text-primary"></i>
            <span>Teses Cadastradas</span>
            <span class="badge bg-secondary ms-2"><?= count($items) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-scroll fa-3x mb-3 opacity-25"></i>
                <p>Nenhuma tese cadastrada<?= $search || $area || $tag ? ' para este filtro' : '' ?>.</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddThesis">
                    <i class="fas fa-plus me-1"></i>Cadastrar primeira tese
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Fundamento Legal</th>
                            <th>Tags</th>
                            <th>Cadastrado por</th>
                            <th>Data</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($item['thesis_text'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($item['thesis_text'], 0, 100), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($item['thesis_text']) > 100 ? '…' : '' ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['area'])): ?>
                                <span class="badge bg-primary"><?= htmlspecialchars($item['area'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= !empty($item['legal_basis']) ? htmlspecialchars(mb_substr($item['legal_basis'], 0, 80), ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="small">
                                <?php if (!empty($item['tags'])): ?>
                                    <?php foreach (array_filter(array_map('trim', explode(',', $item['tags']))) as $t): ?>
                                    <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($item['created_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small text-nowrap">
                                <?= !empty($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : '—' ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="/knowledge/theses/<?= (int)$item['id'] ?>/edit" class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <form action="/knowledge/theses/<?= (int)$item['id'] ?>/delete" method="POST" class="d-inline"
                                      onsubmit="return confirm('Excluir esta tese?');">
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

<!-- Modal Nova Tese -->
<div class="modal fade" id="modalAddThesis" tabindex="-1" aria-labelledby="modalAddThesisLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="/knowledge/theses/store" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddThesisLabel">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Nova Tese Jurídica
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="255"
                                   placeholder="Identifique a tese de forma clara e objetiva">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Área</label>
                            <select name="area" class="form-select">
                                <option value="">— Selecione —</option>
                                <?php foreach ($commonAreas as $a): ?>
                                <option value="<?= $a ?>"><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Texto da Tese</label>
                            <textarea name="thesis_text" class="form-control" rows="6"
                                      placeholder="Redija o texto completo da tese jurídica, com argumentação desenvolvida..."></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Fundamento Legal</label>
                            <input type="text" name="legal_basis" class="form-control" maxlength="500"
                                   placeholder="Ex: Art. 927 CC, Súmula 37 STJ, REsp 1.234.567...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tags</label>
                            <input type="text" name="tags" class="form-control" maxlength="500"
                                   placeholder="responsabilidade, dano moral">
                            <div class="form-text">Separe por vírgula</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Cadastrar Tese
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
