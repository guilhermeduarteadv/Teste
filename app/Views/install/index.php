<?php
$csrf = \Core\Session::csrfToken();
?>
<div id="install-form">
    <div id="step-1">
        <h5 class="fw-bold mb-1">Bem-vindo ao JurisControl!</h5>
        <p class="text-muted small mb-4">Configure as informações abaixo para concluir a instalação.</p>

        <div id="alert-area"></div>

        <form id="installForm">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

            <h6 class="fw-semibold border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-database me-2 text-primary"></i>Banco de Dados
            </h6>
            <div class="row g-3">
                <div class="col-8">
                    <label class="form-label">Host</label>
                    <input type="text" class="form-control" name="db_host" value="localhost" required>
                </div>
                <div class="col-4">
                    <label class="form-label">Porta</label>
                    <input type="text" class="form-control" name="db_port" value="3306" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Nome do Banco</label>
                    <input type="text" class="form-control" name="db_database" placeholder="juriscontrol" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Usuário</label>
                    <input type="text" class="form-control" name="db_username" placeholder="root" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Senha</label>
                    <input type="password" class="form-control" name="db_password" placeholder="••••••••">
                </div>
            </div>

            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="testDbBtn">
                    <i class="fas fa-plug me-1"></i>Testar Conexão
                </button>
                <span id="db-test-result" class="ms-2 small"></span>
            </div>

            <h6 class="fw-semibold border-bottom pb-2 mb-3 mt-4">
                <i class="fas fa-user-shield me-2 text-primary"></i>Administrador
            </h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" class="form-control" name="admin_name" placeholder="Dr. João da Silva" required>
                </div>
                <div class="col-12">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="admin_email" placeholder="admin@escritorio.com.br" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Senha</label>
                    <input type="password" class="form-control" name="admin_password"
                           placeholder="Mínimo 8 caracteres" required minlength="8">
                </div>
                <div class="col-6">
                    <label class="form-label">Confirmar Senha</label>
                    <input type="password" class="form-control" name="admin_password_confirmation"
                           placeholder="Repita a senha" required minlength="8">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary w-100 py-2" id="installBtn">
                    <i class="fas fa-rocket me-2"></i>Instalar Sistema
                </button>
            </div>
        </form>
    </div>

    <div id="step-success" class="text-center" style="display:none;">
        <div style="font-size:4rem;color:#198754;"><i class="fas fa-check-circle"></i></div>
        <h4 class="fw-bold mt-3">Instalação Concluída!</h4>
        <p class="text-muted">O sistema foi configurado com sucesso. Você será redirecionado para o login.</p>
        <a href="/login" class="btn btn-primary mt-2"><i class="fas fa-sign-in-alt me-2"></i>Ir para o Login</a>
    </div>
</div>

<script>
var BASE = '<?= defined('APP_BASE_PATH') ? APP_BASE_PATH : '' ?>';

document.getElementById('testDbBtn').addEventListener('click', function() {
    var form = document.getElementById('installForm');
    var btn = this;
    var result = document.getElementById('db-test-result');
    btn.disabled = true;
    result.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Testando...</span>';

    var data = new FormData(form);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE + '/install/test-db');
    xhr.onload = function() {
        try {
            var json = JSON.parse(xhr.responseText);
            if (json.success) {
                result.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>' + json.message + '</span>';
            } else {
                result.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>' + json.message + '</span>';
            }
        } catch(e) {
            result.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Resposta inválida do servidor.</span>';
        }
        btn.disabled = false;
    };
    xhr.onerror = function() {
        result.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Erro na requisição.</span>';
        btn.disabled = false;
    };
    xhr.send(data);
});

document.getElementById('installForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('installBtn');
    var alertArea = document.getElementById('alert-area');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Instalando...';
    alertArea.innerHTML = '';

    var data = new FormData(this);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE + '/install/run');
    xhr.onload = function() {
        try {
            var json = JSON.parse(xhr.responseText);
            if (json.success) {
                document.getElementById('step-1').style.display = 'none';
                document.getElementById('step-success').style.display = 'block';
                setTimeout(function() { window.location.href = BASE + (json.redirect || '/login'); }, 3000);
            } else {
                var msgs = json.errors ? json.errors.join('<br>') : (json.message || 'Erro desconhecido.');
                alertArea.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' + msgs + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket me-2"></i>Instalar Sistema';
            }
        } catch(e) {
            alertArea.innerHTML = '<div class="alert alert-danger">Erro ao processar a requisição.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rocket me-2"></i>Instalar Sistema';
        }
    };
    xhr.onerror = function() {
        alertArea.innerHTML = '<div class="alert alert-danger">Erro ao processar a requisição.</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rocket me-2"></i>Instalar Sistema';
    };
    xhr.send(data);
});
</script>
