<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Portal do Cliente', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f4f6fb; font-family: 'Inter', sans-serif; }
        .portal-navbar { background: #1e2139; padding: 0.75rem 1.5rem; }
        .portal-navbar .brand { color: #fff; font-size: 1.1rem; font-weight: 700; text-decoration: none; }
        .portal-navbar .brand i { color: #1a56db; margin-right: 0.4rem; }
        .portal-content { padding: 2rem 1rem; max-width: 1100px; margin: 0 auto; }
        .nav-pills .nav-link { color: #6c757d; }
        .nav-pills .nav-link.active { background: #1a56db; }
        .card { border: 1px solid #e9ecef; border-radius: 0.75rem; }
    </style>
</head>
<body>
<?php $portalClient = \Core\Session::get('portal_client'); ?>
<nav class="portal-navbar d-flex align-items-center justify-content-between">
    <a href="/portal/cases" class="brand">
        <i class="fas fa-balance-scale"></i> JurisControl — Portal do Cliente
    </a>
    <?php if ($portalClient): ?>
    <div class="d-flex align-items-center gap-3">
        <span class="text-white-50 small"><?= htmlspecialchars($portalClient['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/portal/logout" class="btn btn-sm btn-outline-light">
            <i class="fas fa-sign-out-alt me-1"></i>Sair
        </a>
    </div>
    <?php endif; ?>
</nav>

<?php if ($portalClient): ?>
<div class="container portal-content">
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/portal/cases') === 0 ? 'active' : '' ?>" href="/portal/cases">
                <i class="fas fa-gavel me-1"></i>Processos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/portal/financial') === 0 ? 'active' : '' ?>" href="/portal/financial">
                <i class="fas fa-dollar-sign me-1"></i>Financeiro
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/portal/documents') === 0 ? 'active' : '' ?>" href="/portal/documents">
                <i class="fas fa-folder me-1"></i>Documentos
            </a>
        </li>
    </ul>
    <?= $content ?>
</div>
<?php else: ?>
<div class="container" style="max-width:450px;padding-top:3rem;">
    <?= $content ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
