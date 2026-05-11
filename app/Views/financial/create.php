<?php $old = $old ?? []; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Novo Lançamento Financeiro</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/financial">Financeiro</a></li>
            <li class="breadcrumb-item active">Novo</li>
        </ol></nav>
    </div>
    <a href="/financial" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/financial/store" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Tipo *</label>
                    <select class="form-select" name="tipo" required>
                        <option value="">Selecione...</option>
                        <?php foreach (['honorario' => 'Honorário', 'custas' => 'Custas Processuais', 'despesa' => 'Despesa', 'reembolso' => 'Reembolso', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['tipo'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['pendente' => 'Pendente', 'pago' => 'Pago', 'cancelado' => 'Cancelado'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['status'] ?? 'pendente') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição *</label>
                    <input type="text" class="form-control" name="descricao" required maxlength="255"
                           value="<?= htmlspecialchars($old['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Valor *</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="valor" placeholder="0,00" required
                               value="<?= htmlspecialchars($old['valor'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Vencimento *</label>
                    <input type="date" class="form-control" name="vencimento" required
                           value="<?= htmlspecialchars($old['vencimento'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Parcelas</label>
                    <input type="number" class="form-control" name="parcela_total" min="1" max="60" placeholder="1"
                           value="<?= htmlspecialchars($old['parcela_total'] ?? '1', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-text">Número de parcelas mensais</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Cliente</label>
                    <select class="form-select" name="client_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($clients ?? [] as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= (int)($old['client_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Processo</label>
                    <select class="form-select" name="case_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($cases ?? [] as $ca): ?>
                        <option value="<?= $ca['id'] ?>" <?= (int)($old['case_id'] ?? 0) === (int)$ca['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ca['numero_cnj'] ?: $ca['assunto'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Data de Pagamento</label>
                    <input type="date" class="form-control" name="data_pagamento"
                           value="<?= htmlspecialchars($old['data_pagamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Forma de Pagamento</label>
                    <select class="form-select" name="forma_pagamento">
                        <option value="">Não informado</option>
                        <?php foreach (['pix' => 'PIX', 'transferencia' => 'Transferência', 'boleto' => 'Boleto', 'dinheiro' => 'Dinheiro', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($old['forma_pagamento'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="2"><?= htmlspecialchars($old['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="visivel_cliente" id="visivel_cliente"
                               <?= !empty($old['visivel_cliente']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="visivel_cliente">Visível no portal do cliente</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Lançamento</button>
                <a href="/financial" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
