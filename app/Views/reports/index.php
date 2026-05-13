<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Relatórios</h4>
        <p class="text-muted small mb-0">Análises e relatórios do escritório</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-3">
        <a href="/reports/cases" class="text-decoration-none">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="fas fa-gavel fa-3x text-primary"></i></div>
                <h5 class="fw-bold">Processos</h5>
                <p class="text-muted small mb-0">Relatório de processos por período, status e tribunal</p>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <a href="/reports/financial" class="text-decoration-none">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="fas fa-dollar-sign fa-3x text-success"></i></div>
                <h5 class="fw-bold">Financeiro</h5>
                <p class="text-muted small mb-0">Receitas, despesas e lançamentos por período</p>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <a href="/reports/clients" class="text-decoration-none">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="fas fa-users fa-3x text-info"></i></div>
                <h5 class="fw-bold">Clientes</h5>
                <p class="text-muted small mb-0">Carteira de clientes com processos e financeiro</p>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <a href="/reports/tasks" class="text-decoration-none">
            <div class="card h-100 text-center p-4">
                <div class="mb-3"><i class="fas fa-tasks fa-3x text-warning"></i></div>
                <h5 class="fw-bold">Tarefas</h5>
                <p class="text-muted small mb-0">Produtividade e status das tarefas da equipe</p>
            </div>
        </a>
    </div>
</div>

<!-- Relatórios de Processo (HTML imprimível) -->
<div class="card mt-4">
    <div class="card-header fw-semibold"><i class="fas fa-print me-2 text-primary"></i>Relatórios de Processo (HTML Imprimível)</div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Gere relatórios HTML imprimíveis para um processo específico. Use o número do processo ou pesquise abaixo.
        </p>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-primary h-100">
                    <div class="card-body">
                        <h6 class="fw-bold text-primary"><i class="fas fa-user me-2"></i>Relatório para o Cliente</h6>
                        <p class="small text-muted mb-3">Movimentações e audiências visíveis, prazos visíveis. Sem informações internas.</p>
                        <form method="post" action="" id="formClientReport" target="_blank">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="input-group input-group-sm">
                                <input type="number" name="case_id_client" id="caseIdClient" class="form-control" placeholder="ID do processo" required min="1">
                                <button type="button" class="btn btn-primary btn-sm" onclick="openCaseReport('client')">
                                    <i class="fas fa-file-alt me-1"></i>Gerar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-secondary h-100">
                    <div class="card-body">
                        <h6 class="fw-bold text-secondary"><i class="fas fa-lock me-2"></i>Relatório Interno</h6>
                        <p class="small text-muted mb-3">Versão completa com estratégia, financeiro e riscos. Uso interno apenas.</p>
                        <form method="post" action="" id="formInternalReport" target="_blank">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="input-group input-group-sm">
                                <input type="number" name="case_id_internal" id="caseIdInternal" class="form-control" placeholder="ID do processo" required min="1">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openCaseReport('internal')">
                                    <i class="fas fa-file-alt me-1"></i>Gerar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios Financeiros (HTML imprimível) -->
<div class="card mt-4">
    <div class="card-header fw-semibold"><i class="fas fa-file-invoice-dollar me-2 text-success"></i>Relatórios Financeiros (HTML Imprimível)</div>
    <div class="card-body">
        <form method="post" action="/reports/financial/generate" id="formFinancialReport" target="_blank">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Data inicial</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Data final</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pago">Pago</option>
                        <option value="pendente">Pendente</option>
                        <option value="vencido">Vencido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="honorario">Honorário</option>
                        <option value="despesa">Despesa</option>
                        <option value="reembolso">Reembolso</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fas fa-print me-1"></i>Gerar Relatório
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de relatórios gerados -->
<div class="card mt-4" id="generatedReportsList">
    <div class="card-header fw-semibold d-flex justify-content-between">
        <span><i class="fas fa-history me-2 text-muted"></i>Relatórios Gerados</span>
        <button class="btn btn-outline-secondary btn-sm" onclick="loadGeneratedReports()">
            <i class="fas fa-sync-alt me-1"></i>Atualizar
        </button>
    </div>
    <div class="card-body" id="generatedReportsBody">
        <p class="text-muted small mb-0">Clique em "Atualizar" para carregar a lista de relatórios gerados.</p>
    </div>
</div>

<div class="mt-4 card">
    <div class="card-header"><i class="fas fa-info-circle me-2 text-muted"></i>Exportação</div>
    <div class="card-body small text-muted">
        <p>Os relatórios são gerados como HTML imprimível. Use Ctrl+P ou o botão Imprimir para salvar como PDF pelo navegador.</p>
        <p class="mb-0">Para exportação em formato Excel ou PDF nativo, configure uma biblioteca (PhpSpreadsheet / mPDF) no servidor.</p>
    </div>
</div>

<script>
function openCaseReport(type) {
    var inputId = type === 'client' ? 'caseIdClient' : 'caseIdInternal';
    var caseId = document.getElementById(inputId).value;
    if (!caseId || caseId < 1) { alert('Informe um ID de processo válido.'); return; }
    var csrf = '<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>';
    var url = '/reports/case/' + caseId + '/' + type;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.target = '_blank';
    var inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = '_csrf_token'; inp.value = csrf;
    form.appendChild(inp);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function loadGeneratedReports() {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/api/reports/generated');
    xhr.onload = function() {
        try {
            var data = JSON.parse(xhr.responseText);
            var body = document.getElementById('generatedReportsBody');
            if (!data.reports || data.reports.length === 0) {
                body.innerHTML = '<p class="text-muted small mb-0">Nenhum relatório gerado ainda.</p>';
                return;
            }
            var html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tipo</th><th>Entidade</th><th>Gerado por</th><th>Data</th></tr></thead><tbody>';
            data.reports.forEach(function(r) {
                html += '<tr><td><span class="badge bg-secondary">' + (r.report_type || '') + '</span></td>';
                html += '<td>' + (r.entity_type || '') + ' #' + (r.entity_id || '') + '</td>';
                html += '<td>' + (r.created_by_name || '#' + r.created_by) + '</td>';
                html += '<td class="text-muted small">' + (r.created_at || '') + '</td></tr>';
            });
            html += '</tbody></table></div>';
            body.innerHTML = html;
        } catch(e) {}
    };
    xhr.onerror = function() {};
    xhr.send();
}
</script>
