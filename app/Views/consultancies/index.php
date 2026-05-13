<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Consultorias Jurídicas</h4>
        <p class="text-muted small mb-0">Consultas, pareceres, orientações e assessorias extrajudiciais.</p>
    </div>
    <a href="/consultancies/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Nova Consultoria</a>
</div>

<div class="card mb-3"><div class="card-body">
    <form method="GET" action="/consultancies" class="row g-2">
        <div class="col-md-7"><input class="form-control" name="search" placeholder="Buscar por cliente, área, tipo..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>"></div>
        <div class="col-md-3"><select name="status" class="form-select"><option value="">Todos</option><?php foreach(['em_andamento'=>'Em andamento','concluida'=>'Concluída','suspensa'=>'Suspensa','cancelada'=>'Cancelada'] as $v=>$l): ?><option value="<?= $v ?>" <?= ($filters['status'] ?? '')===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
    </form>
</div></div>

<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Título</th><th>Cliente</th><th>Área</th><th>Início</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
<tbody>
<?php foreach($consultancies as $c): ?>
<tr>
<td><a href="/consultancies/<?= $c['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($c['titulo']) ?></a><div class="text-muted small"><?= htmlspecialchars($c['tipo_consultoria'] ?? '') ?></div></td>
<td><?= htmlspecialchars($c['client_name'] ?? '—') ?></td>
<td><?= htmlspecialchars($c['area'] ?? '—') ?></td>
<td><?= !empty($c['data_inicio']) ? date('d/m/Y', strtotime($c['data_inicio'])) : '—' ?></td>
<td><span class="badge bg-secondary"><?= htmlspecialchars($c['status']) ?></span></td>
<td class="text-end"><a href="/consultancies/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a> <a href="/consultancies/<?= $c['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Editar</a></td>
</tr>
<?php endforeach; ?>
<?php if(empty($consultancies)): ?><tr><td colspan="6" class="text-center text-muted py-4">Nenhuma consultoria encontrada.</td></tr><?php endif; ?>
</tbody></table></div></div>
