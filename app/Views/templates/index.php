<?php
/** @var array $templates */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Modelos de Peças e Documentos</h4>
        <div class="text-muted small">Cadastre minutas, checklists e textos-base vinculáveis por área.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="/generated-documents" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-file-medical me-1"></i>Docs Gerados
        </a>
        <a href="/templates/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Novo Modelo
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Área</th>
                        <th>Tipo</th>
                        <th>Ativo</th>
                        <th>Prévia</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
                            Nenhum modelo cadastrado.
                            <br><a href="/templates/create" class="btn btn-primary btn-sm mt-2">Criar Primeiro Modelo</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($templates as $t): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($t['area'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $tipoLabels = ['procuracao' => 'Procuração', 'contrato' => 'Contrato', 'recibo' => 'Recibo', 'declaracao' => 'Declaração', 'relatorio' => 'Relatório', 'outro' => 'Outro'];
                            $tipoKey = $t['tipo'] ?? 'outro';
                            ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($tipoLabels[$tipoKey] ?? ucfirst($tipoKey), ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?php if (!isset($t['active']) || $t['active']): ?>
                            <span class="badge text-bg-success">Ativo</span>
                            <?php else: ?>
                            <span class="badge text-bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted" style="max-width:250px;">
                            <?= htmlspecialchars(mb_substr(strip_tags($t['conteudo'] ?? ''), 0, 100), ENT_QUOTES, 'UTF-8') ?>...
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="/templates/<?= $t['id'] ?>/generate-form" class="btn btn-sm btn-success" title="Gerar Documento">
                                    <i class="fas fa-magic"></i>
                                </a>
                                <a href="/templates/<?= $t['id'] ?>/edit" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="/templates/<?= $t['id'] ?>/delete" onsubmit="return confirm('Excluir modelo?')" style="display:inline">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
