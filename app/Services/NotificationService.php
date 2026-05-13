<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Logger;

class NotificationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, string $title, string $message = '', string $type = 'info', string $priority = 'normal', string $entityType = '', int $entityId = 0): int
    {
        try {
            if (!$this->tableExists()) return 0;

            // Avoid duplicates by entity_type / entity_id / type
            if ($entityType && $entityId) {
                $stmt = $this->db->prepare(
                    "SELECT id FROM notifications
                     WHERE user_id = ? AND entity_type = ? AND entity_id = ? AND type = ?
                     AND read_at IS NULL AND deleted_at IS NULL
                     LIMIT 1"
                );
                $stmt->execute([$userId, $entityType, $entityId, $type]);
                if ($stmt->fetch()) return 0;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, title, message, type, priority, entity_type, entity_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $title,
                $message,
                $type,
                $priority,
                $entityType ?: null,
                $entityId ?: null,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            Logger::error('NotificationService::create failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function notifyAll(string $title, string $message = '', string $type = 'info', string $priority = 'normal', string $entityType = '', int $entityId = 0): void
    {
        try {
            if (!$this->tableExists()) return;
            $stmt = $this->db->prepare("SELECT id FROM users WHERE deleted_at IS NULL AND status = 'active'");
            $stmt->execute();
            $users = $stmt->fetchAll();
            foreach ($users as $u) {
                $this->create((int)$u['id'], $title, $message, $type, $priority, $entityType, $entityId);
            }
        } catch (\Throwable $e) {
            Logger::error('NotificationService::notifyAll failed: ' . $e->getMessage());
        }
    }

    public function getUnread(int $userId): array
    {
        try {
            if (!$this->tableExists()) return [];
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications
                 WHERE user_id = ? AND read_at IS NULL AND deleted_at IS NULL
                 ORDER BY created_at DESC LIMIT 50"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function countUnread(int $userId): int
    {
        try {
            if (!$this->tableExists()) return 0;
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM notifications
                 WHERE user_id = ? AND read_at IS NULL AND deleted_at IS NULL"
            );
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function markRead(int $id, int $userId): bool
    {
        try {
            if (!$this->tableExists()) return false;
            $stmt = $this->db->prepare(
                "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?"
            );
            return $stmt->execute([$id, $userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function markAllRead(int $userId): bool
    {
        try {
            if (!$this->tableExists()) return false;
            $stmt = $this->db->prepare(
                "UPDATE notifications SET read_at = NOW()
                 WHERE user_id = ? AND read_at IS NULL AND deleted_at IS NULL"
            );
            return $stmt->execute([$userId]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getAll(int $userId, int $limit = 50): array
    {
        try {
            if (!$this->tableExists()) return [];
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications
                 WHERE user_id = ? AND deleted_at IS NULL
                 ORDER BY created_at DESC LIMIT ?"
            );
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function tableExists(): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'notifications'");
            $stmt->execute();
            return (bool)$stmt->fetch();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function notifyOverdueTasks(): void
    {
        try {
            if (!$this->tableExists()) return;
            $stmt = $this->db->prepare(
                "SELECT t.id, t.title, t.responsible_id
                 FROM tasks t
                 WHERE t.status NOT IN ('concluida','cancelada')
                 AND t.due_date < CURDATE()
                 AND t.deleted_at IS NULL
                 AND t.responsible_id IS NOT NULL"
            );
            $stmt->execute();
            $tasks = $stmt->fetchAll();
            foreach ($tasks as $task) {
                $this->create(
                    (int)$task['responsible_id'],
                    'Tarefa vencida: ' . $task['title'],
                    'A tarefa está vencida.',
                    'warning',
                    'high',
                    'task',
                    (int)$task['id']
                );
            }
        } catch (\Throwable $e) {}
    }

    public function notifyUpcomingDeadlines(): void
    {
        try {
            if (!$this->tableExists()) return;
            $stmt = $this->db->prepare(
                "SELECT d.id, d.tipo, d.prazo_data, c.responsavel_id
                 FROM case_deadlines d
                 JOIN cases c ON c.id = d.case_id
                 WHERE d.prazo_data BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                 AND d.deleted_at IS NULL AND c.deleted_at IS NULL
                 AND c.responsavel_id IS NOT NULL"
            );
            $stmt->execute();
            $deadlines = $stmt->fetchAll();
            foreach ($deadlines as $dl) {
                $this->create(
                    (int)$dl['responsavel_id'],
                    'Prazo próximo: ' . ($dl['tipo'] ?? 'Prazo processual'),
                    'Vence em ' . $dl['prazo_data'],
                    'deadline',
                    'high',
                    'deadline',
                    (int)$dl['id']
                );
            }
        } catch (\Throwable $e) {}
    }
}
