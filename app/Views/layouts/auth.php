<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'JurisControl', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e2139 0%, #1a56db 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .auth-card {
            background: #fff;
            border-radius: 1rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .auth-logo { text-align: center; margin-bottom: 1.5rem; }
        .auth-logo .logo-icon { font-size: 3rem; color: #1a56db; }
        .auth-logo .brand-name { font-size: 1.5rem; font-weight: 700; color: #1e2139; }
        .auth-logo .brand-sub { font-size: 0.8rem; color: #6c757d; }
        .btn-primary { background: #1a56db; border-color: #1a56db; }
        .btn-primary:hover { background: #1447c0; border-color: #1447c0; }
        .form-control:focus { border-color: #1a56db; box-shadow: 0 0 0 0.2rem rgba(26,86,219,0.15); }
        .auth-footer { text-align: center; margin-top: 1rem; font-size: 0.8rem; color: #6c757d; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-balance-scale"></i></div>
        <div class="brand-name">JurisControl</div>
        <div class="brand-sub">Sistema de Gestão Jurídica</div>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?= $content ?>

    <div class="auth-footer">
        &copy; <?= date('Y') ?> JurisControl v1.0.0 &mdash; Sistema Jurídico
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
