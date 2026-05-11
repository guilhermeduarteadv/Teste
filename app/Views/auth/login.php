<?php
$csrf = \Core\Session::csrfToken();
$error = \Core\Session::getFlash('error');
$success = \Core\Session::getFlash('success');
$expired = isset($expired) ? $expired : false;
?>
<?php if ($expired): ?>
<div class="alert alert-warning mb-3"><i class="fas fa-clock me-2"></i>Sua sessão expirou. Faça login novamente.</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success mb-3"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="POST" action="/login" novalidate>
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">E-mail</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="seu@email.com.br"
                   autocomplete="email" required autofocus>
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">Senha</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="••••••••" autocomplete="current-password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePwd" tabindex="-1">
                <i class="fas fa-eye" id="eyeIcon"></i>
            </button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label small" for="remember">Lembrar-me</label>
        </div>
        <a href="/forgot-password" class="small text-decoration-none">Esqueci a senha</a>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="fas fa-sign-in-alt me-2"></i>Entrar no Sistema
    </button>
</form>

<div class="mt-3 text-center">
    <a href="/portal/login" class="small text-muted text-decoration-none">
        <i class="fas fa-user-tie me-1"></i>Área do Cliente
    </a>
</div>

<script>
document.getElementById('togglePwd')?.addEventListener('click', function() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});
</script>
