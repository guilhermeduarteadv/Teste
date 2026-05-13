<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class TimelineService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function rebuildCaseTimeline(int $caseId): array
    {
        $created = 0;

        $case = $this->fetchOne("SELECT * FROM cases WHERE id = ? LIMIT 1", [$caseId]);
        if (!$case) {
            return ['success' => false, 'message' => 'Processo não encontrado.', 'created' => 0];
        }

        if (!empty($case['data_distribuicao'])) {
            if ($this->upsertTimelineEvent([
                'case_id' => $caseId,
                'source' => 'case',
                'source_id' => $caseId,
                'event_date' => $case['data_distribuicao'] . ' 00:00:00',
                'title' => 'Distribuição do processo',
                'description' => 'Processo distribuído.',
                'client_description' => 'O processo foi distribuído no tribunal.',
                'event_type' => 'distribuicao',
                'progress_percent' => 10,
                'is_important' => 1,
                'visible_client' => 1,
            ])) {
                $created++;
            }
        }

        foreach ($this->fetchAll("SELECT * FROM case_movements WHERE case_id = ? ORDER BY data_movimento ASC, id ASC", [$caseId]) as $mov) {
            $descricao = (string)($mov['descricao'] ?? '');
            $date = $this->normalizeDateTime($mov['data_movimento'] ?? null);
            if (!$date) {
                continue;
            }

            $type = $this->classifyEvent($descricao);
            $important = $this->isImportant($descricao, $type) ? 1 : 0;
            $progress = $this->progressForType($type);

            if ($this->upsertTimelineEvent([
                'case_id' => $caseId,
                'source' => 'movement',
                'source_id' => (int)$mov['id'],
                'event_date' => $date,
                'title' => $this->titleForType($type, $descricao),
                'description' => $descricao,
                'client_description' => $this->clientTextForType($type, $descricao),
                'event_type' => $type,
                'progress_percent' => $progress,
                'is_important' => $important,
                'visible_client' => $important,
            ])) {
                $created++;
            }
        }

        foreach ($this->fetchAll("SELECT * FROM case_deadlines WHERE case_id = ? ORDER BY data_final ASC, id ASC", [$caseId]) as $deadline) {
            $date = $deadline['data_final'] ?? $deadline['data_final_calculada'] ?? null;
            if (empty($date)) {
                continue;
            }
            if ($this->upsertTimelineEvent([
                'case_id' => $caseId,
                'source' => 'deadline',
                'source_id' => (int)$deadline['id'],
                'event_date' => $date . ' 00:00:00',
                'title' => 'Prazo processual',
                'description' => (string)($deadline['descricao'] ?? 'Prazo processual'),
                'client_description' => 'Há um prazo processual cadastrado para acompanhamento interno.',
                'event_type' => 'prazo',
                'progress_percent' => 0,
                'is_important' => 0,
                'visible_client' => (int)($deadline['visivel_cliente'] ?? 0),
            ])) {
                $created++;
            }
        }

        foreach ($this->fetchAll("SELECT * FROM case_hearings WHERE case_id = ? ORDER BY data ASC, hora ASC, id ASC", [$caseId]) as $hearing) {
            if (empty($hearing['data'])) {
                continue;
            }
            $date = $hearing['data'] . ' ' . (!empty($hearing['hora']) ? $hearing['hora'] : '00:00:00');
            if ($this->upsertTimelineEvent([
                'case_id' => $caseId,
                'source' => 'hearing',
                'source_id' => (int)$hearing['id'],
                'event_date' => $date,
                'title' => 'Audiência',
                'description' => (string)($hearing['titulo'] ?? $hearing['tipo'] ?? 'Audiência'),
                'client_description' => 'Audiência designada no processo.',
                'event_type' => 'audiencia',
                'progress_percent' => 45,
                'is_important' => 1,
                'visible_client' => (int)($hearing['visivel_cliente'] ?? 1),
            ])) {
                $created++;
            }
        }

        foreach ($this->fetchAll("SELECT * FROM financial_entries WHERE case_id = ? ORDER BY COALESCE(data_pagamento, vencimento) ASC, id ASC", [$caseId]) as $fin) {
            $date = $fin['data_pagamento'] ?? $fin['vencimento'] ?? null;
            if (empty($date)) {
                continue;
            }
            $isRepasse = (($fin['tipo_operacao'] ?? '') === 'repasse_cliente');
            if ($this->upsertTimelineEvent([
                'case_id' => $caseId,
                'source' => 'financial',
                'source_id' => (int)$fin['id'],
                'event_date' => $date . ' 00:00:00',
                'title' => $isRepasse ? 'Repasse ao cliente' : 'Evento financeiro',
                'description' => (string)($fin['descricao'] ?? ''),
                'client_description' => $isRepasse ? 'Repasse financeiro vinculado ao processo.' : 'Lançamento financeiro vinculado ao processo.',
                'event_type' => $isRepasse ? 'repasse' : 'financeiro',
                'progress_percent' => $isRepasse ? 95 : 0,
                'is_important' => $isRepasse ? 1 : 0,
                'visible_client' => (int)($fin['visivel_cliente'] ?? 0),
            ])) {
                $created++;
            }
        }

        return ['success' => true, 'message' => 'Timeline reconstruída.', 'created' => $created];
    }

    public function getCaseTimeline(int $caseId, bool $clientOnly = false): array
    {
        $sql = "SELECT * FROM case_timeline WHERE case_id = ?";
        $params = [$caseId];

        if ($clientOnly) {
            $sql .= " AND visible_client = 1";
        }

        $sql .= " ORDER BY event_date ASC, id ASC";
        $events = $this->fetchAll($sql, $params);

        // No portal, se ainda não houver evento marcado como visível,
        // mostra os eventos importantes como fallback para não deixar a tela vazia.
        if ($clientOnly && empty($events)) {
            $events = $this->fetchAll(
                "SELECT * FROM case_timeline WHERE case_id = ? AND is_important = 1 ORDER BY event_date ASC, id ASC",
                [$caseId]
            );
        }

        return $events;
    }

    public function markImportant(int $timelineId, bool $important, bool $visibleClient = true): bool
    {
        $stmt = $this->db->prepare("UPDATE case_timeline SET is_important = ?, visible_client = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$important ? 1 : 0, $visibleClient ? 1 : 0, $timelineId]);
    }

    public function calculateCaseProgress(int $caseId): int
    {
        $row = $this->fetchOne("SELECT MAX(progress_percent) AS progress FROM case_timeline WHERE case_id = ?", [$caseId]);
        return (int)($row['progress'] ?? 0);
    }

    private function upsertTimelineEvent(array $event): bool
    {
        $hash = $this->eventHash($event);

        $exists = $this->fetchOne("SELECT id FROM case_timeline WHERE hash = ? LIMIT 1", [$hash]);
        if ($exists) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO case_timeline
            (case_id, source, source_id, event_date, title, description, client_description, event_type, progress_percent, is_important, visible_client, hash, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        return $stmt->execute([
            $event['case_id'],
            $event['source'] ?? 'manual',
            $event['source_id'] ?? null,
            $event['event_date'],
            $event['title'],
            $event['description'] ?? null,
            $event['client_description'] ?? null,
            $event['event_type'] ?? null,
            $event['progress_percent'] ?? 0,
            $event['is_important'] ?? 0,
            $event['visible_client'] ?? 1,
            $hash
        ]);
    }

    private function eventHash(array $event): string
    {
        return hash('sha256', implode('|', [
            $event['case_id'] ?? '',
            $event['source'] ?? '',
            $event['source_id'] ?? '',
            $event['event_date'] ?? '',
            $event['title'] ?? '',
            $event['description'] ?? ''
        ]));
    }

    private function normalizeDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $ts = strtotime((string)$value);
        if (!$ts) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function classifyEvent(string $text): string
    {
        $t = mb_strtolower($text, 'UTF-8');
        $map = [
            'sentenca' => ['sentença', 'sentenca'],
            'acordao' => ['acórdão', 'acordao'],
            'decisao' => ['decisão', 'decisao', 'liminar', 'tutela'],
            'despacho' => ['despacho'],
            'audiencia' => ['audiência', 'audiencia'],
            'pericia' => ['perícia', 'pericia', 'laudo'],
            'contestacao' => ['contestação', 'contestacao'],
            'replica' => ['réplica', 'replica'],
            'recurso' => ['recurso', 'apelação', 'apelacao', 'agravo', 'embargos'],
            'cumprimento' => ['cumprimento', 'execução', 'execucao'],
            'pagamento' => ['pagamento', 'alvará', 'alvara', 'levantamento'],
            'baixa' => ['baixa', 'arquivado', 'arquivamento', 'trânsito em julgado', 'transito em julgado'],
            'peticao' => ['petição', 'peticao', 'juntada'],
            'publicacao' => ['publicação', 'publicacao', 'disponibilizado', 'diário', 'diario'],
        ];

        foreach ($map as $type => $needles) {
            foreach ($needles as $needle) {
                if (strpos($t, $needle) !== false) {
                    return $type;
                }
            }
        }

        return 'movimentacao';
    }

    private function isImportant(string $text, string $type): bool
    {
        return in_array($type, [
            'sentenca', 'acordao', 'decisao', 'despacho', 'audiencia', 'pericia',
            'contestacao', 'replica', 'recurso', 'cumprimento', 'pagamento', 'baixa', 'publicacao'
        ], true);
    }

    private function progressForType(string $type): int
    {
        $progress = [
            'distribuicao' => 10,
            'contestacao' => 25,
            'replica' => 35,
            'audiencia' => 45,
            'pericia' => 55,
            'decisao' => 60,
            'despacho' => 50,
            'sentenca' => 70,
            'recurso' => 82,
            'acordao' => 88,
            'cumprimento' => 92,
            'pagamento' => 96,
            'repasse' => 98,
            'baixa' => 100,
        ];

        return $progress[$type] ?? 0;
    }

    private function titleForType(string $type, string $description): string
    {
        $titles = [
            'sentenca' => 'Sentença',
            'acordao' => 'Acórdão',
            'decisao' => 'Decisão',
            'despacho' => 'Despacho',
            'audiencia' => 'Audiência',
            'pericia' => 'Perícia / laudo',
            'contestacao' => 'Contestação',
            'replica' => 'Réplica',
            'recurso' => 'Recurso',
            'cumprimento' => 'Cumprimento / execução',
            'pagamento' => 'Pagamento / levantamento',
            'baixa' => 'Baixa / arquivamento',
            'peticao' => 'Peticionamento / juntada',
            'publicacao' => 'Publicação',
            'movimentacao' => 'Movimentação processual',
        ];

        return $titles[$type] ?? 'Movimentação processual';
    }

    private function clientTextForType(string $type, string $description): string
    {
        $texts = [
            'sentenca' => 'O juiz proferiu sentença no processo.',
            'acordao' => 'Houve julgamento pelo tribunal.',
            'decisao' => 'Foi proferida uma decisão relevante no processo.',
            'despacho' => 'O juiz determinou uma providência no processo.',
            'audiencia' => 'Foi designada ou realizada audiência.',
            'pericia' => 'Houve movimentação relacionada à perícia ou laudo.',
            'contestacao' => 'A parte contrária apresentou defesa.',
            'replica' => 'Foi apresentada manifestação sobre a defesa.',
            'recurso' => 'Houve movimentação relacionada a recurso.',
            'cumprimento' => 'O processo entrou em fase de cumprimento/execução.',
            'pagamento' => 'Houve movimentação relacionada a pagamento ou levantamento.',
            'baixa' => 'O processo teve movimentação de encerramento ou arquivamento.',
            'peticao' => 'Foi juntada petição ou documento no processo.',
            'publicacao' => 'Houve publicação no diário oficial.',
        ];

        return $texts[$type] ?? 'Houve nova movimentação processual.';
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
