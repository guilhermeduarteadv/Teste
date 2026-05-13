<?php use App\Helpers\FormatHelper; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Processos Administrativos</h4>
        <p class="text-muted small mb-0">Procedimentos junto a prefeituras, secretarias e órgãos administrativos.</p>
    </div>
    <a href="/administrative-procedures/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Novo Procedimento</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/administrative-procedures" class="row g-2">
            <div class="col-md-6"><input class="form-control" name="search" placeholder="Buscar por cliente, prefeitura, número, assunto..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>"></div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach(['ativo'=>'Ativo','aguardando'=>'Aguardando','exigencia'=>'Exigência','deferido'=>'Deferido','indeferido'=>'Indeferido','encerrado'=>'Encerrado'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($filters['status'] ?? '')===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Título</th><th>Cliente</th><th>Prefeitura</th><th>Protocolo</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
                <?php foreach($procedures as $p): ?>
                <tr>
                    <td>
                        <a href="/administrative-procedures/<?= $p['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars($p['titulo']) ?></a>
                        <div class="text-muted small"><?= htmlspecialchars($p['numero_processo'] ?: $p['assunto'] ?: '') ?></div>
                    </td>
                    <td><?= htmlspecialchars($p['client_name'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['prefeitura'] ?? '—') ?></td>
                    <td><?= !empty($p['data_protocolo']) ? date('d/m/Y', strtotime($p['data_protocolo'])) : '—' ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['status']) ?></span></td>
                    <td class="text-end">
                        <a href="/administrative-procedures/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                        <a href="/administrative-procedures/<?= $p['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">Editar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($procedures)): ?><tr><td colspan="6" class="text-center text-muted py-4">Nenhum procedimento encontrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
