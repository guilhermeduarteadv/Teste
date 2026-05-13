<?php
use App\Helpers\FormatHelper;
use App\Helpers\DateHelper;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($case['numero_cnj'] ?: $case['assunto'], ENT_QUOTES, 'UTF-8') ?></h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/cases">Processos</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol></nav>
    </div>
    <div class="d-flex gap-2">
        <?php if (!empty($case['numero_cnj']) && empty($case['segredo_justica'])): ?>
        <form method="POST" action="/cases/<?= $case['id'] ?>/sync" class="d-inline">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-sm btn-outline-info" title="Sincronizar CNJ">
                <i class="fas fa-sync me-1"></i>Sincronizar CNJ
            </button>
        </form>
        <?php elseif (!empty($case['segredo_justica'])): ?>
        <button type="button" class="btn btn-sm btn-outline-dark" disabled title="Sincronização bloqueada por segredo de justiça">
            <i class="fas fa-lock me-1"></i>Segredo de justiça
        </button>
        <?php endif; ?>
        <a href="/cases/<?= $case['id'] ?>/timeline" class="btn btn-sm btn-outline-primary"><i class="fas fa-stream me-1"></i>Linha do tempo</a>
        <a href="/cases/<?= $case['id'] ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit me-1"></i>Editar</a>
        <a href="/cases" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
    </div>
</div>

