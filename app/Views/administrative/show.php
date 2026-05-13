<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($procedure['titulo']) ?></h4>
        <p class="text-muted small mb-0"><?= htmlspecialchars($procedure['prefeitura'] ?? '') ?> <?= !empty($procedure['numero_processo']) ? ' · ' . htmlspecialchars($procedure['numero_processo']) : '' ?></p>
    </div>
    <div>
        <a href="/financial/create?administrative_procedure_id=<?= $procedure['id'] ?>&client_id=<?= (int)($procedure['client_id'] ?? 0) ?>" class="btn btn-success">Lançar financeiro</a>
        <a href="/administrative-procedures/<?= $procedure['id'] ?>/edit" class="btn btn-outline-secondary">Editar</a>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card mb-3"><div class="card-header">Dados</div><div class="card-body">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($procedure['client_name'] ?? '—') ?></p>
            <p><strong>Secretaria/Setor:</strong> <?= htmlspecialchars(($procedure['secretaria'] ?? '—') . ' / ' . ($procedure['setor'] ?? '—')) ?></p>
            <p><strong>Natureza:</strong> <?= (($procedure['natureza'] ?? '')==='cartorio') ? 'Cartório / Extrajudicial' : 'Administração Pública' ?></p>
            <?php if (($procedure['natureza'] ?? '') === 'cartorio'): ?>
            <p><strong>Cartório:</strong> <?= htmlspecialchars($procedure['cartorio_nome'] ?? '—') ?></p>
            <p><strong>Matrícula/Registro:</strong> <?= htmlspecialchars($procedure['cartorio_matricula'] ?? '—') ?></p>
            <p><strong>Livro/Folha:</strong> <?= htmlspecialchars(($procedure['cartorio_livro'] ?? '—') . ' / ' . ($procedure['cartorio_folha'] ?? '—')) ?></p>
            <p><strong>Ato:</strong> <?= htmlspecialchars($procedure['cartorio_ato'] ?? '—') ?></p>
            <?php endif; ?>
            <p><strong>Tipo:</strong> <?= htmlspecialchars($procedure['tipo_procedimento'] ?? '—') ?></p>
            <p><strong>Assunto:</strong> <?= htmlspecialchars($procedure['assunto'] ?? '—') ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($procedure['status'] ?? '—') ?></p>
            <p><strong>Portal:</strong> <?= !empty($procedure['portal_url']) ? '<a target="_blank" href="'.htmlspecialchars($procedure['portal_url']).'">'.htmlspecialchars($procedure['portal_url']).'</a>' : '—' ?></p>
            <p><strong>Login:</strong> <?= htmlspecialchars($procedure['portal_login'] ?? '—') ?></p>
            <p><strong>Observações:</strong><br><?= nl2br(htmlspecialchars($procedure['observacoes'] ?? '')) ?></p>
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card"><div class="card-header">Financeiro vinculado</div><div class="card-body p-0">
            <table class="table table-sm mb-0">
                <?php foreach($financial as $f): ?><tr><td><?= htmlspecialchars($f['descricao']) ?></td><td>R$ <?= number_format((float)$f['valor'],2,',','.') ?></td><td><?= htmlspecialchars($f['status']) ?></td></tr><?php endforeach; ?>
                <?php if(empty($financial)): ?><tr><td class="text-muted p-3">Nenhum lançamento vinculado.</td></tr><?php endif; ?>
            </table>
        </div></div>
    </div>
</div>
