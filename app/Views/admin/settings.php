<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Configurações do Sistema</h4>
        <p class="text-muted small mb-0">Parâmetros gerais do escritório</p>
    </div>
</div>

<?php
$get = function(string $key, string $default = '') use ($settings): string {
    if (isset($settings[$key])) {
        $item = $settings[$key];
        if (is_array($item)) {
            return (string)($item['value'] ?? $item['valor'] ?? $item['raw'] ?? $default);
        }
        return (string)$item;
    }
    foreach ($settings as $s) {
        if (is_array($s) && (($s['key'] ?? $s['chave'] ?? '') === $key)) {
            return (string)($s['value'] ?? $s['valor'] ?? $s['raw'] ?? $default);
        }
    }
    return $default;
};
?>

<form method="POST" action="<?= rtrim($base_path ?? '/public', '/') ?>/admin/settings/update" enctype="multipart/form-data">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <div class="row g-3">
        <!-- Office Info -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header fw-semibold"><i class="fas fa-building me-2"></i>Dados do Escritório</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Escritório</label>
                        <input type="text" class="form-control" name="office_name" maxlength="200"
                               value="<?= htmlspecialchars($get('office_name'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label">Número OAB</label>
                            <input type="text" class="form-control" name="office_oab" maxlength="20"
                                   value="<?= htmlspecialchars($get('office_oab'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="office_oab_state">
                                <option value="">UF</option>
                                <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
                                <option value="<?= $uf ?>" <?= $get('office_oab_state') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="office_email"
                               value="<?= htmlspecialchars($get('office_email'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="tel" class="form-control" name="office_phone"
                               value="<?= htmlspecialchars($get('office_phone'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <textarea class="form-control" name="office_address" rows="2"><?= htmlspecialchars($get('office_address'), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file" class="form-control" name="logo" accept=".jpg,.jpeg,.png,.gif,.svg">
                        <?php if ($get('logo_path')): ?>
                        <div class="mt-2"><img src="<?= htmlspecialchars($get('logo_path'), ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height:50px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="col-12 col-lg-6">
            <div class="card mb-3">
                <div class="card-header fw-semibold"><i class="fas fa-sync me-2"></i>Sincronização CNJ</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Sincronização Automática</label>
                        <select class="form-select" name="cnj_sync_enabled">
                            <option value="1" <?= $get('cnj_sync_enabled') === '1' ? 'selected' : '' ?>>Habilitada</option>
                            <option value="0" <?= $get('cnj_sync_enabled') === '0' ? 'selected' : '' ?>>Desabilitada</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Intervalo de Sincronização (horas)</label>
                        <input type="number" class="form-control" name="cnj_sync_interval" min="1" max="168"
                               value="<?= htmlspecialchars($get('cnj_sync_interval', '24'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header fw-semibold"><i class="fas fa-shield-alt me-2"></i>Segurança</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Timeout de Sessão (minutos)</label>
                        <input type="number" class="form-control" name="session_timeout" min="5" max="1440"
                               value="<?= htmlspecialchars($get('session_timeout', '120'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Máx. Tentativas de Login</label>
                        <input type="number" class="form-control" name="max_login_attempts" min="3" max="20"
                               value="<?= htmlspecialchars($get('max_login_attempts', '5'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bloqueio de Login (minutos)</label>
                        <input type="number" class="form-control" name="login_block_minutes" min="1" max="1440"
                               value="<?= htmlspecialchars($get('login_block_minutes', '30'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Configurações</button>
        </div>
    </div>
</form>
