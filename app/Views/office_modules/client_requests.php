<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="fw-bold mb-0">Pendências do Cliente</h4></div>
<div class="card mb-4"><div class="card-body"><form method="POST" class="row g-3"><input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<div class="col-12">
<?php
$clientFieldName = 'client_id';
$caseFieldName = 'case_id';
$selectedClient = null;
$selectedCase = null;
$labelCliente = 'Cliente';
$labelProcesso = 'Processo vinculado';
include ROOT_PATH . '/app/Views/partials/client_case_link.php';
?>
</div>
<div class="col-md-4"><label class="form-label">Título</label><input name="title" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Tipo</label><input name="request_type" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Prazo</label><input type="date" name="due_date" class="form-control"></div>
<div class="col-12"><label class="form-label">Descrição</label><textarea name="description" class="form-control"></textarea></div>
<div class="col-12 text-end"><button class="btn btn-primary">Salvar</button></div></form></div></div>
<div class="card"><table class="table mb-0"><thead><tr><th>Cliente</th><th>Título</th><th>Status</th><th>Prazo</th></tr></thead><tbody><?php foreach($items as $i): ?><tr><td><?= htmlspecialchars($i['client_name'] ?? '') ?></td><td><?= htmlspecialchars($i['title'] ?? '') ?></td><td><?= htmlspecialchars($i['status'] ?? '') ?></td><td><?= htmlspecialchars($i['due_date'] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div>