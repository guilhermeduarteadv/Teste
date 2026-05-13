<div class="d-flex justify-content-between align-items-center mb-4"><h4 class="fw-bold mb-0">DRE Simples</h4><form><input type="number" name="year" value="<?= (int)$year ?>" class="form-control d-inline-block" style="width:120px"><button class="btn btn-primary">Filtrar</button></form></div>
<?php
$map=[]; foreach($receitas as $r){$map[$r['mes']]['receita']=(float)$r['total'];} foreach($despesas as $d){$map[$d['mes']]['despesa']=(float)$d['total'];} ksort($map);
?>
<div class="card"><table class="table mb-0"><thead><tr><th>Mês</th><th>Receitas</th><th>Despesas</th><th>Resultado</th></tr></thead><tbody>
<?php foreach($map as $mes=>$v): $rec=$v['receita']??0; $des=$v['despesa']??0; ?>
<tr><td><?= htmlspecialchars($mes) ?></td><td>R$ <?= number_format($rec,2,',','.') ?></td><td>R$ <?= number_format($des,2,',','.') ?></td><td>R$ <?= number_format($rec-$des,2,',','.') ?></td></tr>
<?php endforeach; ?></tbody></table></div>