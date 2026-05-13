<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Session;

class AuditService
{
    public static function log(string $module, string $action, string $description = '', ?string $entityType = null, ?int $entityId = null): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO audit_trail (user_id, module, entity_type, entity_id, action, description, ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                Session::get('user_id'), $module, $entityType, $entityId, $action, $description,
                $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000)
            ]);
        } catch (\Throwable $e) {}
    }
}
