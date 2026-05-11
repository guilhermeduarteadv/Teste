<div class="card shadow-sm" style="margin-top:2rem;">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="fas fa-balance-scale fa-3x text-primary mb-2"></i>
            <h5 class="fw-bold">Acesso ao Portal</h5>
            <p class="text-muted small">Entre com suas credenciais fornecidas pelo escritório</p>
        </div>
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="POST" action="/portal/login">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">E-mail</label>
                <input type="email" class="form-control" name="email" required autocomplete="email" placeholder="seu@email.com">
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Senha</label>
                <input type="password" class="form-control" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-sign-in-alt me-2"></i>Entrar
            </button>
        </form>
        <div class="text-center mt-3 small text-muted">
            Dificuldades? Entre em contato com o escritório.
        </div>
    </div>
</div>
