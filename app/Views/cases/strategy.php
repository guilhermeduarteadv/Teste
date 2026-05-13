<?php
/**
 * View: Estratégia Processual
 * @var array $case
 * @var array $strategy
 * @var string $csrf_token
 */
?>
<div class="page-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-chess me-2 text-primary"></i>Estratégia Processual</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="/cases">Processos</a></li>
                    <li class="breadcrumb-item"><a href="/cases/<?= (int)$case['id'] ?>"><?= htmlspecialchars($case['numero_cnj'] ?: $case['assunto'], ENT_QUOTES, 'UTF-8') ?></a></li>
                    <li class="breadcrumb-item active">Estratégia</li>
                </ol>
            </nav>
        </div>
        <a href="/cases/<?= (int)$case['id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar
        </a>
    </div>

    <div class="alert alert-warning border-warning d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fas fa-lock fa-lg text-warning"></i>
        <span><strong>Conteúdo interno</strong> — não visível ao cliente. Todas as informações desta página são restritas ao escritório.</span>
    </div>

    <form action="/cases/<?= (int)$case['id'] ?>/strategy/save" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

        <!-- TESES -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-lightbulb text-primary"></i>
                <span>Teses Jurídicas</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tese Principal</label>
                        <textarea name="tese_principal" class="form-control" rows="5"
                                  placeholder="Descreva a tese principal que será sustentada no processo..."><?= htmlspecialchars($strategy['tese_principal'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tese Subsidiária</label>
                        <textarea name="tese_subsidiaria" class="form-control" rows="5"
                                  placeholder="Argumento subsidiário, caso a tese principal não seja acolhida..."><?= htmlspecialchars($strategy['tese_subsidiaria'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- RISCOS -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <span>Análise de Riscos</span>
            </div>
            <div class="card-body">
                <label class="form-label fw-semibold">Riscos e Pontos Fracos</label>
                <textarea name="riscos" class="form-control" rows="4"
                          placeholder="Identifique os riscos do processo: jurisprudência contrária, fatos desfavoráveis, lacunas probatórias..."><?= htmlspecialchars($strategy['riscos'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>

        <!-- PROVAS -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-balance-scale text-primary"></i>
                <span>Mapa de Provas</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-success">
                            <i class="fas fa-thumbs-up me-1"></i>Provas Favoráveis
                        </label>
                        <textarea name="provas_favoraveis" class="form-control" rows="4"
                                  placeholder="Liste as provas que suportam a tese do cliente..."><?= htmlspecialchars($strategy['provas_favoraveis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-danger">
                            <i class="fas fa-thumbs-down me-1"></i>Provas Desfavoráveis
                        </label>
                        <textarea name="provas_desfavoraveis" class="form-control" rows="4"
                                  placeholder="Provas que prejudicam a tese ou favorecem a parte contrária..."><?= htmlspecialchars($strategy['provas_desfavoraveis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACORDO -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-handshake text-success"></i>
                <span>Perspectiva de Acordo</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Valor Provável da Condenação</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" name="valor_provavel" class="form-control"
                                   placeholder="0,00"
                                   value="<?= !empty($strategy['valor_provavel']) ? number_format((float)$strategy['valor_provavel'], 2, ',', '.') : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Chance de Acordo (%)</label>
                        <div class="input-group">
                            <input type="number" name="chance_acordo" class="form-control" min="0" max="100"
                                   placeholder="0–100"
                                   value="<?= htmlspecialchars($strategy['chance_acordo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Valor Mínimo de Acordo</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" name="valor_minimo_acordo" class="form-control"
                                   placeholder="0,00"
                                   value="<?= !empty($strategy['valor_minimo_acordo']) ? number_format((float)$strategy['valor_minimo_acordo'], 2, ',', '.') : '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRÓXIMOS PASSOS -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="fas fa-tasks text-primary"></i>
                <span>Próximos Passos</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Plano de Ação</label>
                        <textarea name="proximos_passos" class="form-control" rows="4"
                                  placeholder="Descreva as ações estratégicas planejadas: petições, provas a produzir, diligências..."><?= htmlspecialchars($strategy['proximos_passos'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Observações Adicionais (Interno)</label>
                        <textarea name="observacoes" class="form-control" rows="3"
                                  placeholder="Notas internas complementares..."><?= htmlspecialchars($strategy['observacoes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-5">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Salvar Estratégia
            </button>
            <a href="/cases/<?= (int)$case['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>

    </form>

    <?php if (!empty($strategy['updated_at'])): ?>
    <p class="text-muted small">
        <i class="fas fa-clock me-1"></i>
        Última atualização: <?= date('d/m/Y H:i', strtotime($strategy['updated_at'])) ?>
    </p>
    <?php endif; ?>
</div>
