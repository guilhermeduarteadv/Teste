<?php $old = $old ?? []; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Novo Processo</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/cases">Processos</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol></nav>
    </div>
    <a href="/cases" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/cases/store" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Número CNJ</label>
                    <input type="text" class="form-control" name="numero_cnj" placeholder="0000000-00.0000.0.00.0000"
                           value="<?= htmlspecialchars($old['numero_cnj'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Tribunal</label>
                    <select class="form-select" name="tribunal">
                        <option value="">Selecione...</option>
                        <?php foreach ($tribunais ?? [] as $t): ?>
                        <option value="<?= $t ?>" <?= ($old['tribunal'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['ativo' => 'Ativo', 'aguardando' => 'Aguardando', 'suspenso' => 'Suspenso', 'arquivado' => 'Arquivado', 'encerrado' => 'Encerrado'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['status'] ?? 'ativo') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch border rounded p-3 ps-5 bg-light">
                        <input class="form-check-input" type="checkbox" role="switch" id="segredo_justica" name="segredo_justica" value="1" <?= !empty($old['segredo_justica']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="segredo_justica">Segredo de justiça</label>
                        <div class="form-text mb-0">Quando marcado, este processo não será atualizado automaticamente pelo CNJ/DataJud/eproc.</div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Assunto/Objeto *</label>
                    <input type="text" class="form-control" name="assunto" required maxlength="255"
                           value="<?= htmlspecialchars($old['assunto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Ação de Indenização por Danos Morais">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Comarca</label>
                    <input type="text" class="form-control" name="comarca" maxlength="100"
                           value="<?= htmlspecialchars($old['comarca'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Vara</label>
                    <input type="text" class="form-control" name="vara" maxlength="100"
                           value="<?= htmlspecialchars($old['vara'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Classe Processual</label>
                    <select class="form-select" name="classe">
                        <option value="">Selecione...</option>
                        <?php foreach ($classesProcessuais ?? [] as $classeOpcao): ?>
                        <option value="<?= htmlspecialchars($classeOpcao, ENT_QUOTES, 'UTF-8') ?>" <?= ($old['classe'] ?? '') === $classeOpcao ? 'selected' : '' ?>><?= htmlspecialchars($classeOpcao, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Área do Processo</label>
                    <select class="form-select" name="area">
                        <option value="">Selecione...</option>
                        <?php foreach ($areasProcessuais ?? [] as $areaOpcao): ?>
                        <option value="<?= htmlspecialchars($areaOpcao, ENT_QUOTES, 'UTF-8') ?>" <?= ($old['area'] ?? '') === $areaOpcao ? 'selected' : '' ?>><?= htmlspecialchars($areaOpcao, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Valor da Causa</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="valor_causa" id="valor_causa"
                               placeholder="0,00" value="<?= htmlspecialchars($old['valor_causa'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Fase Processual</label>
                    <select class="form-select" name="fase_processual">
                        <option value="">Selecione...</option>
                        <?php foreach ($fasesProcessuais ?? [] as $faseOpcao): ?>
                        <option value="<?= htmlspecialchars($faseOpcao, ENT_QUOTES, 'UTF-8') ?>" <?= ($old['fase_processual'] ?? '') === $faseOpcao ? 'selected' : '' ?>><?= htmlspecialchars($faseOpcao, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Risco Processual</label>
                    <select class="form-select" name="risco_processual">
                        <option value="">Não definido</option>
                        <?php foreach (['baixo' => 'Baixo', 'medio' => 'Médio', 'alto' => 'Alto', 'critico' => 'Crítico'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['risco_processual'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Prob. de Êxito</label>
                    <select class="form-select" name="probabilidade_exito">
                        <option value="">Não definido</option>
                        <?php foreach (['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa', 'incerta' => 'Incerta'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['probabilidade_exito'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Responsável</label>
                    <select class="form-select" name="responsavel_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($users ?? [] as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ((int)($old['responsavel_id'] ?? 0)) === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <?php
                    $clientFieldName = 'clients[]';
                    $clientMultiple = true;
                    $selectedClients = [];
                    $roleField = true;
                    include ROOT_PATH . '/app/Views/partials/client_autocomplete.php';
                    ?>
                </div>

                <div class="col-12">
                    <hr>
                    <h5 class="fw-bold mb-0">Parte contrária</h5>
                    <p class="text-muted small mb-0">Preencha apenas os dados da parte adversa. Os dados do cliente vêm do cadastro do cliente vinculado.</p>
                </div>
                <div class="col-12 col-md-3">
                                    <label class="form-label">CPF/CNPJ</label>
                                    <input type="text" class="form-control" name="parte_contraria_cpf_cnpj" value="<?= htmlspecialchars($old['parte_contraria_cpf_cnpj'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">RG/IE</label>
                                    <input type="text" class="form-control" name="parte_contraria_rg_ie" value="<?= htmlspecialchars($old['parte_contraria_rg_ie'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Telefone</label>
                                    <input type="text" class="form-control" name="parte_contraria_telefone" value="<?= htmlspecialchars($old['parte_contraria_telefone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control" name="parte_contraria_email" value="<?= htmlspecialchars($old['parte_contraria_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label">Endereço</label>
                                    <input type="text" class="form-control" name="parte_contraria_endereco" value="<?= htmlspecialchars($old['parte_contraria_endereco'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Número</label>
                                    <input type="text" class="form-control" name="parte_contraria_numero" value="<?= htmlspecialchars($old['parte_contraria_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label">CEP</label>
                                    <input type="text" class="form-control" name="parte_contraria_cep" value="<?= htmlspecialchars($old['parte_contraria_cep'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">Complemento</label>
                                    <input type="text" class="form-control" name="parte_contraria_complemento" value="<?= htmlspecialchars($old['parte_contraria_complemento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Bairro</label>
                                    <input type="text" class="form-control" name="parte_contraria_bairro" value="<?= htmlspecialchars($old['parte_contraria_bairro'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Cidade</label>
                                    <input type="text" class="form-control" name="parte_contraria_cidade" value="<?= htmlspecialchars($old['parte_contraria_cidade'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label">UF</label>
                                    <input type="text" class="form-control" name="parte_contraria_estado" maxlength="2" value="<?= htmlspecialchars($old['parte_contraria_estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Advogado da parte contrária</label>
                                    <input type="text" class="form-control" name="parte_contraria_advogado" value="<?= htmlspecialchars($old['parte_contraria_advogado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">OAB do advogado</label>
                                    <input type="text" class="form-control" name="parte_contraria_advogado_oab" value="<?= htmlspecialchars($old['parte_contraria_advogado_oab'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observações</label>
                                    <textarea class="form-control" name="parte_contraria_observacoes" rows="2"><?= htmlspecialchars($old['parte_contraria_observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Próxima Providência</label>
                    <textarea class="form-control" name="proxima_providencia" rows="2"><?= htmlspecialchars($old['proxima_providencia'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Outras Informações</label>
                    <textarea class="form-control" name="outras_informacoes" rows="3"><?= htmlspecialchars($old['outras_informacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Processo</button>
                <a href="/cases" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        
                <div class="col-12 mt-4 border-top pt-3 text-end">
                    <a href="/cases" class="btn btn-light me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Salvar processo
                    </button>
                </div>

</form>
    </div>
</div>
