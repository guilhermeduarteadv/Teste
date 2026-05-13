<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Conectar Tribunais</h4>
        <p class="text-muted small mb-0">Use sessão autenticada para importação por OAB e processos em segredo de justiça.</p>
    </div>
</div>

<div class="alert alert-warning">
    <strong>Importante:</strong> por segurança e estabilidade, o MVP usa preferencialmente cookies/sessão do navegador já autenticado no tribunal. Login por senha fica armazenado criptografado, mas eproc/e-SAJ podem exigir SSO, 2FA ou captcha, impedindo login automático puro por PHP/cURL.
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-plug me-2"></i>Nova conexão</div>
            <div class="card-body">
                <form method="POST" action="/tribunals/store">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Tribunal</label>
                            <select name="tribunal" class="form-select">
                                <option value="tjsp">TJSP</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sistema</label>
                            <select name="sistema" class="form-select">
                                <option value="eproc">eproc</option>
                                <option value="esaj">e-SAJ</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">OAB</label>
                            <input type="text" name="oab_number" class="form-control" placeholder="513079">
                        </div>
                        <div class="col-6">
                            <label class="form-label">UF</label>
                            <input type="text" name="oab_state" class="form-control" maxlength="2" placeholder="SP">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Usuário do tribunal</label>
                            <input type="text" name="username" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Senha do tribunal</label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password">
                            <div class="form-text">A senha é criptografada no banco. Para o MVP, cookies autenticados são mais confiáveis.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Cookies/sessão autenticada</label>
                            <textarea name="cookies" rows="6" class="form-control" placeholder="Cole aqui o cabeçalho Cookie do eproc/e-SAJ após fazer login no navegador"></textarea>
                            <div class="form-text">No Chrome: F12 > Network > abra uma página do eproc/e-SAJ logado > Request Headers > Cookie.</div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3"><i class="fas fa-save me-2"></i>Salvar conexão</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-list me-2"></i>Conexões cadastradas</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Tribunal</th><th>Sistema</th><th>OAB</th><th>Sessão</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php if (empty($connections)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma conexão cadastrada.</td></tr>
                    <?php else: foreach ($connections as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars(strtoupper($c['tribunal']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($c['sistema'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(($c['oab_number'] ?: '-') . '/' . ($c['oab_state'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= !empty($c['has_cookies']) ? '<span class="badge bg-success">cookies</span>' : '<span class="badge bg-warning text-dark">sem cookies</span>' ?></td>
                            <td><span class="badge bg-<?= ($c['status'] ?? '') === 'ativo' ? 'success' : (($c['status'] ?? '') === 'erro' ? 'danger' : 'secondary') ?>"><?= htmlspecialchars($c['status'] ?? 'pendente', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <form method="POST" action="/tribunals/<?= (int)$c['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Remover conexão?')">
                                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                <button class="btn btn-sm btn-outline-primary" onclick="testConnection(<?= (int)$c['id'] ?>)"><i class="fas fa-vial"></i></button>
                            </td>
                        </tr>
                        <?php if (!empty($c['last_error'])): ?><tr><td colspan="6" class="small text-muted">Último retorno: <?= htmlspecialchars($c['last_error'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endif; ?>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function testConnection(id){
    const fd = new FormData(); fd.append('_csrf_token', '<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>');
    fetch(APP_BASE_PATH + '/tribunals/' + id + '/test', {method:'POST', body:fd})
      .then(r=>r.json()).then(d=>{ alert(d.message || 'Teste finalizado.'); location.reload(); })
      .catch(()=>alert('Falha ao testar conexão.'));
}
</script>
