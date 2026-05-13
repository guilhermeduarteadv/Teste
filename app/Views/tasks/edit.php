<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Editar Tarefa</h4>
        <nav aria-label="breadcrumb"><ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="/tasks">Tarefas</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol></nav>
    </div>
    <a href="/tasks" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Voltar</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/tasks/<?= $task['id'] ?>/update" novalidate>
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Título *</label>
                    <input type="text" class="form-control" name="title" required maxlength="255"
                           value="<?= htmlspecialchars($task['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Tipo</label>
                    <select class="form-select" name="tipo">
                        <?php foreach (['audiencia' => 'Audiência', 'prazo' => 'Prazo', 'reuniao' => 'Reunião', 'protocolo' => 'Protocolo', 'diligencia' => 'Diligência', 'pesquisa' => 'Pesquisa', 'outros' => 'Outros'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($task['tipo'] ?? 'outros') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Prioridade</label>
                    <select class="form-select" name="prioridade">
                        <?php foreach (['baixa' => 'Baixa', 'media' => 'Média', 'alta' => 'Alta', 'urgente' => 'Urgente'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($task['prioridade'] ?? 'media') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach (['pendente' => 'Pendente', 'em_andamento' => 'Em Andamento', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($task['status'] ?? 'pendente') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Prazo</label>
                    <input type="date" class="form-control" name="prazo"
                           value="<?= htmlspecialchars($task['prazo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Horário</label>
                    <input type="time" class="form-control" name="hora" value="<?= htmlspecialchars($task['hora'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Local</label>
                    <input type="text" class="form-control" name="local" maxlength="255" value="<?= htmlspecialchars($task['local'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Fórum, sala, endereço ou link">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Responsável</label>
                    <select class="form-select" name="responsavel_id">
                        <option value="">Selecione...</option>
                        <?php foreach ($users ?? [] as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)($task['responsavel_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold">Processo</label>
                    <select class="form-select" name="case_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($cases ?? [] as $ca): ?>
                        <option value="<?= $ca['id'] ?>" <?= (int)($task['case_id'] ?? 0) === (int)$ca['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ca['numero_cnj'] ?: $ca['assunto'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Cliente</label>
                    <select class="form-select" name="client_id">
                        <option value="">Nenhum</option>
                        <?php foreach ($clients ?? [] as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= (int)($task['client_id'] ?? 0) === (int)$cl['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição</label>
                    <textarea class="form-control" name="descricao" rows="3"><?= htmlspecialchars($task['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="2"><?= htmlspecialchars($task['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salvar Alterações</button>
                <a href="/tasks" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