<div class="row g-3">
    <!-- Case Info -->
    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-info-circle me-2 text-primary"></i>Dados do Processo</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted small">Número CNJ</td><td class="small fw-semibold"><?= htmlspecialchars($case['numero_cnj'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Tribunal</td><td class="small"><?= htmlspecialchars($case['tribunal'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Comarca</td><td class="small"><?= htmlspecialchars($case['comarca'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Vara</td><td class="small"><?= htmlspecialchars($case['vara'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Classe</td><td class="small"><?= htmlspecialchars($case['classe'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Fase</td><td class="small"><?= htmlspecialchars($case['fase_processual'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Valor da Causa</td><td class="small fw-semibold"><?= FormatHelper::money((float)($case['valor_causa'] ?? 0)) ?></td></tr>
                    <tr><td class="text-muted small">Status</td><td><?= FormatHelper::statusBadge($case['status'] ?? 'ativo') ?></td></tr>
                    <tr><td class="text-muted small">Segredo de justiça</td><td class="small"><?= !empty($case['segredo_justica']) ? '<span class="badge bg-dark"><i class="fas fa-lock me-1"></i>Sim</span>' : 'Não' ?></td></tr>
                    <tr><td class="text-muted small">Risco</td><td class="small"><?= !empty($case['risco_processual']) ? ucfirst($case['risco_processual']) : '—' ?></td></tr>
                    <tr><td class="text-muted small">Êxito</td><td class="small"><?= !empty($case['probabilidade_exito']) ? ucfirst($case['probabilidade_exito']) : '—' ?></td></tr>
                    <tr><td class="text-muted small">Responsável</td><td class="small"><?= htmlspecialchars($case['responsavel_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Clients -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-users me-2 text-success"></i>Partes</div>
            <div class="card-body p-0">
                <?php if (empty($clients)): ?>
                <div class="text-center text-muted py-3 small">Nenhuma parte vinculada.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($clients as $cl): ?>
                    <a href="/clients/<?= $cl['id'] ?>" class="list-group-item list-group-item-action border-0 py-2 px-3">
                        <div class="small fw-semibold"><?= htmlspecialchars($cl['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted" style="font-size:0.72rem;"><?= ucfirst($cl['tipo'] ?? 'autor') ?></div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>


        <?php if (!empty($case['parte_contraria_nome']) || !empty($case['parte_contraria_cpf_cnpj']) || !empty($case['parte_contraria_advogado'])): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-user-shield me-2 text-danger"></i>Parte Contrária</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted small">Nome/Razão Social</td><td class="small"><?= htmlspecialchars($case['parte_contraria_nome'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Tipo</td><td class="small"><?= htmlspecialchars($case['parte_contraria_tipo_pessoa'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">CPF/CNPJ</td><td class="small"><?= htmlspecialchars($case['parte_contraria_cpf_cnpj'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">RG/IE</td><td class="small"><?= htmlspecialchars($case['parte_contraria_rg_ie'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Telefone</td><td class="small"><?= htmlspecialchars($case['parte_contraria_telefone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">E-mail</td><td class="small"><?= htmlspecialchars($case['parte_contraria_email'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Endereço</td><td class="small"><?= htmlspecialchars(trim(($case['parte_contraria_endereco'] ?? '') . ', ' . ($case['parte_contraria_numero'] ?? '') . ' ' . ($case['parte_contraria_complemento'] ?? '') . ' - ' . ($case['parte_contraria_bairro'] ?? '') . ' - ' . ($case['parte_contraria_cidade'] ?? '') . '/' . ($case['parte_contraria_estado'] ?? '') . ' CEP ' . ($case['parte_contraria_cep'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">Advogado</td><td class="small"><?= htmlspecialchars($case['parte_contraria_advogado'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted small">OAB</td><td class="small"><?= htmlspecialchars($case['parte_contraria_advogado_oab'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <?php if (!empty($case['parte_contraria_observacoes'])): ?><tr><td class="text-muted small">Obs.</td><td class="small"><?= nl2br(htmlspecialchars($case['parte_contraria_observacoes'], ENT_QUOTES, 'UTF-8')) ?></td></tr><?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($case['proxima_providencia'])): ?>
        <div class="card">
            <div class="card-header"><i class="fas fa-lightbulb me-2 text-warning"></i>Próxima Providência</div>
            <div class="card-body small"><?= nl2br(htmlspecialchars($case['proxima_providencia'], ENT_QUOTES, 'UTF-8')) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-8">
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-movements">Movimentações <span class="badge bg-secondary"><?= count($movements) ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-deadlines">Prazos <span class="badge bg-secondary"><?= count($deadlines) ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-hearings">Audiências <span class="badge bg-secondary"><?= count($hearings) ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-documents">Docs <span class="badge bg-secondary"><?= count($documents) ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-tasks">Tarefas <span class="badge bg-secondary"><?= count($tasks) ?></span></a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-financial">Financeiro <span class="badge bg-secondary"><?= count($financial) ?></span></a></li>
        </ul>
        <div class="tab-content">
            <!-- Movements -->
            <div class="tab-pane fade show active" id="tab-movements">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-history me-2"></i>Movimentações</span>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#movementModal">
                            <i class="fas fa-plus me-1"></i>Adicionar
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($movements)): ?>
                        <div class="text-center text-muted py-4 small">Nenhuma movimentação registrada.</div>
                        <?php else: ?>
                        <div class="list-group list-group-flush" style="max-height:400px;overflow-y:auto;">
                            <?php foreach ($movements as $mv): ?>
                            <div class="list-group-item border-0 py-2 px-3">
                                <div class="d-flex justify-content-between">
                                    <span class="small fw-semibold"><?= DateHelper::formatBrDateTime($mv['data_movimento']) ?></span>
                                    <span class="badge bg-light text-dark"><?= htmlspecialchars($mv['tipo'] ?: 'despacho', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="small mt-1"><?= nl2br(htmlspecialchars($mv['descricao'], ENT_QUOTES, 'UTF-8')) ?></div>
                                <?php if ($mv['fonte'] === 'manual'): ?>
                                <div class="text-muted" style="font-size:0.72rem;"><i class="fas fa-user me-1"></i>Manual</div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Deadlines -->
            <div class="tab-pane fade" id="tab-deadlines">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock me-2"></i>Prazos</span>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#deadlineModal">
                            <i class="fas fa-plus me-1"></i>Adicionar
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($deadlines)): ?>
                        <div class="text-center text-muted py-4 small">Nenhum prazo cadastrado.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Tipo</th><th>Descrição</th><th>Data Final</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($deadlines as $dl): ?>
                                    <?php $daysLeft = DateHelper::daysUntil($dl['data_final']); ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= ucfirst($dl['tipo']) ?></span></td>
                                        <td class="small"><?= htmlspecialchars($dl['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small <?= $daysLeft < 0 ? 'text-danger fw-bold' : ($daysLeft <= 3 ? 'text-warning fw-bold' : '') ?>">
                                            <?= DateHelper::formatBr($dl['data_final']) ?>
                                            <?php if ($daysLeft >= 0): ?><span class="text-muted">(<?= $daysLeft ?>d)</span><?php endif; ?>
                                        </td>
                                        <td><?= FormatHelper::statusBadge($dl['status'] ?? 'pendente') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Hearings -->
            <div class="tab-pane fade" id="tab-hearings">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-gavel me-2"></i>Audiências</span>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#hearingModal">
                            <i class="fas fa-plus me-1"></i>Adicionar
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($hearings)): ?>
                        <div class="text-center text-muted py-4 small">Nenhuma audiência cadastrada.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Tipo</th><th>Título</th><th>Data/Hora</th><th>Local</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($hearings as $h): ?>
                                    <tr>
                                        <td><span class="badge bg-info text-dark"><?= ucfirst($h['tipo']) ?></span></td>
                                        <td class="small"><?= htmlspecialchars($h['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small"><?= DateHelper::formatBr($h['data']) ?> <?= substr($h['hora'], 0, 5) ?></td>
                                        <td class="small"><?= htmlspecialchars($h['local'] ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= FormatHelper::statusBadge($h['status'] ?? 'agendado') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="tab-pane fade" id="tab-documents">
                <div class="card">
                    <div class="card-header"><i class="fas fa-folder-open me-2"></i>Documentos</div>
                    <div class="card-body p-0">
                        <?php if (empty($documents)): ?>
                        <div class="text-center text-muted py-4 small">Nenhum documento anexado.</div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($documents as $doc): ?>
                            <div class="list-group-item border-0 d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <i class="fas fa-file-<?= in_array($doc['extension'], ['pdf']) ? 'pdf text-danger' : (in_array($doc['extension'], ['jpg','jpeg','png']) ? 'image text-info' : 'alt text-secondary') ?> me-2"></i>
                                    <span class="small"><?= htmlspecialchars($doc['original_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted ms-2" style="font-size:0.72rem;"><?= FormatHelper::fileSize($doc['size'] ?? 0) ?></span>
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

            <!-- Tasks -->
            <div class="tab-pane fade" id="tab-tasks">
                <div class="card">
                    <div class="card-header"><i class="fas fa-tasks me-2"></i>Tarefas</div>
                    <div class="card-body p-0">
                        <?php if (empty($tasks)): ?>
                        <div class="text-center text-muted py-4 small">Nenhuma tarefa vinculada.</div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($tasks as $t): ?>
                            <div class="list-group-item border-0 py-2 px-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="small fw-semibold"><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($t['responsavel_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                                    </div>
                                    <div class="text-end">
                                        <?= FormatHelper::priorityBadge($t['prioridade']) ?>
                                        <div class="small text-muted"><?= DateHelper::formatBr($t['prazo'] ?? '') ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Financial -->
            <div class="tab-pane fade" id="tab-financial">
                <div class="card">
                    <div class="card-header"><i class="fas fa-dollar-sign me-2"></i>Financeiro</div>
                    <div class="card-body p-0">
                        <?php if (empty($financial)): ?>
                        <div class="text-center text-muted py-4 small">Nenhum lançamento vinculado.</div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($financial as $fe): ?>
                                    <tr>
                                        <td class="small"><?= htmlspecialchars($fe['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge bg-secondary"><?= ucfirst($fe['tipo']) ?></span></td>
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
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row g-4 mt-1">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><span><i class="fas fa-address-book me-2"></i>Contatos do Processo</span><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#contactModal">Adicionar</button></div>
            <div class="card-body p-0">
                <?php if (empty($caseContacts ?? [])): ?><div class="text-center text-muted py-3 small">Nenhum contato adicional cadastrado.</div><?php else: ?>
                <table class="table table-sm mb-0"><thead><tr><th>Nome</th><th>Tipo</th><th>Contato</th></tr></thead><tbody><?php foreach($caseContacts as $ct): ?><tr><td><?= htmlspecialchars($ct['nome']) ?></td><td><?= htmlspecialchars($ct['tipo']) ?></td><td class="small"><?= htmlspecialchars(trim(($ct['telefone']??'').' '.($ct['email']??''))) ?></td></tr><?php endforeach; ?></tbody></table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center"><span><i class="fas fa-user-friends me-2"></i>Testemunhas</span><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#witnessModal">Adicionar</button></div>
            <div class="card-body p-0">
                <?php if (empty($caseWitnesses ?? [])): ?><div class="text-center text-muted py-3 small">Nenhuma testemunha cadastrada.</div><?php else: ?>
                <table class="table table-sm mb-0"><thead><tr><th>Nome</th><th>Status</th><th>Resumo</th></tr></thead><tbody><?php foreach($caseWitnesses as $wt): ?><tr><td><?= htmlspecialchars($wt['nome']) ?></td><td><span class="badge bg-secondary"><?= htmlspecialchars($wt['status']) ?></span></td><td class="small"><?= htmlspecialchars(mb_substr($wt['resumo_depoimento'] ?? '',0,80)) ?></td></tr><?php endforeach; ?></tbody></table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Movement Modal -->
<div class="modal fade" id="movementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Adicionar Movimentação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="/cases/<?= $case['id'] ?>/movements/add">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Data/Hora</label>
                        <input type="datetime-local" class="form-control" name="data_movimento" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <input type="text" class="form-control" name="tipo" placeholder="despacho, decisão, petição...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição *</label>
                        <textarea class="form-control" name="descricao" rows="4" required></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="visivel_cliente" id="movVisible">
                        <label class="form-check-label" for="movVisible">Visível no portal do cliente</label>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Add Deadline Modal -->
<div class="modal fade" id="deadlineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Adicionar Prazo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="/cases/<?= $case['id'] ?>/deadlines/add">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo">
                                <option value="processual">Processual</option>
                                <option value="fatal">Fatal</option>
                                <option value="interno">Interno</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data Início</label>
                            <input type="date" class="form-control" name="data_inicio" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Prazo (dias)</label>
                            <input type="number" class="form-control" name="prazo_dias" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data Final</label>
                            <input type="date" class="form-control" name="data_final">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição *</label>
                            <input type="text" class="form-control" name="descricao" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Add Hearing Modal -->
<div class="modal fade" id="hearingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Adicionar Audiência</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="/cases/<?= $case['id'] ?>/hearings/add">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo">
                                <option value="conciliacao">Conciliação</option>
                                <option value="instrucao">Instrução</option>
                                <option value="julgamento">Julgamento</option>
                                <option value="reuniao_cliente">Reunião c/ Cliente</option>
                                <option value="diligencia">Diligência</option>
                                <option value="compromisso">Compromisso</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data *</label>
                            <input type="date" class="form-control" name="data" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hora *</label>
                            <input type="time" class="form-control" name="hora" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Local</label>
                            <input type="text" class="form-control" name="local">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="contactModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="/cases/<?= $case['id'] ?>/contacts/add"><input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><div class="modal-header"><h5>Adicionar Contato</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-2"><div class="col-8"><label class="form-label">Nome</label><input name="nome" class="form-control" required></div><div class="col-4"><label class="form-label">Tipo</label><input name="tipo" class="form-control" placeholder="perito, contador..."></div><div class="col-6"><label class="form-label">Telefone</label><input name="telefone" class="form-control"></div><div class="col-6"><label class="form-label">E-mail</label><input name="email" class="form-control"></div><div class="col-12"><label class="form-label">Documento</label><input name="documento" class="form-control"></div><div class="col-12"><label class="form-label">Endereço</label><textarea name="endereco" class="form-control" rows="2"></textarea></div><div class="col-12"><label class="form-label">Observações</label><textarea name="observacoes" class="form-control" rows="3"></textarea></div></div></div><div class="modal-footer"><button class="btn btn-primary">Salvar</button></div></form></div></div>

<div class="modal fade" id="witnessModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="/cases/<?= $case['id'] ?>/witnesses/add"><input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><div class="modal-header"><h5>Adicionar Testemunha</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-2"><div class="col-8"><label class="form-label">Nome</label><input name="nome" class="form-control" required></div><div class="col-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="a_arrolar">A arrolar</option><option value="arrolada">Arrolada</option><option value="intimada">Intimada</option><option value="ouvida">Ouvida</option><option value="dispensada">Dispensada</option></select></div><div class="col-6"><label class="form-label">Telefone</label><input name="telefone" class="form-control"></div><div class="col-6"><label class="form-label">E-mail</label><input name="email" class="form-control"></div><div class="col-12"><label class="form-label">Documento</label><input name="documento" class="form-control"></div><div class="col-12"><label class="form-label">Endereço</label><textarea name="endereco" class="form-control" rows="2"></textarea></div><div class="col-12"><label class="form-label">Resumo do depoimento esperado</label><textarea name="resumo_depoimento" class="form-control" rows="3"></textarea></div></div></div><div class="modal-footer"><button class="btn btn-primary">Salvar</button></div></form></div></div>
