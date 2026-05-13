<?php
/**
 * View: Jurisprudência Interna
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
            <h4 class="mb-1"><i class="fas fa-book-open me-2 text-primary"></i>Jurisprudência</h4>
            <p class="text-muted mb-0 small">Banco interno de jurisprudências e decisões de referência</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddJuris">
            <i class="fas fa-plus me-1"></i>Cadastrar Jurisprudência
        </button>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" action="/knowledge/jurisprudence" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Título, tribunal, tema..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
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
                    <a href="/knowledge/jurisprudence" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="fas fa-gavel me-2 text-primary"></i>
            <span>Jurisprudências Cadastradas</span>
            <span class="badge bg-secondary ms-2"><?= count($items) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-book-open fa-3x mb-3 opacity-25"></i>
                <p>Nenhuma jurisprudência cadastrada<?= $search || $area || $tag ? ' para este filtro' : '' ?>.</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddJuris">
                    <i class="fas fa-plus me-1"></i>Cadastrar primeira jurisprudência
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tribunal</th>
                            <th>Área</th>
                            <th>Tema</th>
                            <th>Decisão</th>
                            <th>Tags</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($item['summary'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($item['summary'], 0, 80), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($item['summary']) > 80 ? '…' : '' ?></small>
                                <?php endif; ?>
                                <?php if (!empty($item['link'])): ?>
                                <br><a href="<?= htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="small text-info"><i class="fas fa-external-link-alt me-1"></i>Fonte</a>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($item['court'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (!empty($item['area'])): ?>
                                <span class="badge bg-primary"><?= htmlspecialchars($item['area'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($item['theme'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small text-nowrap">
                                <?= !empty($item['decision_date']) ? date('d/m/Y', strtotime($item['decision_date'])) : '—' ?>
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
                            <td class="text-end text-nowrap">
                                <a href="/knowledge/jurisprudence/<?= (int)$item['id'] ?>/edit" class="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <form action="/knowledge/jurisprudence/<?= (int)$item['id'] ?>/delete" method="POST" class="d-inline"
                                      onsubmit="return confirm('Excluir esta jurisprudência?');">
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

<!-- Modal Cadastrar Jurisprudência -->
<div class="modal fade" id="modalAddJuris" tabindex="-1" aria-labelledby="modalAddJurisLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="/knowledge/jurisprudence/store" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddJurisLabel">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Cadastrar Jurisprudência
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="255"
                                   placeholder="Ex: STJ — Responsabilidade civil por dano moral digital">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tribunal</label>
                            <input type="text" name="court" class="form-control" maxlength="100"
                                   placeholder="STJ, STF, TJ-SP, TRF3...">
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
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Tema</label>
                            <input type="text" name="theme" class="form-control" maxlength="150"
                                   placeholder="Tema central da decisão">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Data da Decisão</label>
                            <input type="date" name="decision_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Resumo</label>
                            <textarea name="summary" class="form-control" rows="3"
                                      placeholder="Síntese da decisão e seu impacto estratégico..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Ementa</label>
                            <textarea name="ementa" class="form-control" rows="4"
                                      placeholder="Ementa completa da decisão..."></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Link / Fonte</label>
                            <input type="url" name="link" class="form-control"
                                   placeholder="https://...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tags</label>
                            <input type="text" name="tags" class="form-control" maxlength="500"
                                   placeholder="dano moral, responsabilidade, digital">
                            <div class="form-text">Separe por vírgula</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Cadastrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
