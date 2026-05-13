<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-0">Checklists por Procedimento</h4></div></div>
<div class="card mb-4"><div class="card-header"><strong>Novo registro</strong></div><div class="card-body">
<form method="POST" class="row g-3">
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<div class="col-md-3"><label class="form-label">Nome</label><input type="text" name="name" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Módulo</label><input type="text" name="module" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Tipo de procedimento</label><input type="text" name="procedure_type" class="form-control"></div>
<div class="col-12"><label class="form-label">Itens, um por linha</label><textarea name="items" class="form-control" rows="3"></textarea></div>

<div class="col-12 text-end"><button class="btn btn-primary">Salvar</button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>name</th><th>module</th><th>procedure_type</th><th>active</th></tr></thead><tbody>
<?php foreach (($items ?? []) as $item): ?><tr><td><?= htmlspecialchars((string)($item['name'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['module'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['procedure_type'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['active'] ?? '—')) ?></td></tr><?php endforeach; ?>
<?php if (empty($items)): ?><tr><td colspan="4" class="text-center text-muted p-4">Nenhum registro.</td></tr><?php endif; ?>
</tbody></table></div></div>