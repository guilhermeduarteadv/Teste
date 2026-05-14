<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title ?? 'JurisControl', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #1a56db;
            --sidebar-bg: #1e2139;
            --sidebar-text: #b0b8d4;
            --sidebar-hover: rgba(255,255,255,0.08);
            --sidebar-active: rgba(26,86,219,0.3);
        }
        body { background-color: #f4f6fb; font-family: 'Inter', sans-serif; }
        /* Sidebar */
        #sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-width);
            height: 100vh; background: var(--sidebar-bg); z-index: 1000;
            overflow-y: auto; transition: width 0.3s;
            display: flex; flex-direction: column;
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08);
            color: #fff; font-size: 1.1rem; font-weight: 700; text-decoration: none;
            display: flex; align-items: center; gap: 0.6rem;
        }
        #sidebar .sidebar-brand .logo-icon { font-size: 1.4rem; color: var(--primary-color); }
        #sidebar .nav-section { padding: 0.75rem 1rem 0.25rem; font-size: 0.7rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.35); }
        #sidebar .nav-link {
            display: flex; align-items: center; gap: 0.7rem; padding: 0.6rem 1.5rem;
            color: var(--sidebar-text); text-decoration: none; font-size: 0.875rem;
            border-radius: 0; transition: all 0.2s; border-left: 3px solid transparent;
        }
        #sidebar .nav-link:hover { background: var(--sidebar-hover); color: #fff; }
        #sidebar .nav-link.active { background: var(--sidebar-active); color: #fff; border-left-color: var(--primary-color); }
        #sidebar .nav-link i { width: 18px; text-align: center; font-size: 0.95rem; }
        /* Main content */
        #main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        /* Topbar */
        #topbar {
            background: #fff; padding: 0.75rem 1.5rem; border-bottom: 1px solid #e9ecef;
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 500;
        }
        .page-wrapper { padding: 1.5rem; }
        /* Cards */
        .card { border: 1px solid #e9ecef; border-radius: 0.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .card-header { background: #fff; border-bottom: 1px solid #e9ecef; font-weight: 600; }
        /* Stats cards */
        .stat-card { border-radius: 0.75rem; padding: 1.25rem; color: #fff; position: relative; overflow: hidden; }
        .stat-card .stat-icon { font-size: 2.5rem; opacity: 0.25; position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 700; }
        .stat-card .stat-label { font-size: 0.8rem; opacity: 0.85; }
        /* Badges */
        .badge { font-size: 0.72rem; font-weight: 500; }
        /* Table */
        .table th { font-size: 0.78rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; }
        .table td { vertical-align: middle; font-size: 0.875rem; }
        /* Alerts */
        .alert { border-radius: 0.5rem; }
        /* Forms */
        .form-control:focus, .form-select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 0.2rem rgba(26,86,219,0.15); }
        /* Pagination */
        .page-link { color: var(--primary-color); }
        .page-item.active .page-link { background-color: var(--primary-color); border-color: var(--primary-color); }
        /* Responsive */
        @media (max-width: 768px) {
            #sidebar { width: 0; overflow: hidden; }
            #sidebar.show { width: var(--sidebar-width); }
            #main-content { margin-left: 0; }
        }
        /* Sidebar user info */
        #sidebar .sidebar-user {
            padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: auto; color: var(--sidebar-text); font-size: 0.8rem;
            display: flex; align-items: center; gap: 0.6rem;
        }
        #sidebar .sidebar-user .avatar-circle {
            width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color);
            display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 600; font-size: 0.85rem; flex-shrink: 0;
        }
    </style>
