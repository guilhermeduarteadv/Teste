<?php
/**
 * View: Prazos Processuais
 * @var array  $deadlines
 * @var array  $cases
 * @var string $filter
 * @var int    $caseId
 * @var string $csrf_token
 */
$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$week     = date('Y-m-d', strtotime('+7 days'));

function deadlineBadge(string $dataFinal, int $confirmado): string
{
    if ($confirmado) {
        return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Conferido</span>';
    }
    if (!$dataFinal) {
        return '<span class="badge bg-secondary">Sem data</span>';
    }
    global $today, $tomorrow;
    if ($dataFinal < $today) {
        return '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i>Vencido</span><br><span class="badge bg-warning text-dark ms-1"><i class="fas fa-eye me-1"></i>Não conferido</span>';
    }
    if ($dataFinal <= $tomorrow) {
        return '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Urgente</span><br><span class="badge bg-warning text-dark ms-1 mt-1"><i class="fas fa-eye me-1"></i>Não conferido</span>';
    }
    return '<span class="badge bg-warning text-dark"><i class="fas fa-eye me-1"></i>Não conferido</span>';
}
?>
<div class="page-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-hourglass-half me-2 text-primary"></i>Prazos Processuais</h4>
            <p class="text-muted mb-0 small">Gerenciamento de prazos com cálculo automático de dias úteis</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddDeadline">
            <i class="fas fa-plus me-1"></i>Novo Prazo
        </button>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" action="/deadlines" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="text" name="filter" class="form-control form-control-sm"
                           placeholder="Processo, tipo de prazo..." value="<?= htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Processo</label>
                    <select name="case_id" class="form-select form-select-sm">
                        <option value="">— Todos —</option>
                        <?php foreach ($cases as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $caseId === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['numero_cnj'] ?: $c['assunto'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="/deadlines" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Calculadora rápida -->
    <div class="card mb-4 border-info">
        <div class="card-header bg-info bg-opacity-10 d-flex align-items-center gap-2">
            <i class="fas fa-calculator text-info"></i>
            <span class="fw-semibold">Calculadora de Prazo</span>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end" id="deadlineCalcForm">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Data inicial</label>
                    <input type="date" id="calc_start" class="form-control form-control-sm" value="<?= $today ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Dias</label>
                    <input type="number" id="calc_days" class="form-control form-control-sm" min="1" value="15">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">UF</label>
                    <input type="text" id="calc_state" class="form-control form-control-sm" maxlength="2" placeholder="SP">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="calc_business" checked>
                        <label class="form-check-label small" for="calc_business">Dias úteis</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-info btn-sm w-100" onclick="calcularPrazo()">
                        <i class="fas fa-calculator me-1"></i>Calcular
                    </button>
                </div>
                <div class="col-12" id="calc_result" style="display:none">
                    <div class="alert alert-info mb-0 py-2 small">
                        <strong>Data final:</strong> <span id="calc_end_date"></span>
                        <span id="calc_holidays_info" class="text-muted ms-2"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de prazos -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="fas fa-list me-2 text-primary"></i>
            <span>Lista de Prazos</span>
            <span class="badge bg-secondary ms-2"><?= count($deadlines) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($deadlines)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-hourglass-half fa-3x mb-3 opacity-25"></i>
                <p>Nenhum prazo cadastrado<?= $filter ? ' para este filtro' : '' ?>.</p>
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddDeadline">
                    <i class="fas fa-plus me-1"></i>Cadastrar primeiro prazo
                </button>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Processo</th>
                            <th>Tipo / Título</th>
                            <th>Data Final</th>
                            <th>Dias</th>
                            <th>Status</th>
                            <th>Conferido por</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($deadlines as $dl): ?>
                        <?php
                        $dataFinal  = $dl['data_final'] ?: ($dl['prazo'] ?? '');
                        $confirmado = (int)($dl['confirmado'] ?? 0);
                        $isVencido  = $dataFinal && $dataFinal < $today && !$confirmado;
                        ?>
                        <tr class="<?= $isVencido ? 'table-danger' : '' ?>">
                            <td class="small">
                                <?php if (!empty($dl['numero_cnj'])): ?>
                                <a href="/cases/<?= (int)$dl['case_id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($dl['numero_cnj'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <?php elseif (!empty($dl['case_assunto'])): ?>
                                <a href="/cases/<?= (int)$dl['case_id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars(mb_substr($dl['case_assunto'], 0, 40), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">#<?= (int)$dl['case_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($dl['title'] ?: ($dl['tipo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($dl['observacoes'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($dl['observacoes'], 0, 60), ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <?= $dataFinal ? '<strong>' . date('d/m/Y', strtotime($dataFinal)) . '</strong>' : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="small text-center">
                                <?php if (!empty($dl['prazo_dias'])): ?>
                                <span class="badge bg-light text-dark border">
                                    <?= (int)$dl['prazo_dias'] ?>d
                                    <?= $dl['business_days'] ? '<abbr title="dias úteis">ú</abbr>' : '<abbr title="dias corridos">c</abbr>' ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= deadlineBadge((string)$dataFinal, $confirmado) ?>
                            </td>
                            <td class="small">
                                <?= htmlspecialchars($dl['confirmed_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($dl['checked_at'])): ?>
                                <br><span class="text-muted"><?= date('d/m/Y', strtotime($dl['checked_at'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if (!$confirmado): ?>
                                <form action="/deadlines/<?= (int)$dl['id'] ?>/confirm" method="POST" class="d-inline"
                                      onsubmit="return confirm('Confirmar conferência deste prazo?');">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" title="Marcar como conferido">
                                        <i class="fas fa-check"></i> Conferir
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-double me-1"></i>OK
                                </span>
                                <?php endif; ?>
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

<!-- Modal Novo Prazo -->
<div class="modal fade" id="modalAddDeadline" tabindex="-1" aria-labelledby="modalAddDeadlineLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="/deadlines/store" method="POST" id="formNewDeadline">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddDeadlineLabel">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Novo Prazo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Processo <span class="text-danger">*</span></label>
                            <select name="case_id" class="form-select" required>
                                <option value="">— Selecione —</option>
                                <?php foreach ($cases as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $caseId === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['numero_cnj'] ?: $c['assunto'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Método de Cálculo</label>
                            <select name="calculation_method" class="form-select">
                                <option value="uteis">Dias Úteis</option>
                                <option value="corridos">Dias Corridos</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Título / Tipo de Prazo <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="255"
                                   placeholder="Ex: Contestação, Recurso de Apelação, Embargo de Declaração...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Data de Início</label>
                            <input type="date" name="start_count_at" class="form-control" id="new_start_date"
                                   value="<?= $today ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Qtd. Dias</label>
                            <input type="number" name="prazo_dias" class="form-control" min="1" id="new_days">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">UF</label>
                            <input type="text" name="state" class="form-control" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="business_days" id="new_business" value="1" checked>
                                <label class="form-check-label" for="new_business">Úteis</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Data Final</label>
                            <input type="date" name="data_final" class="form-control" id="new_data_final">
                            <div class="form-text">Ou informe dias acima</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="2"
                                      placeholder="Detalhes adicionais sobre o prazo..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Prazo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcularPrazo() {
    var startDate   = document.getElementById('calc_start').value;
    var days        = document.getElementById('calc_days').value;
    var state       = document.getElementById('calc_state').value;
    var businessEl  = document.getElementById('calc_business');
    var businessDays = businessEl && businessEl.checked ? '1' : '0';

    if (!startDate || !days) {
        alert('Preencha a data inicial e os dias.');
        return;
    }

    var formData = new FormData();
    formData.append('start_date', startDate);
    formData.append('days', days);
    formData.append('state', state);
    formData.append('business_days', businessDays);

    var token = document.querySelector('input[name="_csrf_token"]');
    if (token) formData.append('_csrf_token', token.value);

    fetch('/deadlines/calculate', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var result = document.getElementById('calc_result');
        var endDate = document.getElementById('calc_end_date');
        var holidaysInfo = document.getElementById('calc_holidays_info');

        if (data.end_date) {
            endDate.textContent = data.end_date_br || data.end_date;
            if (data.holidays_count > 0) {
                holidaysInfo.textContent = '(' + data.holidays_count + ' feriado(s) pulados)';
            } else {
                holidaysInfo.textContent = '';
            }
            result.style.display = '';
        } else {
            endDate.textContent = data.error || 'Erro ao calcular.';
            result.style.display = '';
        }
    })
    .catch(function() {
        alert('Erro ao comunicar com o servidor.');
    });
}
</script>
