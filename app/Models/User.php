<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;
use Core\Database;
use App\Helpers\SecurityHelper;
use PDO;

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne(
            "SELECT u.*, r.name as role FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.deleted_at IS NULL LIMIT 1",
            [strtolower(trim($email))]
        );
    }

    public function findWithRole(int $id): ?array
    {
        return $this->queryOne(
            "SELECT u.*, r.name as role, r.label as role_label FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }

    public function findAllWithRoles(array $conditions = []): array
    {
        $where = 'u.deleted_at IS NULL';
        $params = [];
        if (!empty($conditions['status'])) {
            $where .= ' AND u.status = ?';
            $params[] = $conditions['status'];
        }
        if (!empty($conditions['search'])) {
            $where .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $conditions['search'] . '%';
            $params[] = '%' . $conditions['search'] . '%';
        }
        return $this->query(
            "SELECT u.id, u.name, u.email, u.cargo, u.status, u.last_login, u.created_at, r.name as role, r.label as role_label
             FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE {$where} ORDER BY u.name ASC",
            $params
        );
    }

    public function create(array $data): int
    {
        if (!empty($data['password'])) {
            $data['password'] = SecurityHelper::hashPassword($data['password']);
        }
        return $this->insert($data);
    }

    public function updateProfile(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $data['password'] = SecurityHelper::hashPassword($data['password']);
        } else {
            unset($data['password']);
        }
        return $this->update($id, $data);
    }

    public function getUserPermissions(int $userId): array
    {
        $rows = $this->query(
            "SELECT p.name, up.granted FROM user_permissions up JOIN permissions p ON up.permission_id = p.id WHERE up.user_id = ?",
            [$userId]
        );
        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row['name']] = (bool)$row['granted'];
        }
        return $permissions;
    }

    public function setPermissions(int $userId, array $permissions): void
    {
        $this->db->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);
        foreach ($permissions as $permissionId => $granted) {
            if ($granted) {
                $stmt = $this->db->prepare(
                    "INSERT INTO user_permissions (user_id, permission_id, granted) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE granted = 1"
                );
                $stmt->execute([$userId, $permissionId]);
            }
        }
    }

    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ? AND deleted_at IS NULL";
        $params = [strtolower(trim($email))];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
