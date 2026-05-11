<?php $c = $case; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Editar Processo</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/cases">Processos</a></li>
            <li class="breadcrumb-item"><a href="/cases/<?= $c['id'] ?>"><?= htmlspecialchars($c['numero_cnj'] ?: $c['assunto'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol></nav>
    </div>
    <a href="/cases/<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/cases/<?= $c['id'] ?>/update" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Número CNJ</label>
                    <input type="text" class="form-control" name="numero_cnj" placeholder="0000000-00.0000.0.00.0000"
                           value="<?= htmlspecialchars($c['numero_cnj'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Tribunal</label>
                    <select class="form-select" name="tribunal">
                        <option value="">Selecione...</option>
                        <?php foreach ($tribunais ?? [] as $t): ?>
                        <option value="<?= $t ?>" <?= ($c['tribunal'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['ativo' => 'Ativo', 'aguardando' => 'Aguardando', 'suspenso' => 'Suspenso', 'arquivado' => 'Arquivado', 'encerrado' => 'Encerrado'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($c['status'] ?? 'ativo') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Assunto/Objeto *</label>
                    <input type="text" class="form-control" name="assunto" required maxlength="255"
                           value="<?= htmlspecialchars($c['assunto'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Comarca</label>
                    <input type="text" class="form-control" name="comarca" maxlength="100"
                           value="<?= htmlspecialchars($c['comarca'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Vara</label>
                    <input type="text" class="form-control" name="vara" maxlength="100"
                           value="<?= htmlspecialchars($c['vara'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Classe Processual</label>
                    <input type="text" class="form-control" name="classe" maxlength="150"
                           value="<?= htmlspecialchars($c['classe'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Valor da Causa</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="valor_causa"
                               value="<?= number_format((float)($c['valor_causa'] ?? 0), 2, ',', '.') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Fase Processual</label>
                    <input type="text" class="form-control" name="fase_processual" maxlength="100"
                           value="<?= htmlspecialchars($c['fase_processual'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Risco Processual</label>
                    <select class="form-select" name="risco_processual">
                        <option value="">Não definido</option>
                        <?php foreach (['baixo' => 'Baixo', 'medio' => 'Médio', 'alto' => 'Alto', 'critico' => 'Crítico'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($c['risco_processual'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Prob. de Êxito</label>
                    <select class="form-select" name="probabilidade_exito">
                        <option value="">Não definido</option>
                        <?php foreach (['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa', 'incerta' => 'Incerta'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($c['probabilidade_exito'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Responsável</label>
                    <select class="form-select" name="responsavel_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($users ?? [] as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)($c['responsavel_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Clientes (Partes)</label>
                    <select class="form-select" name="clients[]" multiple>
                        <?php
                        $caseClientIds = array_column($caseClients ?? [], 'id');
                        foreach ($clients ?? [] as $cl):
                        ?>
                        <option value="<?= $cl['id'] ?>" <?= in_array($cl['id'], $caseClientIds) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Segure Ctrl para selecionar múltiplos.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Próxima Providência</label>
                    <textarea class="form-control" name="proxima_providencia" rows="2"><?= htmlspecialchars($c['proxima_providencia'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Outras Informações</label>
                    <textarea class="form-control" name="outras_informacoes" rows="3"><?= htmlspecialchars($c['outras_informacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                <a href="/cases/<?= $c['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
