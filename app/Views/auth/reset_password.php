<?php
$csrf = \Core\Session::csrfToken();
$token = htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8');
?>
<p class="text-muted text-center mb-4 small">Digite sua nova senha abaixo.</p>

<form method="POST" action="/reset-password">
    <input type="hidden" name="_csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="token" value="<?= $token ?>">

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">Nova Senha</label>
        <input type="password" class="form-control" id="password" name="password"
               placeholder="Mínimo 8 caracteres" required minlength="8">
        <div class="form-text">Mínimo 8 caracteres, com letras maiúsculas, minúsculas e números.</div>
    </div>

    <div class="mb-3">
        <label for="password_confirmation" class="form-label fw-semibold">Confirmar Senha</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
               placeholder="Repita a senha" required minlength="8">
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="fas fa-key me-2"></i>Redefinir Senha
    </button>
</form>

<div class="mt-3 text-center">
    <a href="/login" class="small text-decoration-none">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao login
    </a>
</div>
