<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-0">Diagnóstico do Sistema</h4><div class="text-muted">Verificação rápida de ambiente, tabelas e permissões locais</div></div></div>
<div class="card"><div class="card-body p-0"><table class="table table-hover mb-0"><thead><tr><th>Item</th><th>Status</th><th>Informação</th></tr></thead><tbody>
<?php foreach ($checks as $name=>$c): ?><tr><td><?= htmlspecialchars($name) ?></td><td><?= $c['ok']?'<span class="badge bg-success">OK</span>':'<span class="badge bg-danger">Falha</span>' ?></td><td class="small"><?= htmlspecialchars($c['info']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
