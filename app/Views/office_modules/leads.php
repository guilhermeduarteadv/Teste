<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-0">Leads / Comercial</h4></div></div>
<div class="card mb-4"><div class="card-header"><strong>Novo registro</strong></div><div class="card-body">
<form method="POST" class="row g-3">
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<div class="col-md-3"><label class="form-label">Nome</label><input type="text" name="name" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Telefone</label><input type="text" name="phone" class="form-control"></div>
<div class="col-md-3"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Origem</label><input type="text" name="source" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Área</label><input type="text" name="area" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Valor estimado</label><input type="text" name="estimated_value" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="novo">Novo</option><option value="contato_feito">Contato feito</option><option value="consulta_marcada">Consulta marcada</option><option value="proposta_enviada">Proposta enviada</option><option value="convertido">Convertido</option><option value="perdido">Perdido</option></select></div>
<div class="col-12"><label class="form-label">Problema jurídico</label><textarea name="legal_issue" class="form-control" rows="3"></textarea></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>

<div class="col-12 text-end"><button class="btn btn-primary">Salvar</button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>name</th><th>phone</th><th>source</th><th>area</th><th>status</th></tr></thead><tbody>
<?php foreach (($items ?? []) as $item): ?><tr><td><?= htmlspecialchars((string)($item['name'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['phone'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['source'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['area'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['status'] ?? '—')) ?></td></tr><?php endforeach; ?>
<?php if (empty($items)): ?><tr><td colspan="5" class="text-center text-muted p-4">Nenhum registro.</td></tr><?php endif; ?>
</tbody></table></div></div>