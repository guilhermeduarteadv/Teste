<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Session;
use Core\Logger;
use App\Helpers\SecurityHelper;

class SystemLogService
{
    public static function log(
        string $action,
        string $module,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        try {
            $db = Database::getInstance();
            $userId = Session::get('user_id');
            $stmt = $db->prepare(
                "INSERT INTO system_logs (user_id, action, module, entity_type, entity_id, description, old_data, new_data, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $action,
                $module,
                $entityType,
                $entityId,
                $description,
                $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                SecurityHelper::getClientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to write system log: ' . $e->getMessage());
        }
    }

    public static function create(string $module, string $entity, int $id, string $description = ''): void
    {
        self::log('create', $module, $description ?: "Criação de {$entity} ID #{$id}", $entity, $id);
    }

    public static function update(string $module, string $entity, int $id, array $old, array $new, string $description = ''): void
    {
        self::log('update', $module, $description ?: "Edição de {$entity} ID #{$id}", $entity, $id, $old, $new);
    }

    public static function delete(string $module, string $entity, int $id, string $description = ''): void
    {
        self::log('delete', $module, $description ?: "Exclusão de {$entity} ID #{$id}", $entity, $id);
    }

    public static function upload(string $module, string $entity, int $id, string $filename): void
    {
        self::log('upload', $module, "Upload de documento: {$filename}", $entity, $id);
    }

    public static function download(string $module, string $entity, int $id, string $filename): void
    {
        self::log('download', $module, "Download de documento: {$filename}", $entity, $id);
    }
}
