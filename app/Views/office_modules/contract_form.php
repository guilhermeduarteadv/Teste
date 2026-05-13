<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="fw-bold mb-0">Novo Contrato de Honorários</h4><a href="/contracts" class="btn btn-outline-secondary">Voltar</a></div>
<form method="POST" action="/contracts/store"><input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
<div class="card"><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Título</label><input name="title" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Tipo</label><select name="fee_type" class="form-select"><option value="fixo">Fixo</option><option value="mensal">Mensal</option><option value="exito">Êxito</option><option value="misto">Misto</option></select></div>
<div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="ativo">Ativo</option><option value="suspenso">Suspenso</option><option value="encerrado">Encerrado</option></select></div>
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
<div class="col-md-2"><label class="form-label">Fixo</label><input name="fixed_amount" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Mensal</label><input name="monthly_amount" class="form-control"></div>
<div class="col-md-2"><label class="form-label">% Êxito</label><input name="success_percentage" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Mínimo êxito</label><input name="success_minimum" class="form-control"></div>
<div class="col-md-2"><label class="form-label">Parcelas</label><input type="number" name="installments" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Início</label><input type="date" name="start_date" class="form-control"></div>
<div class="col-md-3"><label class="form-label">Fim</label><input type="date" name="end_date" class="form-control"></div>
<div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control"></textarea></div>
</div><div class="card-footer text-end"><button class="btn btn-primary">Salvar</button></div></div></form>