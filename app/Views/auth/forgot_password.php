<?php
$csrf = \Core\Session::csrfToken();
?>
<p class="text-muted text-center mb-4 small">Informe seu e-mail e enviaremos as instruções para redefinir sua senha.</p>

<form method="POST" action="/forgot-password">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">E-mail</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
            <input type="email" class="form-control" id="email" name="email"
                   placeholder="seu@email.com.br" required autofocus>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2">
        <i class="fas fa-paper-plane me-2"></i>Enviar Instruções
    </button>
</form>

<div class="mt-3 text-center">
    <a href="/login" class="small text-decoration-none">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao login
    </a>
</div>
