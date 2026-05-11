<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class UserModel extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne(
            "SELECT u.*, r.name AS role FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.email = ? AND u.deleted_at IS NULL LIMIT 1",
            [$email]
        );
    }

    public function findByResetToken(string $token): ?array
    {
        return $this->queryOne(
            "SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND deleted_at IS NULL LIMIT 1",
            [$token]
        );
    }

    public function getAllWithRoles(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $items = $this->query(
            "SELECT u.*, r.label AS role_label, r.name AS role_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.deleted_at IS NULL
             ORDER BY u.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    public function getUserPermissions(int $userId): array
    {
        $rows = $this->query(
            "SELECT p.name, up.granted
             FROM user_permissions up
             JOIN permissions p ON up.permission_id = p.id
             WHERE up.user_id = ? AND up.granted = 1",
            [$userId]
        );
        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row['name']] = true;
        }
        return $permissions;
    }

    public function setPermissions(int $userId, array $permissions): void
    {
        $this->execute("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
        $allPerms = $this->query("SELECT id, name FROM permissions");
        foreach ($allPerms as $perm) {
            if (in_array($perm['name'], $permissions)) {
                $this->execute(
                    "INSERT INTO user_permissions (user_id, permission_id, granted) VALUES (?, ?, 1)",
                    [$userId, $perm['id']]
                );
            }
        }
    }

    public function incrementLoginAttempts(int $userId): void
    {
        $this->execute(
            "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?",
            [$userId]
        );
    }

    public function blockUser(int $userId, int $minutes): void
    {
        $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        $this->execute(
            "UPDATE users SET blocked_until = ?, status = 'blocked' WHERE id = ?",
            [$blockedUntil, $userId]
        );
    }

    public function resetLoginAttempts(int $userId): void
    {
        $this->execute(
            "UPDATE users SET login_attempts = 0, blocked_until = NULL, status = 'active' WHERE id = ?",
            [$userId]
        );
    }

    public function updateLastLogin(int $userId, string $ip): void
    {
        $this->execute(
            "UPDATE users SET last_login = NOW(), last_login_ip = ?, login_attempts = 0 WHERE id = ?",
            [$ip, $userId]
        );
    }

    public function isBlocked(array $user): bool
    {
        if ($user['status'] === 'blocked' && !empty($user['blocked_until'])) {
            return strtotime($user['blocked_until']) > time();
        }
        return false;
    }

    public function setPasswordResetToken(int $userId, string $token): void
    {
        $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
        $this->execute(
            "UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?",
            [$token, $expires, $userId]
        );
    }

    public function clearPasswordResetToken(int $userId): void
    {
        $this->execute(
            "UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?",
            [$userId]
        );
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->update($userId, ['password' => $hashedPassword]);
    }

    public function getRoles(): array
    {
        return $this->query("SELECT * FROM roles ORDER BY id");
    }

    public function getAllPermissions(): array
    {
        return $this->query("SELECT * FROM permissions ORDER BY module, name");
    }

    public function findByIdWithRole(int $id): ?array
    {
        return $this->queryOne(
            "SELECT u.*, r.name AS role, r.label AS role_label
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.id = ? AND u.deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }
}
