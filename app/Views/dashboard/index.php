<?php
use App\Helpers\FormatHelper;
use App\Helpers\DateHelper;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <p class="text-muted small mb-0">Visão geral do escritório em <?= date('d/m/Y') ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-success" id="btnImportProcesses"><i class="fas fa-cloud-download-alt me-1"></i>Importar processos</button>
        <a href="/cases/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Novo Processo</a>
        <a href="/clients/create" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-plus me-1"></i>Novo Cliente</a>
    </div>
</div>

<!-- Critical Alerts -->
<?php
$criticalAlerts = [];
if (!empty($expiredDeadlines)) {
    $criticalAlerts[] = ['type'=>'danger','icon'=>'fas fa-exclamation-circle','msg'=>count($expiredDeadlines).' prazo(s) vencido(s)','link'=>'/cases'];
}
if (!empty($overdueTasks)) {
    $criticalAlerts[] = ['type'=>'warning','icon'=>'fas fa-tasks','msg'=>count($overdueTasks).' tarefa(s) atrasada(s)','link'=>'/tasks'];
}
if (($financialSummary['total_vencido'] ?? 0) > 0) {
    $criticalAlerts[] = ['type'=>'warning','icon'=>'fas fa-dollar-sign','msg'=>'Inadimplência: '.FormatHelper::money((float)$financialSummary['total_vencido']),'link'=>'/financial'];
}
if (!empty($criticalAlerts)): ?>
<div class="mb-3">
    <?php foreach ($criticalAlerts as $alert): ?>
    <a href="<?= $alert['link'] ?>" class="alert alert-<?= $alert['type'] ?> py-2 px-3 mb-1 d-flex align-items-center text-decoration-none" style="font-size:0.875rem;">
        <i class="<?= $alert['icon'] ?> me-2"></i><?= htmlspecialchars($alert['msg'], ENT_QUOTES, 'UTF-8') ?>
        <i class="fas fa-arrow-right ms-auto"></i>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#1a56db,#1447c0);">
            <div class="stat-value"><?= number_format($activeCases ?? 0) ?></div>
            <div class="stat-label">Processos Ativos</div>
            <div class="stat-icon"><i class="fas fa-gavel"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#059669,#047857);">
            <div class="stat-value"><?= number_format($activeClients ?? 0) ?></div>
            <div class="stat-label">Clientes Ativos</div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#d97706,#b45309);">
            <div class="stat-value"><?= count($upcomingDeadlines ?? []) ?></div>
            <div class="stat-label">Prazos (7 dias)</div>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
            <div class="stat-value"><?= FormatHelper::money((float)($financialSummary['total_pendente'] ?? 0)) ?></div>
            <div class="stat-label">A Receber</div>
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Upcoming Deadlines -->
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-clock text-warning me-2"></i>Prazos Próximos</span>
                <a href="/cases" class="btn btn-sm btn-link p-0">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingDeadlines)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>Nenhum prazo nos próximos 7 dias.
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($upcomingDeadlines, 0, 5) as $dl): ?>
                    <?php
                    $daysLeft = DateHelper::daysUntil($dl['data_final']);
                    $colorClass = $daysLeft <= 0 ? 'danger' : ($daysLeft <= 2 ? 'warning' : 'primary');
                    ?>
                    <div class="list-group-item border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small fw-semibold"><?= htmlspecialchars($dl['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    <?= htmlspecialchars($dl['numero_cnj'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($dl['assunto'])): ?> &mdash; <?= htmlspecialchars($dl['assunto'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                </div>
                            </div>
                            <span class="badge bg-<?= $colorClass ?> ms-2">
                                <?= DateHelper::humanDiff($dl['data_final']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending Tasks -->
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-tasks text-primary me-2"></i>Tarefas Pendentes</span>
                <a href="/tasks" class="btn btn-sm btn-link p-0">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php $allTasks = array_merge($overdueTasks ?? [], $upcomingTasks ?? []); ?>
                <?php if (empty($allTasks)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>Nenhuma tarefa pendente.
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($allTasks, 0, 5) as $task): ?>
                    <?php
                    $isOverdue = !empty($task['prazo']) && DateHelper::isOverdue($task['prazo']);
                    ?>
                    <div class="list-group-item border-0 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small fw-semibold">
                                    <?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    <?= htmlspecialchars($task['responsavel_name'] ?? 'Sem responsável', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            <div class="ms-2 text-end">
                                <?= FormatHelper::priorityBadge($task['prioridade']) ?>
                                <?php if (!empty($task['prazo'])): ?>
                                <div class="small <?= $isOverdue ? 'text-danger' : 'text-muted' ?>" style="font-size:0.72rem;">
                                    <?= DateHelper::formatBr($task['prazo']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-dollar-sign text-success me-2"></i>Resumo Financeiro</div>
            <div class="card-body">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 rounded bg-success bg-opacity-10">
                            <div class="fw-bold text-success"><?= FormatHelper::money((float)($financialSummary['total_recebido'] ?? 0)) ?></div>
                            <div class="small text-muted">Recebido</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-warning bg-opacity-10">
                            <div class="fw-bold text-warning"><?= FormatHelper::money((float)($financialSummary['total_pendente'] ?? 0)) ?></div>
                            <div class="small text-muted">Pendente</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded bg-danger bg-opacity-10">
                            <div class="fw-bold text-danger"><?= FormatHelper::money((float)($financialSummary['total_vencido'] ?? 0)) ?></div>
                            <div class="small text-muted">Vencido</div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($overdueFinancial)): ?>
                <hr>
                <div class="small fw-semibold text-danger mb-2"><i class="fas fa-exclamation-triangle me-1"></i>Vencidos (<?= count($overdueFinancial) ?>)</div>
                <?php foreach (array_slice($overdueFinancial, 0, 3) as $f): ?>
                <div class="d-flex justify-content-between border-bottom py-1 small">
                    <span><?= htmlspecialchars($f['descricao'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="text-danger fw-semibold"><?= FormatHelper::money((float)$f['valor']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>



    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie text-success me-2"></i>Processos por Área</div>
            <div class="card-body">
                <?php $areaTotal = array_sum($casesByArea ?? []); ?>
                <?php foreach ($casesByArea ?? [] as $area => $cnt): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= htmlspecialchars($area, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="fw-semibold"><?= $cnt ?> (<?= $areaTotal > 0 ? round($cnt / $areaTotal * 100) : 0 ?>%)</span>
                    </div>
                    <div class="progress" style="height:6px;"><div class="progress-bar bg-success" style="width:<?= $areaTotal > 0 ? ($cnt / $areaTotal * 100) : 0 ?>%"></div></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($casesByArea)): ?><div class="text-center text-muted py-3">Nenhum processo classificado.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Case Status -->
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-chart-pie text-primary me-2"></i>Processos por Status</div>
            <div class="card-body">
                <?php
                $statusLabels = ['ativo' => 'Ativo', 'arquivado' => 'Arquivado', 'suspenso' => 'Suspenso', 'encerrado' => 'Encerrado', 'aguardando' => 'Aguardando'];
                $statusColors = ['ativo' => 'primary', 'arquivado' => 'secondary', 'suspenso' => 'warning', 'encerrado' => 'dark', 'aguardando' => 'info'];
                $total = array_sum($statusCounts ?? []);
                ?>
                <?php foreach ($statusCounts ?? [] as $st => $cnt): ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= $statusLabels[$st] ?? ucfirst($st) ?></span>
                        <span class="fw-semibold"><?= $cnt ?> (<?= $total > 0 ? round($cnt / $total * 100) : 0 ?>%)</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-<?= $statusColors[$st] ?? 'secondary' ?>"
                             style="width:<?= $total > 0 ? ($cnt / $total * 100) : 0 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($statusCounts)): ?>
                <div class="text-center text-muted py-3">Nenhum processo cadastrado.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardImportByOab(btn){
    let oab = prompt('Informe o número da sua OAB, apenas números:', '513079');
    if (oab === null) return;
    oab = (oab || '').replace(/\D/g, '');

    let uf = prompt('Informe a UF da OAB:', 'SP');
    if (uf === null) return;
    uf = (uf || '').trim().toUpperCase();

    if (!oab || !uf) {
        alert('Número OAB e estado são obrigatórios.');
        return;
    }

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Importando...';

    const fd = new FormData();
    fd.append('_csrf_token', '<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>');
    fd.append('oab_number', oab);
    fd.append('oab_state', uf);

    fetch((window.APP_BASE_PATH || '') + '/api/cnj/sync', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>'},
        body: fd
    })
    .then(async r => {
        const text = await r.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            const clean = (text || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            return {success:false, message: clean ? ('Resposta inválida do servidor: ' + clean.substring(0, 500)) : 'Resposta inválida do servidor sem conteúdo.'};
        }
    })
    .then(data => {
        alert(data.message || 'Operação concluída.');
        if (data.success) location.reload();
    })
    .catch(err => alert(err && err.message ? err.message : 'Erro ao importar processos.'))
    .finally(() => { btn.disabled = false; btn.innerHTML = original; });
}

document.getElementById('btnImportProcesses')?.addEventListener('click', function(){ dashboardImportByOab(this); });
</script>


<div id="dashboard-stats-v36" class="row mt-4">
    <div class="col-md-6" id="chart-processes-month"></div>
    <div class="col-md-6" id="chart-received-month"></div>
    <div class="col-md-6" id="chart-cases-area"></div>
    <div class="col-md-6" id="chart-cases-comarca"></div>
</div>
<script src="<?= (defined('BASE_URL') ? BASE_URL : '/public') ?>/assets/js/dashboard_stats_v36.js"></script>
