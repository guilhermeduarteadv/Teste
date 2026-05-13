<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Publicações</h4>
        <p class="text-muted small mb-0">Leitura do Diário da Justiça, importação manual e vinculação automática ao histórico dos processos.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Ler DJE/TJSP por OAB</div>
            <div class="card-body">
                <form id="djeForm" class="row g-2">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-md-4">
                        <label class="form-label small">Número OAB</label>
                        <input type="text" name="oab_number" class="form-control" placeholder="513079" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">UF</label>
                        <select name="oab_state" class="form-select">
                            <option value="SP" selected>SP</option>
                            <option value="RJ">RJ</option>
                            <option value="MG">MG</option>
                            <option value="PR">PR</option>
                            <option value="SC">SC</option>
                            <option value="RS">RS</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">De</label>
                        <input type="date" name="date_start" class="form-control" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Até</label>
                        <input type="date" name="date_end" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Termo adicional opcional</label>
                        <input type="text" name="query_extra" class="form-control" placeholder="Ex.: nome do advogado, sociedade, número do processo">
                    </div>
                    <div class="col-12 d-flex gap-2 align-items-center">
                        <button class="btn btn-success" id="djeBtn" type="submit"><i class="bi bi-search"></i> Ler diário</button>
                        <span class="small text-muted">Busca no DJE/TJSP e vincula automaticamente pelo número CNJ.</span>
                    </div>
                </form>
                <div id="djeResult" class="small mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Ler texto colado do diário</div>
            <div class="card-body">
                <form id="diaryTextForm" class="row g-2">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-md-4">
                        <label class="form-label small">Data da publicação</label>
                        <input type="date" name="data_publicacao_texto" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <textarea name="diary_text" class="form-control" rows="7" placeholder="Cole aqui o texto extraído do DJE, DJEN, PDF ou publicação. O sistema procura números CNJ e vincula aos processos cadastrados."></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-journal-text"></i> Ler texto</button>
                    </div>
                </form>
                <div id="textResult" class="small mt-3"></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Importar publicação manualmente</div>
    <div class="card-body">
        <form id="pubForm" class="row g-2">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-md-3"><input type="date" name="data_publicacao" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-9"><input type="text" name="titulo" class="form-control" placeholder="Título"></div>
            <div class="col-md-12"><textarea name="texto" class="form-control" rows="4" placeholder="Cole aqui o texto da publicação"></textarea></div>
            <div><button class="btn btn-primary">Importar manual</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Últimas publicações importadas</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Data</th><th>Processo</th><th>Fonte</th><th>Título</th><th>Texto</th></tr></thead>
            <tbody>
            <?php if (empty($publications)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma publicação importada.</td></tr>
            <?php endif; ?>
            <?php foreach($publications as $p): ?>
                <tr>
                    <td><?= !empty($p['data_publicacao']) ? date('d/m/Y', strtotime($p['data_publicacao'])) : '-' ?></td>
                    <td>
                        <?= htmlspecialchars($p['numero_cnj'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                        <?php if(!empty($p['linked_case_id'])): ?><span class="badge bg-success ms-1">Vinculada</span><?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($p['fonte'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($p['titulo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small" style="max-width:520px"><?= htmlspecialchars(mb_substr($p['texto'] ?? '',0,280), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function postFormJson(url, form) {
    const response = await fetch((window.APP_BASE_PATH || '') + url, { method: 'POST', body: new FormData(form) });
    const text = await response.text();
    try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Resposta inválida do servidor'); }
}
function showResult(el, data) {
    el.innerHTML = `<div class="alert ${data.success ? 'alert-success' : 'alert-danger'} mb-0">${data.message || 'Operação concluída.'}<br>` +
        `Encontradas: ${data.found ?? '-'} | Novas: ${data.imported ?? 0} | Atualizadas: ${data.updated ?? 0} | Vinculadas: ${data.linked ?? 0} | Movimentos: ${data.movements ?? 0}</div>`;
}

document.getElementById('pubForm')?.addEventListener('submit', async function(e){
    e.preventDefault();
    try { const d = await postFormJson('/publications/import', this); alert(d.message); if(d.success) location.reload(); }
    catch(err){ alert(err.message); }
});

document.getElementById('djeForm')?.addEventListener('submit', async function(e){
    e.preventDefault();
    const btn = document.getElementById('djeBtn');
    const out = document.getElementById('djeResult');
    btn.disabled = true; btn.innerHTML = 'Lendo...'; out.innerHTML = '<div class="text-muted">Consultando DJE/TJSP...</div>';
    try { const d = await postFormJson('/publications/read-dje', this); showResult(out, d); if(d.success && ((d.imported||0)+(d.updated||0)>0)) setTimeout(()=>location.reload(), 1200); }
    catch(err){ out.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`; }
    finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-search"></i> Ler diário'; }
});

document.getElementById('diaryTextForm')?.addEventListener('submit', async function(e){
    e.preventDefault();
    const out = document.getElementById('textResult');
    try { const d = await postFormJson('/publications/read-text', this); showResult(out, d); if(d.success && ((d.imported||0)+(d.updated||0)>0)) setTimeout(()=>location.reload(), 1200); }
    catch(err){ out.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`; }
});
</script>
