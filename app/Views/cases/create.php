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
                    <input type="text" class="form-control" name="classe" maxlength="150"
                           value="<?= htmlspecialchars($old['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                    <input type="text" class="form-control" name="fase_processual" maxlength="100"
                           value="<?= htmlspecialchars($old['fase_processual'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Clientes</label>
                    <select class="form-select" name="clients[]" multiple id="clientSelect">
                        <?php foreach ($clients ?? [] as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                            <?= !empty($c['cpf']) ? ' (CPF: ' . $c['cpf'] . ')' : (!empty($c['cnpj']) ? ' (CNPJ: ' . $c['cnpj'] . ')' : '') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Segure Ctrl para selecionar múltiplos.</div>
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
        </form>
    </div>
</div>
