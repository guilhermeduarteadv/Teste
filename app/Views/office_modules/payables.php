<div class="d-flex justify-content-between align-items-center mb-4"><div><h4 class="fw-bold mb-0">Contas a Pagar</h4></div></div>
<div class="card mb-4"><div class="card-header"><strong>Novo registro</strong></div><div class="card-body">
<form method="POST" class="row g-3">
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<div class="col-md-3"><label class="form-label">Descrição</label><input type="text" name="description" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Categoria</label><input type="text" name="category" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Fornecedor</label><input type="text" name="supplier" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Valor</label><input type="text" name="amount" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Vencimento</label><input type="date" name="due_date" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Pagamento</label><input type="date" name="payment_date" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Forma</label><input type="text" name="payment_method" class="form-control"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="3"></textarea></div>

<div class="col-12 text-end"><button class="btn btn-primary">Salvar</button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>description</th><th>category</th><th>supplier</th><th>amount</th><th>due_date</th><th>status</th></tr></thead><tbody>
<?php foreach (($items ?? []) as $item): ?><tr><td><?= htmlspecialchars((string)($item['description'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['category'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['supplier'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['amount'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['due_date'] ?? '—')) ?></td><td><?= htmlspecialchars((string)($item['status'] ?? '—')) ?></td></tr><?php endforeach; ?>
<?php if (empty($items)): ?><tr><td colspan="6" class="text-center text-muted p-4">Nenhum registro.</td></tr><?php endif; ?>
</tbody></table></div></div>