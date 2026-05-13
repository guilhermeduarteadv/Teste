<?php $c = $consultancy ?? []; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><?= htmlspecialchars($pageTitle ?? 'Consultoria') ?></h4>
    <a href="/consultancies" class="btn btn-outline-secondary">Voltar</a>
</div>

<form method="POST" action="<?= !empty($c['id']) ? '/consultancies/'.$c['id'].'/update' : '/consultancies/store' ?>">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    <div class="card">
        <div class="card-body row g-3">
            <div class="col-md-8"><label class="form-label">Título</label><input name="titulo" required class="form-control" value="<?= htmlspecialchars($c['titulo'] ?? '') ?>"></div>
            <div class="col-md-4">
                <?php
                $clientFieldName = 'client_id';
                $clientMultiple = false;
                $selectedClient = null;
                if (!empty($c['client_id'])) {
                    foreach (($clients ?? []) as $cl) {
                        if ((int)$cl['id'] === (int)$c['client_id']) { $selectedClient = $cl; break; }
                    }
                }
                $roleField = false;
                include ROOT_PATH . '/app/Views/partials/client_autocomplete.php';
                ?>
            </div>
            <div class="col-md-4"><label class="form-label">Área</label><input name="area" class="form-control" placeholder="Família, Trabalhista, Imobiliário..." value="<?= htmlspecialchars($c['area'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Tipo de consultoria</label><input name="tipo_consultoria" class="form-control" placeholder="Parecer, reunião, contrato..." value="<?= htmlspecialchars($c['tipo_consultoria'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach(['em_andamento'=>'Em andamento','concluida'=>'Concluída','suspensa'=>'Suspensa','cancelada'=>'Cancelada'] as $v=>$l): ?><option value="<?= $v ?>" <?= (($c['status'] ?? 'em_andamento')===$v)?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Data de início</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($c['data_inicio'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Data de conclusão</label><input type="date" name="data_conclusao" class="form-control" value="<?= htmlspecialchars($c['data_conclusao'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Valor estimado</label><input name="valor_estimado" class="form-control" value="<?= htmlspecialchars($c['valor_estimado'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Responsável</label><select name="responsavel_id" class="form-select"><option value="">—</option><?php foreach($users as $u): ?><option value="<?= $u['id'] ?>" <?= (($c['responsavel_id'] ?? '')==$u['id'])?'selected':'' ?>><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="4"><?= htmlspecialchars($c['descricao'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label">Observações</label><textarea name="observacoes" class="form-control" rows="3"><?= htmlspecialchars($c['observacoes'] ?? '') ?></textarea></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-primary">Salvar</button></div>
    </div>
</form>
