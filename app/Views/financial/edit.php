<?php use App\Helpers\FormatHelper; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Editar Lançamento</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/financial">Financeiro</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol></nav>
    </div>
    <a href="/financial" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/financial/<?= $entry['id'] ?>/update" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Tipo *</label>
                    <select class="form-select" name="tipo" required>
                        <?php foreach (['honorario' => 'Honorário', 'custas' => 'Custas Processuais', 'despesa' => 'Despesa', 'reembolso' => 'Reembolso', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($entry['tipo'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['pendente' => 'Pendente', 'parcial' => 'Parcial', 'pago' => 'Pago', 'vencido' => 'Vencido', 'cancelado' => 'Cancelado'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($entry['status'] ?? 'pendente') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Vincular como pagamento/parcela de uma cobrança existente</label>
                    <select class="form-select" name="parent_entry_id">
                        <option value="">Não vincular — este lançamento é uma cobrança independente</option>
                        <?php foreach ($openReceivables ?? [] as $rec): ?>
                        <?php
                            $saldo = (float)($rec['saldo_aberto'] ?? $rec['valor']);
                            $label = '#' . $rec['id'] . ' - ' . ($rec['client_name'] ?? 'Sem cliente') . ' - ' . ($rec['descricao'] ?? '') . ' - saldo ' . FormatHelper::money($saldo);
                        ?>
                        <option value="<?= $rec['id'] ?>" <?= (int)($entry['parent_entry_id'] ?? 0) === (int)$rec['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Quando vinculado, este lançamento será considerado pagamento/parcela da cobrança selecionada.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição *</label>
                    <input type="text" class="form-control" name="descricao" required maxlength="255"
                           value="<?= htmlspecialchars($entry['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Valor *</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control" name="valor" required
                               value="<?= number_format((float)($entry['valor'] ?? 0), 2, ',', '.') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Vencimento *</label>
                    <input type="date" class="form-control" name="vencimento" required
                           value="<?= htmlspecialchars($entry['vencimento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Data de Pagamento</label>
                    <input type="date" class="form-control" name="data_pagamento"
                           value="<?= htmlspecialchars($entry['data_pagamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Cliente</label>
                    <select class="form-select" name="client_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($clients ?? [] as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= (int)($entry['client_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
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
                        <option value="<?= $ca['id'] ?>" <?= (int)($entry['case_id'] ?? 0) === (int)$ca['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ca['numero_cnj'] ?: $ca['assunto'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Forma de Pagamento</label>
                    <select class="form-select" name="forma_pagamento">
                        <option value="">Não informado</option>
                        <?php foreach (['pix' => 'PIX', 'transferencia' => 'Transferência', 'boleto' => 'Boleto', 'dinheiro' => 'Dinheiro', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($entry['forma_pagamento'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Comprovante de Pagamento</label>
                    <input type="file" class="form-control" name="comprovante" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <div class="form-text">PDF, JPG, PNG ou WEBP, até 10 MB.</div>
                    <?php if (!empty($entry['recibo_path'])): ?>
                        <a class="btn btn-sm btn-outline-primary mt-2" target="_blank" href="/financial/<?= (int)$entry['id'] ?>/receipt">
                            <i class="fas fa-paperclip me-1"></i>Ver comprovante atual
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="2"><?= htmlspecialchars($entry['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="visivel_cliente" id="visivel_cliente"
                               <?= !empty($entry['visivel_cliente']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="visivel_cliente">Visível no portal do cliente</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                <a href="/financial" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($entry['parent_entry_id'])): ?>
<div class="card mt-3">
    <div class="card-header fw-semibold">Pagamentos vinculados a este lançamento</div>
    <div class="card-body">
        <?php $prog = $paymentProgress ?? ['valor_total'=>0,'total_pago'=>0,'saldo'=>0,'percentual'=>0]; ?>
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Valor total</div><div class="fw-bold"><?= FormatHelper::money((float)$prog['valor_total']) ?></div></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Pago vinculado</div><div class="fw-bold text-success"><?= FormatHelper::money((float)$prog['total_pago']) ?></div></div></div>
            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Saldo em aberto</div><div class="fw-bold text-danger"><?= FormatHelper::money((float)$prog['saldo']) ?></div></div></div>
        </div>
        <div class="progress mb-3" style="height: 10px;"><div class="progress-bar" role="progressbar" style="width: <?= (float)$prog['percentual'] ?>%"></div></div>
        <?php if (empty($linkedPayments)): ?>
            <p class="text-muted mb-0">Nenhum pagamento vinculado.</p>
        <?php else: ?>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Forma</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($linkedPayments as $pay): ?>
                <tr>
                    <td><?= htmlspecialchars($pay['data_pagamento'] ?: $pay['vencimento'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($pay['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= FormatHelper::money((float)$pay['valor']) ?></td>
                    <td><?= htmlspecialchars($pay['forma_pagamento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= FormatHelper::statusBadge($pay['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