</head>
<body>
<script>
window.APP_BASE_PATH = <?= json_encode($basePath ?? '') ?>;
if (/^[A-Z]:\//i.test(window.APP_BASE_PATH || '')) { window.APP_BASE_PATH = '/public'; }
const APP_BASE_PATH = window.APP_BASE_PATH || '';
</script>

<?php
$currentUser = \Core\Session::get('user');
$currentUri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$base        = rtrim(defined('APP_BASE_PATH') ? APP_BASE_PATH : '', '/');
$isActive    = function(string $path) use ($currentUri, $base): string {
    return strpos($currentUri, $base . $path) === 0 ? 'active' : '';
};
?>
<!-- Sidebar -->
<nav id="sidebar">
    <a href="<?= $base ?>/dashboard" class="sidebar-brand">
        <span class="logo-icon"><i class="fas fa-balance-scale"></i></span>
        JurisControl
    </a>

    <div class="mt-1">
        <div class="nav-section">Principal</div>
        <a href="<?= $base ?>/dashboard" class="nav-link <?= $isActive('/dashboard') ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?= $base ?>/calendar" class="nav-link <?= $isActive('/calendar') ?>">
            <i class="fas fa-calendar-alt"></i> Calendário
        </a>

        <div class="nav-section">Gestão</div>
        <a href="<?= $base ?>/clients" class="nav-link <?= $isActive('/clients') ?>">
            <i class="fas fa-users"></i> Clientes
        </a>
        <a href="<?= $base ?>/cases" class="nav-link <?= $isActive('/cases') ?>">
            <i class="fas fa-gavel"></i> Processos
        </a>

        <a href="<?= $base ?>/administrative-procedures" class="nav-link <?= $isActive('/administrative-procedures') ?>">
            <i class="fas fa-city"></i> Administrativos
        </a>
        <a href="<?= $base ?>/consultancies" class="nav-link <?= $isActive('/consultancies') ?>">
            <i class="fas fa-comments"></i> Consultorias
        </a>

        <a href="<?= $base ?>/tasks" class="nav-link <?= $isActive('/tasks') ?>">
            <i class="fas fa-tasks"></i> Tarefas
        </a>
        <a href="<?= $base ?>/financial" class="nav-link <?= $isActive('/financial') ?>">
            <i class="fas fa-dollar-sign"></i> Financeiro
        </a>
        <a href="<?= $base ?>/timesheets" class="nav-link <?= $isActive('/timesheets') ?>">
            <i class="fas fa-stopwatch"></i> Timesheet
        </a>

        <a href="<?= $base ?>/contracts" class="nav-link <?= $isActive('/contracts') ?>">
            <i class="fas fa-file-contract"></i> Honorários
        </a>
        <a href="<?= $base ?>/payables" class="nav-link <?= $isActive('/payables') ?>">
            <i class="fas fa-receipt"></i> Contas a Pagar
        </a>
        <a href="<?= $base ?>/dre" class="nav-link <?= $isActive('/dre') ?>">
            <i class="fas fa-calculator"></i> DRE
        </a>
        <a href="<?= $base ?>/leads" class="nav-link <?= $isActive('/leads') ?>">
            <i class="fas fa-user-plus"></i> Leads
        </a>
        <a href="<?= $base ?>/checklists" class="nav-link <?= $isActive('/checklists') ?>">
            <i class="fas fa-clipboard-check"></i> Checklists
        </a>
        <a href="<?= $base ?>/client-requests" class="nav-link <?= $isActive('/client-requests') ?>">
            <i class="fas fa-inbox"></i> Pendências Cliente
        </a>

        <a href="<?= $base ?>/publications" class="nav-link <?= $isActive('/publications') ?>">
            <i class="fas fa-newspaper"></i> Publicações
        </a>
        <a href="<?= $base ?>/documents" class="nav-link <?= $isActive('/documents') ?>">
            <i class="fas fa-folder-open"></i> Documentos
        </a>

        <a href="<?= $base ?>/tribunals" class="nav-link <?= $isActive('/tribunals') ?>">
            <i class="fas fa-plug"></i> Tribunais
        </a>
        <a href="<?= $base ?>/templates" class="nav-link <?= $isActive('/templates') && !$isActive('/generated-documents') ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> Modelos
        </a>
        <a href="<?= $base ?>/generated-documents" class="nav-link <?= $isActive('/generated-documents') ?>">
            <i class="fas fa-file-medical"></i> Docs Gerados
        </a>
        <a href="<?= $base ?>/search" class="nav-link <?= $isActive('/search') ?>">
            <i class="fas fa-search"></i> Busca Global
        </a>

        <div class="nav-section">Jurídico</div>
        <a href="<?= $base ?>/deadlines" class="nav-link <?= $isActive('/deadlines') ?>">
            <i class="fas fa-hourglass-half"></i> Prazos
        </a>

        <div class="nav-section">Conhecimento</div>
        <a href="<?= $base ?>/knowledge/jurisprudence" class="nav-link <?= $isActive('/knowledge/jurisprudence') ?>">
            <i class="fas fa-book-open"></i> Jurisprudência
        </a>
        <a href="<?= $base ?>/knowledge/theses" class="nav-link <?= $isActive('/knowledge/theses') ?>">
            <i class="fas fa-scroll"></i> Teses
        </a>

        <div class="nav-section">Análise</div>
        <a href="<?= $base ?>/reports" class="nav-link <?= $isActive('/reports') ?>">
            <i class="fas fa-chart-bar"></i> Relatórios
        </a>
        <a href="<?= $base ?>/stats" class="nav-link <?= $isActive('/stats') ?>">
            <i class="fas fa-chart-line"></i> Estatísticas
        </a>
        <a href="<?= $base ?>/productivity" class="nav-link <?= $isActive('/productivity') ?>">
            <i class="fas fa-user-clock"></i> Produtividade
        </a>
        <a href="<?= $base ?>/financial/repases" class="nav-link <?= $isActive('/financial/repases') ?>">
            <i class="fas fa-hand-holding-usd"></i> Repasses
        </a>

        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
        <div class="nav-section">Administração</div>
        <a href="<?= $base ?>/admin/users" class="nav-link <?= $isActive('/admin/users') ?>">
            <i class="fas fa-user-shield"></i> Usuários
        </a>
        <a href="<?= $base ?>/admin/settings" class="nav-link <?= $isActive('/admin/settings') ?>">
            <i class="fas fa-cog"></i> Configurações
        </a>
        <a href="<?= $base ?>/admin/tribunals" class="nav-link <?= $isActive('/admin/tribunals') ?>">
            <i class="fas fa-landmark"></i> Tribunais
        </a>
        <a href="<?= $base ?>/admin/logs" class="nav-link <?= $isActive('/admin/logs') ?>">
            <i class="fas fa-list-alt"></i> Logs
        </a>
        <a href="<?= $base ?>/diagnostics" class="nav-link <?= $isActive('/diagnostics') ?>">
            <i class="fas fa-stethoscope"></i> Diagnóstico
        </a>
        <a href="<?= $base ?>/backups" class="nav-link <?= $isActive('/backups') ?>">
            <i class="fas fa-database"></i> Backup
        </a>

        <a href="<?= $base ?>/admin/system-check" class="nav-link <?= $isActive('/admin/system-check') ?>">
            <i class="fas fa-shield-alt"></i> Saúde do Sistema
        </a>
        <a href="<?= $base ?>/admin/audit" class="nav-link <?= $isActive('/admin/audit') ?>">
            <i class="fas fa-clipboard-list"></i> Auditoria
        </a>
        <a href="<?= $base ?>/maintenance/diagnostics" class="nav-link <?= $isActive('/maintenance/diagnostics') ?>">
            <i class="fas fa-heartbeat"></i> Diagnóstico Avançado
        </a>
        <a href="<?= $base ?>/maintenance/migrations" class="nav-link <?= $isActive('/maintenance/migrations') ?>">
            <i class="fas fa-code-branch"></i> Atualizações
        </a>
        <a href="<?= $base ?>/maintenance/error-logs" class="nav-link <?= $isActive('/maintenance/error-logs') ?>">
            <i class="fas fa-bug"></i> Logs de Erro
        </a>

        <?php endif; ?>

        <div class="nav-section">Portal</div>
        <a href="<?= $base ?>/portal/login" class="nav-link" target="_blank">
            <i class="fas fa-external-link-alt"></i> Portal Cliente
        </a>
    </div>

    <div class="sidebar-user">
        <div class="avatar-circle">
            <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div>
            <div style="color:#fff;font-weight:500;"><?= htmlspecialchars(substr($currentUser['name'] ?? '', 0, 20), ENT_QUOTES, 'UTF-8') ?></div>
            <div><?= htmlspecialchars(ucfirst($currentUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div id="main-content">
    <!-- Topbar -->
    <div id="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0 small">
                    <?php foreach ($breadcrumbs ?? [['label' => 'Dashboard', 'url' => '/dashboard']] as $bc): ?>
                    <li class="breadcrumb-item <?= isset($bc['active']) ? 'active' : '' ?>">
                        <?php if (isset($bc['url']) && !isset($bc['active'])): ?>
                            <a href="<?= $bc['url'] ?>"><?= htmlspecialchars($bc['label'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($bc['label'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form action="/search" method="GET" class="d-none d-md-flex">
                <div class="input-group input-group-sm">
                    <input type="text" name="q" class="form-control" placeholder="Buscar..." style="width:200px"
                           value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <span class="text-muted small d-none d-md-block">
                <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i') ?>
            </span>
            <div class="dropdown">
                <a href="<?= $base ?>/notifications" class="btn btn-sm btn-outline-secondary position-relative" title="Notificações">
                    <i class="fas fa-bell"></i>
                    <span id="notif-counter" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;font-size:0.6rem;">0</span>
                </a>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars(explode(' ', $currentUser['name'] ?? 'Usuário')[0], ENT_QUOTES, 'UTF-8') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= $base ?>/admin/settings"><i class="fas fa-cog me-2"></i>Configurações</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= $base ?>/logout"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="page-wrapper">
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});
// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert-dismissible').forEach(function(el) {
    setTimeout(function() {
        var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
        bsAlert && bsAlert.close();
    }, 5000);
});

// Notification badge polling
(function() {
    var BASE = '<?= defined('APP_BASE_PATH') ? APP_BASE_PATH : '' ?>';
    function updateNotifCount() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', BASE + '/api/notifications/unread');
        xhr.onload = function() {
            try {
                var data = JSON.parse(xhr.responseText);
                var counter = document.getElementById('notif-counter');
                if (counter) {
                    if (data.count > 0) {
                        counter.textContent = data.count > 99 ? '99+' : data.count;
                        counter.style.display = '';
                    } else {
                        counter.style.display = 'none';
                    }
                }
            } catch(e) {}
        };
        xhr.onerror = function() {};
        xhr.send();
    }
    updateNotifCount();
    setInterval(updateNotifCount, 60000);
})();
</script>
</body>
</html>
