<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-0"><?= htmlspecialchars($consultancy['titulo']) ?></h4><p class="text-muted small mb-0"><?= htmlspecialchars($consultancy['client_name'] ?? '') ?></p></div>
    <div><a href="/financial/create?consultancy_id=<?= $consultancy['id'] ?>&client_id=<?= (int)($consultancy['client_id'] ?? 0) ?>" class="btn btn-success">Lançar financeiro</a> <a href="/consultancies/<?= $consultancy['id'] ?>/edit" class="btn btn-outline-secondary">Editar</a></div>
</div>
<div class="row">
<div class="col-md-7"><div class="card mb-3"><div class="card-header">Dados</div><div class="card-body">
<p><strong>Cliente:</strong> <?= htmlspecialchars($consultancy['client_name'] ?? '—') ?></p>
<p><strong>Área:</strong> <?= htmlspecialchars($consultancy['area'] ?? '—') ?></p>
<p><strong>Tipo:</strong> <?= htmlspecialchars($consultancy['tipo_consultoria'] ?? '—') ?></p>
<p><strong>Status:</strong> <?= htmlspecialchars($consultancy['status'] ?? '—') ?></p>
<p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($consultancy['descricao'] ?? '')) ?></p>
<p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars($consultancy['observacoes'] ?? '')) ?></p>
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header">Financeiro vinculado</div><div class="card-body p-0"><table class="table table-sm mb-0">
<?php foreach($financial as $f): ?><tr><td><?= htmlspecialchars($f['descricao']) ?></td><td>R$ <?= number_format((float)$f['valor'],2,',','.') ?></td><td><?= htmlspecialchars($f['status']) ?></td></tr><?php endforeach; ?>
<?php if(empty($financial)): ?><tr><td class="text-muted p-3">Nenhum lançamento vinculado.</td></tr><?php endif; ?>
</table></div></div></div>
</div>
