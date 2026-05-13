<?php $p = $procedure ?? []; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><?= htmlspecialchars($pageTitle ?? 'Processo Administrativo') ?></h4>
    <a href="/administrative-procedures" class="btn btn-outline-secondary">Voltar</a>
</div>

<form method="POST" action="<?= !empty($p['id']) ? '/administrative-procedures/'.$p['id'].'/update' : '/administrative-procedures/store' ?>">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    <div class="card">
        <div class="card-body row g-3">
            
            <div class="col-md-4">
                <label class="form-label">Natureza</label>
                <select name="natureza" class="form-select" id="naturezaProcedimento" onchange="toggleNaturezaCampos()">
                    <option value="administracao_publica" <?= (($p['natureza'] ?? 'administracao_publica')==='administracao_publica')?'selected':'' ?>>Administração Pública / Prefeitura</option>
                    <option value="cartorio" <?= (($p['natureza'] ?? '')==='cartorio')?'selected':'' ?>>Cartório / Extrajudicial</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Título</label>
                <input name="titulo" class="form-control" required value="<?= htmlspecialchars($p['titulo'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <?php
                $clientFieldName = 'client_id';
                $clientMultiple = false;
                $selectedClient = null;
                if (!empty($p['client_id'])) {
                    foreach (($clients ?? []) as $cl) {
                        if ((int)$cl['id'] === (int)$p['client_id']) { $selectedClient = $cl; break; }
                    }
                }
                $roleField = false;
                include ROOT_PATH . '/app/Views/partials/client_autocomplete.php';
                ?>
            </div>
            <div class="col-md-4"><label class="form-label">Número/Protocolo</label><input name="numero_processo" class="form-control" value="<?= htmlspecialchars($p['numero_processo'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Prefeitura</label><input name="prefeitura" class="form-control" value="<?= htmlspecialchars($p['prefeitura'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Secretaria</label><input name="secretaria" class="form-control" value="<?= htmlspecialchars($p['secretaria'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Setor</label><input name="setor" class="form-control" value="<?= htmlspecialchars($p['setor'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Tipo de procedimento</label><input name="tipo_procedimento" class="form-control" placeholder="Habite-se, alvará, regularização..." value="<?= htmlspecialchars($p['tipo_procedimento'] ?? '') ?>"></div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach(['ativo'=>'Ativo','aguardando'=>'Aguardando','exigencia'=>'Exigência','deferido'=>'Deferido','indeferido'=>'Indeferido','encerrado'=>'Encerrado'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= (($p['status'] ?? 'ativo')===$v)?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Assunto</label><input name="assunto" class="form-control" value="<?= htmlspecialchars($p['assunto'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Data do protocolo</label><input type="date" name="data_protocolo" class="form-control" value="<?= htmlspecialchars($p['data_protocolo'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Prazo/resposta</label><input type="date" name="prazo_resposta" class="form-control" value="<?= htmlspecialchars($p['prazo_resposta'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Valor estimado</label><input name="valor_estimado" class="form-control" value="<?= htmlspecialchars($p['valor_estimado'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Responsável</label><select name="responsavel_id" class="form-select"><option value="">—</option><?php foreach($users as $u): ?><option value="<?= $u['id'] ?>" <?= (($p['responsavel_id'] ?? '')==$u['id'])?'selected':'' ?>><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Portal URL</label><input name="portal_url" class="form-control" value="<?= htmlspecialchars($p['portal_url'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Login do portal</label><input name="portal_login" class="form-control" value="<?= htmlspecialchars($p['portal_login'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Senha do portal</label><input name="portal_password" type="password" class="form-control" placeholder="Preencher apenas se for alterar/cadastrar"></div>

            <div class="col-12 natureza-cartorio">
                <div class="alert alert-light border mb-0"><strong>Dados do cartório / procedimento extrajudicial</strong></div>
            </div>
            <div class="col-md-6 natureza-cartorio"><label class="form-label">Nome do cartório</label><input name="cartorio_nome" class="form-control" value="<?= htmlspecialchars($p['cartorio_nome'] ?? '') ?>"></div>
            <div class="col-md-3 natureza-cartorio"><label class="form-label">CNPJ do cartório</label><input name="cartorio_cnpj" class="form-control" value="<?= htmlspecialchars($p['cartorio_cnpj'] ?? '') ?>"></div>
            <div class="col-md-3 natureza-cartorio"><label class="form-label">Oficial/Tabelião</label><input name="cartorio_oficial" class="form-control" value="<?= htmlspecialchars($p['cartorio_oficial'] ?? '') ?>"></div>
            <div class="col-md-3 natureza-cartorio"><label class="form-label">Livro</label><input name="cartorio_livro" class="form-control" value="<?= htmlspecialchars($p['cartorio_livro'] ?? '') ?>"></div>
            <div class="col-md-3 natureza-cartorio"><label class="form-label">Folha</label><input name="cartorio_folha" class="form-control" value="<?= htmlspecialchars($p['cartorio_folha'] ?? '') ?>"></div>
            <div class="col-md-3 natureza-cartorio"><label class="form-label">Matrícula/Registro</label><input name="cartorio_matricula" class="form-control" value="<?= htmlspecialchars($p['cartorio_matricula'] ?? '') ?>"></div>
            <div class="col-md-3 natureza-cartorio"><label class="form-label">Ato</label><input name="cartorio_ato" class="form-control" placeholder="Escritura, registro, averbação..." value="<?= htmlspecialchars($p['cartorio_ato'] ?? '') ?>"></div>
<script>
function toggleNaturezaCampos() {
    const natureza = document.getElementById('naturezaProcedimento')?.value || 'administracao_publica';
    document.querySelectorAll('.natureza-cartorio').forEach(el => el.style.display = natureza === 'cartorio' ? '' : 'none');
}
document.addEventListener('DOMContentLoaded', toggleNaturezaCampos);
</script>

            <div class="col-12"><label class="form-label">Observações</label><textarea name="observacoes" class="form-control" rows="4"><?= htmlspecialchars($p['observacoes'] ?? '') ?></textarea></div>
        </div>
        <div class="card-footer text-end"><button class="btn btn-primary">Salvar</button></div>
    </div>
</form>
