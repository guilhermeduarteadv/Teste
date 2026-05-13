<?php
use App\Helpers\FormatHelper;
use App\Helpers\DateHelper;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/clients">Clientes</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/clients/<?= $client['id'] ?>/timeline" class="btn btn-sm btn-outline-info">
            <i class="fas fa-history me-1"></i>Timeline
        </a>
        <a href="/clients/<?= $client['id'] ?>/notes" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-sticky-note me-1"></i>Anotações
        </a>
        <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit me-1"></i>Editar
        </a>
        <a href="/clients/<?= $client['id'] ?>/portal-access" class="btn btn-sm btn-outline-<?= $client['portal_access'] ? 'warning' : 'success' ?>">
            <i class="fas fa-<?= $client['portal_access'] ? 'lock' : 'unlock' ?> me-1"></i>
            <?= $client['portal_access'] ? 'Revogar Portal' : 'Ativar Portal' ?>
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Client Info -->
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-header">Dados do Cliente</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small">Tipo</td>
                        <td><?= $client['tipo_pessoa'] === 'fisica' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></td>
                    </tr>
                    <?php if (!empty($client['cpf'])): ?>
                    <tr><td class="text-muted small">CPF</td><td><?= FormatHelper::cpf($client['cpf']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['cnpj'])): ?>
                    <tr><td class="text-muted small">CNPJ</td><td><?= FormatHelper::cnpj($client['cnpj']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['rg'])): ?>
                    <tr><td class="text-muted small">RG</td><td><?= htmlspecialchars($client['rg'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['data_nascimento'])): ?>
                    <tr><td class="text-muted small">Nascimento</td><td><?= DateHelper::formatBr($client['data_nascimento']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['estado_civil'])): ?>
                    <tr><td class="text-muted small">Est. Civil</td><td><?= ucfirst(str_replace('_', ' ', $client['estado_civil'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['profissao'])): ?>
                    <tr><td class="text-muted small">Profissão</td><td><?= htmlspecialchars($client['profissao'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="text-muted small">Status</td><td><?= FormatHelper::statusBadge($client['status']) ?></td></tr>
                    <tr><td class="text-muted small">Portal</td>
                        <td><?= $client['portal_access'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Contato</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <?php if (!empty($client['phone'])): ?>
                    <tr><td class="text-muted small">Telefone</td><td><?= FormatHelper::phone($client['phone']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['whatsapp'])): ?>
                    <tr><td class="text-muted small">WhatsApp</td><td><?= FormatHelper::phone($client['whatsapp']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($client['email'])): ?>
                    <tr><td class="text-muted small">E-mail</td><td><a href="mailto:<?= htmlspecialchars($client['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($client['email'], ENT_QUOTES, 'UTF-8') ?></a></td></tr>
                    <?php endif; ?>
                </table>
                <?php if (!empty($client['endereco'])): ?>
                <hr class="my-2">
                <p class="small mb-0 text-muted">
                    <?= htmlspecialchars($client['endereco'], ENT_QUOTES, 'UTF-8') ?>
                    <?= !empty($client['numero']) ? ', ' . $client['numero'] : '' ?>
                    <?= !empty($client['complemento']) ? ' - ' . $client['complemento'] : '' ?>
                    <br>
                    <?= !empty($client['bairro']) ? $client['bairro'] . ' - ' : '' ?>
                    <?= htmlspecialchars($client['cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    <?= !empty($client['estado']) ? '/' . $client['estado'] : '' ?>
                    <?= !empty($client['cep']) ? '<br>CEP: ' . FormatHelper::cep($client['cep']) : '' ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabs for cases, financial, documents -->
    <div class="col-12 col-md-8">
        <ul class="nav nav-tabs mb-3" id="clientTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#cases-tab">
                    <i class="fas fa-gavel me-1"></i>Processos (<?= count($cases ?? []) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#financial-tab">
                    <i class="fas fa-dollar-sign me-1"></i>Financeiro (<?= count($financial ?? []) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#docs-tab">
                    <i class="fas fa-folder me-1"></i>Documentos (<?= count($documents ?? []) ?>)
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <!-- Cases Tab -->
            <div class="tab-pane fade show active" id="cases-tab">
                <div class="d-flex justify-content-end mb-2">
                    <a href="/cases/create" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>Novo Processo
                    </a>
                </div>
                <?php if (empty($cases)): ?>
                <div class="text-center text-muted py-4"><i class="fas fa-gavel fa-2x mb-2 d-block"></i>Nenhum processo.</div>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($cases as $case): ?>
                    <a href="/cases/<?= $case['id'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($case['numero_cnj'] ?? 'Sem número', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted" style="font-size:0.78rem;"><?= htmlspecialchars($case['assunto'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted" style="font-size:0.72rem;">Polo: <?= htmlspecialchars($case['polo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <?= FormatHelper::statusBadge($case['status']) ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Financial Tab -->
            <div class="tab-pane fade" id="financial-tab">
                <div class="d-flex justify-content-end mb-2">
                    <a href="/financial/create?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i>Novo Lançamento
                    </a>
                </div>
                <?php if (empty($financial)): ?>
                <div class="text-center text-muted py-4"><i class="fas fa-dollar-sign fa-2x mb-2 d-block"></i>Nenhum lançamento.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>Descrição</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($financial as $fe): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars($fe['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small fw-semibold"><?= FormatHelper::money((float)$fe['valor']) ?></td>
                            <td class="small"><?= DateHelper::formatBr($fe['vencimento']) ?></td>
                            <td><?= FormatHelper::statusBadge($fe['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Documents Tab -->
            <div class="tab-pane fade" id="docs-tab">
                <div class="mb-2">
                    <form method="POST" action="/documents/upload" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="entity_type" value="client">
                        <input type="hidden" name="entity_id" value="<?= $client['id'] ?>">
                        <input type="file" class="form-control form-control-sm" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                        <input type="text" class="form-control form-control-sm" name="descricao" placeholder="Descrição" style="max-width:160px;">
                        <button type="submit" class="btn btn-sm btn-primary">Upload</button>
                    </form>
                </div>
                <?php if (empty($documents)): ?>
                <div class="text-center text-muted py-4"><i class="fas fa-folder fa-2x mb-2 d-block"></i>Nenhum documento.</div>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($documents as $doc): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <div>
                            <i class="fas fa-file-<?= in_array($doc['extension'], ['pdf']) ? 'pdf text-danger' : 'alt text-secondary' ?> me-2"></i>
                            <span class="small"><?= htmlspecialchars($doc['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="text-muted ms-2" style="font-size:0.72rem;"><?= \App\Helpers\FormatHelper::fileSize((int)$doc['size']) ?></span>
                        </div>
                        <a href="/documents/<?= $doc['id'] ?>/download" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
