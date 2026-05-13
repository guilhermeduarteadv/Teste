<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-heartbeat me-2 text-primary"></i>Saúde do Sistema</h4>
        <p class="text-muted small mb-0">Verificação estrutural do banco, pastas, extensões PHP e configurações</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/system-check/export" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-download me-1"></i>Exportar JSON
        </a>
        <button id="btnRepair" class="btn btn-warning btn-sm">
            <i class="fas fa-wrench me-1"></i>Corrigir Estrutura
        </button>
        <button id="btnRun" class="btn btn-primary">
            <i class="fas fa-play me-2"></i>Executar Verificação
        </button>
    </div>
</div>

<div id="alert-area" class="mb-3"></div>

<div id="results-area" style="display:none;">
    <div class="row g-3 mb-4" id="summary-cards">
        <div class="col-md-4">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-success" id="cnt-ok">0</div>
                    <div class="text-muted small">OK</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-warning" id="cnt-warning">0</div>
                    <div class="text-muted small">Avisos</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-2 fw-bold text-danger" id="cnt-error">0</div>
                    <div class="text-muted small">Erros</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold">Resultado da Verificação</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px">Status</th>
                        <th style="width:90px">Tipo</th>
                        <th>Item</th>
                        <th>Esperado</th>
                        <th>Atual</th>
                        <th>Mensagem</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody id="results-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($runs)): ?>
<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white fw-semibold">Últimas Verificações</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Status</th><th>Erros</th><th>Avisos</th><th>Data</th></tr>
            </thead>
            <tbody>
                <?php foreach ($runs as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td>
                        <?php if ($r['status'] === 'ok'): ?>
                            <span class="badge bg-success">OK</span>
                        <?php elseif ($r['status'] === 'warning'): ?>
                            <span class="badge bg-warning text-dark">Aviso</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Erro</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$r['total_errors'] ?></td>
                    <td><?= (int)$r['total_warnings'] ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['finished_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
var BASE = '<?= defined('APP_BASE_PATH') ? APP_BASE_PATH : '' ?>';
var csrf = '<?= htmlspecialchars(\Core\Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>';

var typeLabels = {
    table: 'Tabela', column: 'Coluna', user: 'Usuário',
    folder: 'Pasta', extension: 'Extensão', php: 'PHP',
    file: 'Arquivo', config: 'Config', data: 'Dados'
};

function statusBadge(s) {
    if (s === 'ok')      return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>';
    if (s === 'warning') return '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Aviso</span>';
    return '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Erro</span>';
}

function esc(v) {
    var d = document.createElement('div');
    d.textContent = v || '';
    return d.innerHTML;
}

document.getElementById('btnRun').addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verificando...';
    document.getElementById('alert-area').innerHTML = '';

    var fd = new FormData();
    fd.append('_csrf_token', csrf);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE + '/admin/system-check/run');
    xhr.onload = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Executar Verificação';
        try {
            var data = JSON.parse(xhr.responseText);
            if (!data.success) {
                document.getElementById('alert-area').innerHTML =
                    '<div class="alert alert-danger">' + esc(data.message) + '</div>';
                return;
            }
            renderResults(data.items, data.summary);
        } catch(e) {
            document.getElementById('alert-area').innerHTML =
                '<div class="alert alert-danger">Erro ao processar resposta.</div>';
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Executar Verificação';
        document.getElementById('alert-area').innerHTML =
            '<div class="alert alert-danger">Falha na requisição.</div>';
    };
    xhr.send(fd);
});

function renderResults(items, summary) {
    document.getElementById('cnt-ok').textContent      = summary.total - summary.errors - summary.warnings;
    document.getElementById('cnt-warning').textContent = summary.warnings;
    document.getElementById('cnt-error').textContent   = summary.errors;

    var rows = '';
    for (var i = 0; i < items.length; i++) {
        var item = items[i];
        rows += '<tr class="' + (item.status === 'error' ? 'table-danger' : item.status === 'warning' ? 'table-warning' : '') + '">'
            + '<td>' + statusBadge(item.status) + '</td>'
            + '<td><span class="badge bg-secondary">' + esc(typeLabels[item.type] || item.type) + '</span></td>'
            + '<td><code>' + esc(item.name) + '</code></td>'
            + '<td class="text-muted small">' + esc(item.expected) + '</td>'
            + '<td class="text-muted small">' + esc(item.actual) + '</td>'
            + '<td class="small">' + esc(item.message) + '</td>'
            + '<td class="text-muted small">' + (item.repair_action ? esc(item.repair_action) : '') + '</td>'
            + '</tr>';
    }
    document.getElementById('results-tbody').innerHTML = rows;
    document.getElementById('results-area').style.display = 'block';
    document.getElementById('results-area').scrollIntoView({behavior:'smooth', block:'start'});
}

document.getElementById('btnRepair').addEventListener('click', function() {
    if (!confirm('Executar reparadores automáticos? Nenhum dado será apagado.')) return;
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Reparando...';

    var fd = new FormData();
    fd.append('_csrf_token', csrf);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE + '/admin/system-check/repair');
    xhr.onload = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wrench me-1"></i>Corrigir Estrutura';
        try {
            var data = JSON.parse(xhr.responseText);
            var msgs = [];
            if (data.result && data.result.repaired) {
                data.result.repaired.forEach(function(m) { msgs.push('<li class="text-success">' + esc(m) + '</li>'); });
            }
            if (data.result && data.result.errors) {
                data.result.errors.forEach(function(m) { msgs.push('<li class="text-danger">' + esc(m) + '</li>'); });
            }
            var cls = (data.result && data.result.errors && data.result.errors.length) ? 'alert-warning' : 'alert-success';
            document.getElementById('alert-area').innerHTML =
                '<div class="alert ' + cls + '"><strong>Resultado do reparo:</strong><ul class="mb-0 mt-1">' + msgs.join('') + '</ul></div>';
        } catch(e) {
            document.getElementById('alert-area').innerHTML =
                '<div class="alert alert-danger">Erro ao processar reparo.</div>';
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wrench me-1"></i>Corrigir Estrutura';
        document.getElementById('alert-area').innerHTML =
            '<div class="alert alert-danger">Falha na requisição.</div>';
    };
    xhr.send(fd);
});
</script>
