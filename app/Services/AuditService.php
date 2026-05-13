<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Session;

class AuditService
{
    /**
     * Legacy static method — kept for backward compatibility.
     */
    public static function log(string $module, string $action, string $description = '', ?string $entityType = null, ?int $entityId = null): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO audit_trail (user_id, module, entity_type, entity_id, action, description, ip, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                Session::get('user_id'),
                $module,
                $entityType,
                $entityId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Instance-style log with full parameters (2.16 spec).
     *
     * @param int    $userId
     * @param string $module
     * @param string $action
     * @param string $entityType
     * @param int    $entityId
     * @param string $description
     */
    public static function logFull(int $userId, string $module, string $action, string $entityType, int $entityId, string $description): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "INSERT INTO audit_trail (user_id, module, entity_type, entity_id, action, description, ip, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $module,
                $entityType,
                $entityId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Log access to a document (2.16 spec).
     */
    public static function logDocumentAccess(int $documentId, int $userId, int $clientId, string $action): void
    {
        try {
            $db = Database::getInstance();
            // Ensure document_access_logs table exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS `document_access_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `document_id` INT NOT NULL,
                    `user_id` INT NOT NULL,
                    `client_id` INT NOT NULL,
                    `action` VARCHAR(50) NOT NULL,
                    `ip` VARCHAR(100) NULL,
                    `user_agent` VARCHAR(500) NULL,
                    `created_at` DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $stmt = $db->prepare(
                "INSERT INTO document_access_logs (document_id, user_id, client_id, action, ip, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $documentId,
                $userId,
                $clientId,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {}
    }

    /**
     * Export all data for a client as JSON, save to storage/exports/, register in data_exports.
     *
     * @return array ['success' => bool, 'file_path' => string, 'message' => string]
     */
    public static function exportClientData(int $clientId, int $userId): array
    {
        try {
            $db = Database::getInstance();

            // Gather client data
            $client = $db->prepare("SELECT * FROM clients WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $client->execute([$clientId]);
            $clientData = $client->fetch(\PDO::FETCH_ASSOC);
            if (!$clientData) {
                return ['success' => false, 'message' => 'Cliente não encontrado.', 'file_path' => ''];
            }

            // Mask sensitive fields for LGPD export
            unset($clientData['portal_password']);

            $export = ['client' => $clientData];

            // Cases
            try {
                $stmt = $db->prepare(
                    "SELECT c.* FROM cases c
                     INNER JOIN case_clients cc ON cc.case_id = c.id
                     WHERE cc.client_id = ? AND c.deleted_at IS NULL"
                );
                $stmt->execute([$clientId]);
                $export['cases'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $export['cases'] = [];
            }

            // Financial entries
            try {
                $stmt = $db->prepare(
                    "SELECT * FROM financial_entries WHERE client_id = ? AND deleted_at IS NULL ORDER BY vencimento DESC"
                );
                $stmt->execute([$clientId]);
                $export['financial_entries'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $export['financial_entries'] = [];
            }

            // Documents
            try {
                $stmt = $db->prepare(
                    "SELECT id, title, filename, original_name, mime_type, size, categoria, entity_type, visivel_cliente, created_at
                     FROM documents WHERE entity_id = ? AND entity_type = 'client' AND deleted_at IS NULL"
                );
                $stmt->execute([$clientId]);
                $export['documents'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $export['documents'] = [];
            }

            // Audit trail for this client
            try {
                $stmt = $db->prepare(
                    "SELECT at.*, u.name AS user_name
                     FROM audit_trail at
                     LEFT JOIN users u ON u.id = at.user_id
                     WHERE (at.entity_type = 'client' AND at.entity_id = ?)
                        OR at.description LIKE ?
                     ORDER BY at.created_at DESC
                     LIMIT 500"
                );
                $stmt->execute([$clientId, '%' . $clientId . '%']);
                $export['audit_trail'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $export['audit_trail'] = [];
            }

            $export['exported_at'] = date('Y-m-d H:i:s');
            $export['exported_by'] = $userId;

            // Save file
            $dir = ROOT_PATH . '/storage/exports';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $filename = 'client_export_' . $clientId . '_' . date('Ymd_His') . '.json';
            $filePath = $dir . '/' . $filename;
            file_put_contents($filePath, json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $relativePath = 'storage/exports/' . $filename;

            // Register in data_exports
            try {
                $db->exec("
                    CREATE TABLE IF NOT EXISTS `data_exports` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `client_id` INT NOT NULL,
                        `file_path` VARCHAR(500) NOT NULL,
                        `exported_by` INT NOT NULL,
                        `created_at` DATETIME NOT NULL,
                        `deleted_at` DATETIME NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                $stmt = $db->prepare(
                    "INSERT INTO data_exports (client_id, file_path, exported_by, created_at) VALUES (?, ?, ?, NOW())"
                );
                $stmt->execute([$clientId, $relativePath, $userId]);
            } catch (\Throwable $e) {}

            // Log the action
            self::logFull($userId, 'lgpd', 'export_client_data', 'client', $clientId, 'Exportação LGPD de dados do cliente #' . $clientId);

            return ['success' => true, 'file_path' => $relativePath, 'message' => 'Dados exportados com sucesso.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Erro ao exportar: ' . $e->getMessage(), 'file_path' => ''];
        }
    }
}
