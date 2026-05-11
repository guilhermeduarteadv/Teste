<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Editar Usuário</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/admin/users">Usuários</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($editUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
        </ol></nav>
    </div>
    <a href="/admin/users" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/users/<?= $editUser['id'] ?>/update" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nome Completo *</label>
                    <input type="text" class="form-control" name="name" required maxlength="100"
                           value="<?= htmlspecialchars($editUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">E-mail *</label>
                    <input type="email" class="form-control" name="email" required
                           value="<?= htmlspecialchars($editUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nova Senha</label>
                    <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password"
                           placeholder="Deixe em branco para não alterar">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Confirmar Nova Senha</label>
                    <input type="password" class="form-control" name="password_confirmation" autocomplete="new-password"
                           placeholder="Deixe em branco para não alterar">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Perfil *</label>
                    <select class="form-select" name="role_id" required>
                        <?php foreach ($roles ?? [] as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (int)($editUser['role_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Cargo</label>
                    <input type="text" class="form-control" name="cargo" maxlength="100"
                           value="<?= htmlspecialchars($editUser['cargo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?= ($editUser['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inactive" <?= ($editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Número OAB</label>
                    <input type="text" class="form-control" name="oab_number" maxlength="20"
                           value="<?= htmlspecialchars($editUser['oab_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Estado OAB</label>
                    <select class="form-select" name="oab_state">
                        <option value="">Selecione...</option>
                        <?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
                        <option value="<?= $uf ?>" <?= ($editUser['oab_state'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Telefone</label>
                    <input type="tel" class="form-control" name="phone" maxlength="11"
                           value="<?= htmlspecialchars($editUser['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <?php if (!empty($allPermissions)): ?>
            <hr>
            <h6 class="fw-semibold mb-3">Permissões Específicas</h6>
            <?php $userPermIds = array_column($userPermissions ?? [], 'permission_id'); ?>
            <div class="row g-2">
                <?php foreach ($allPermissions as $perm): ?>
                <div class="col-6 col-md-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="permissions[]"
                               id="perm_<?= $perm['id'] ?>" value="<?= $perm['id'] ?>"
                               <?= in_array($perm['id'], $userPermIds) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="perm_<?= $perm['id'] ?>">
                            <?= htmlspecialchars($perm['name'], ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                <a href="/admin/users" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
