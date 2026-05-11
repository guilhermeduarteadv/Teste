<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - JurisControl</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e2139 0%, #1a56db 100%); min-height: 100vh; padding: 2rem 0; font-family: 'Inter', sans-serif; }
        .install-card { background: #fff; border-radius: 1rem; padding: 2.5rem; max-width: 700px; margin: 0 auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .install-header { text-align: center; margin-bottom: 2rem; }
        .install-header .logo-icon { font-size: 3rem; color: #1a56db; }
        .install-header h1 { font-size: 1.6rem; font-weight: 700; color: #1e2139; margin-top: 0.5rem; }
        .step-indicator { display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 2rem; }
        .step { width: 2rem; height: 2rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 600; }
        .step.active { background: #1a56db; color: #fff; }
        .step.done { background: #198754; color: #fff; }
        .step.pending { background: #e9ecef; color: #6c757d; }
        .btn-primary { background: #1a56db; border-color: #1a56db; }
    </style>
</head>
<body>
<div class="container">
    <div class="install-card">
        <div class="install-header">
            <div class="logo-icon"><i class="fas fa-balance-scale"></i></div>
            <h1>JurisControl</h1>
            <p class="text-muted">Assistente de Instalação</p>
        </div>
        <?= $content ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
