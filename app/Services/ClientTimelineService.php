<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class ClientTimelineService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function addEvent(
        int    $clientId,
        string $title,
        string $description,
        string $source,
        int    $sourceId,
        int    $visibleClient,
        int    $userId
    ): int {
        try {
            $st = $this->db->prepare(
                "INSERT INTO client_timeline
                 (client_id, event_date, title, description, source, source_id,
                  visible_client, created_by, created_at, updated_at)
                 VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $st->execute([
                $clientId,
                $title,
                $description,
                $source,
                $sourceId > 0 ? $sourceId : null,
                $visibleClient,
                $userId,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getEvents(int $clientId, bool $includeInternal = true): array
    {
        try {
            $sql = "SELECT ct.*, u.name AS author_name
                    FROM client_timeline ct
                    LEFT JOIN users u ON u.id = ct.created_by
                    WHERE ct.client_id = ? AND ct.deleted_at IS NULL";

            if (!$includeInternal) {
                $sql .= " AND ct.visible_client = 1";
            }

            $sql .= " ORDER BY ct.event_date DESC, ct.id DESC";

            $st = $this->db->prepare($sql);
            $st->execute([$clientId]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function recordClientCreated(int $clientId, int $userId): void
    {
        $this->addEvent(
            $clientId,
            'Cliente cadastrado no sistema',
            'Registro inicial criado.',
            'system',
            0,
            1,
            $userId
        );
    }

    public function recordDocumentUploaded(int $clientId, int $documentId, string $docName, int $userId): void
    {
        $this->addEvent(
            $clientId,
            'Documento enviado',
            'Documento "' . $docName . '" adicionado ao cadastro.',
            'document',
            $documentId,
            1,
            $userId
        );
    }

    public function recordTaskCreated(int $clientId, int $taskId, string $taskTitle, int $userId): void
    {
        $this->addEvent(
            $clientId,
            'Tarefa criada',
            'Nova tarefa: "' . $taskTitle . '".',
            'task',
            $taskId,
            0,
            $userId
        );
    }

    public function recordPaymentReceived(int $clientId, int $financialId, float $value, int $userId): void
    {
        $formatted = 'R$ ' . number_format($value, 2, ',', '.');
        $this->addEvent(
            $clientId,
            'Pagamento registrado',
            'Recebimento de ' . $formatted . ' registrado.',
            'financial',
            $financialId,
            1,
            $userId
        );
    }
}
