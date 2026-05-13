<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="fas fa-bell me-2 text-primary"></i>Notificações</h4>
        <p class="text-muted small mb-0">Alertas e avisos do sistema</p>
    </div>
    <button id="btnReadAll" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-check-double me-1"></i>Marcar todas como lidas
    </button>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <?php if (empty($notifications)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="fas fa-bell-slash fa-3x mb-3 opacity-25"></i>
        <p class="mb-0">Nenhuma notificação encontrada.</p>
    </div>
    <?php else: ?>
    <div class="list-group list-group-flush" id="notif-list">
        <?php foreach ($notifications as $n): ?>
        <?php
            $isRead = !empty($n['read_at']);
            $rowClass = $isRead ? '' : 'list-group-item-light';
            $typeIcon = [
                'warning'  => 'fas fa-exclamation-triangle text-warning',
                'error'    => 'fas fa-times-circle text-danger',
                'deadline' => 'fas fa-clock text-danger',
                'task'     => 'fas fa-tasks text-primary',
                'document' => 'fas fa-file text-info',
                'payment'  => 'fas fa-dollar-sign text-success',
                'info'     => 'fas fa-info-circle text-primary',
            ];
            $icon = $typeIcon[$n['type']] ?? 'fas fa-bell text-secondary';
        ?>
        <div class="list-group-item <?= $rowClass ?> py-3" id="notif-<?= (int)$n['id'] ?>">
            <div class="d-flex align-items-start gap-3">
                <div class="mt-1"><i class="<?= $icon ?>"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold <?= $isRead ? 'text-muted' : '' ?>">
                            <?= htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="text-muted small ms-3 text-nowrap">
                            <?= htmlspecialchars($n['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?php if (!empty($n['message'])): ?>
                    <p class="mb-1 small text-muted"><?= htmlspecialchars($n['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!$isRead): ?>
                    <button class="btn btn-link btn-sm p-0 text-primary btn-mark-read" data-id="<?= (int)$n['id'] ?>">
                        Marcar como lida
                    </button>
                    <?php endif; ?>
                </div>
                <?php if (!$isRead): ?>
                <span class="badge bg-primary rounded-pill align-self-center">Nova</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
var BASE = '<?= defined('APP_BASE_PATH') ? APP_BASE_PATH : '' ?>';
var csrf = '<?= htmlspecialchars(\Core\Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>';

document.querySelectorAll('.btn-mark-read').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        var row = document.getElementById('notif-' + id);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', BASE + '/notifications/' + id + '/read');
        xhr.setRequestHeader('X-CSRF-Token', csrf);
        xhr.onload = function() {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success && row) {
                    row.classList.remove('list-group-item-light');
                    var badge = row.querySelector('.badge');
                    if (badge) badge.remove();
                    var markBtn = row.querySelector('.btn-mark-read');
                    if (markBtn) markBtn.remove();
                    var title = row.querySelector('.fw-semibold');
                    if (title) title.classList.add('text-muted');
                }
            } catch(e) {}
        };
        var fd = new FormData();
        fd.append('_csrf_token', csrf);
        xhr.send(fd);
    });
});

document.getElementById('btnReadAll').addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('_csrf_token', csrf);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE + '/notifications/read-all');
    xhr.onload = function() {
        btn.disabled = false;
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
                document.querySelectorAll('.list-group-item-light').forEach(function(el) {
                    el.classList.remove('list-group-item-light');
                    var badge = el.querySelector('.badge');
                    if (badge) badge.remove();
                    var markBtn = el.querySelector('.btn-mark-read');
                    if (markBtn) markBtn.remove();
                    var title = el.querySelector('.fw-semibold');
                    if (title) title.classList.add('text-muted');
                });
                var counter = document.getElementById('notif-counter');
                if (counter) counter.style.display = 'none';
            }
        } catch(e) {}
    };
    xhr.send(fd);
});
</script>
