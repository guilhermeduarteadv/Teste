<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class UserModel extends Model
{

    public function __construct()
    {
        parent::__construct();
        $this->ensureUserSchema();
    }

    private function ensureUserSchema(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS roles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    label VARCHAR(150) NOT NULL,
                    description TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $this->db->exec("
                INSERT IGNORE INTO roles (id, name, label, description, created_at) VALUES
                (1, 'admin', 'Administrador', 'Acesso total ao sistema', NOW()),
                (2, 'lawyer', 'Advogado', 'Usuário advogado', NOW()),
                (3, 'assistant', 'Assistente', 'Usuário assistente', NOW()),
                (4, 'client', 'Cliente', 'Acesso ao portal do cliente', NOW())
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL UNIQUE,
                    label VARCHAR(255) NULL,
                    module VARCHAR(100) NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS user_permissions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    permission_id INT NOT NULL,
                    granted TINYINT(1) DEFAULT 1,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL,
                    UNIQUE KEY uk_user_permission (user_id, permission_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        foreach ([
            'role_id' => 'INT NULL',
            'cargo' => 'VARCHAR(150) NULL',
            'oab_number' => 'VARCHAR(30) NULL',
            'oab_state' => 'VARCHAR(2) NULL',
            'phone' => 'VARCHAR(50) NULL',
            'last_login' => 'DATETIME NULL',
            'login_attempts' => 'INT DEFAULT 0',
            'blocked_until' => 'DATETIME NULL',
            'last_login_ip' => 'VARCHAR(100) NULL',
            'password_reset_token' => 'VARCHAR(255) NULL',
            'password_reset_expires' => 'DATETIME NULL',
            'deleted_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME NULL',
            'updated_at' => 'DATETIME NULL'
        ] as $column => $definition) {
            $this->addUserColumnIfMissing($column, $definition);
        }

        try {
            $this->db->exec("UPDATE users SET role_id = 1 WHERE role_id IS NULL OR role_id = 0");
        } catch (\Throwable $e) {}
    }

    private function addUserColumnIfMissing(string $column, string $definition): void
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        } catch (\Throwable $e) {}
    }


    protected $table = 'users';

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
        $this->ensureUserSchema();

        try {
            $this->execute("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
        } catch (\Throwable $e) {
            return;
        }

        try {
            $allPerms = $this->query("SELECT id, name FROM permissions");
        } catch (\Throwable $e) {
            return;
        }

        $selected = array_map('strval', $permissions);

        foreach ($allPerms as $perm) {
            $permId = (int)$perm['id'];
            $permName = (string)$perm['name'];

            if (in_array((string)$permId, $selected, true) || in_array($permName, $selected, true)) {
                try {
                    $this->execute(
                        "INSERT INTO user_permissions (user_id, permission_id, granted, created_at) VALUES (?, ?, 1, NOW())
                         ON DUPLICATE KEY UPDATE granted = 1, updated_at = NOW()",
                        [$userId, $permId]
                    );
                } catch (\Throwable $e) {}
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
        $this->ensureUserSchema();
        try {
            return $this->query("SELECT * FROM roles ORDER BY id");
        } catch (\Throwable $e) {
            return [
                ['id' => 1, 'name' => 'admin', 'label' => 'Administrador'],
                ['id' => 2, 'name' => 'lawyer', 'label' => 'Advogado'],
                ['id' => 3, 'name' => 'assistant', 'label' => 'Assistente'],
                ['id' => 4, 'name' => 'client', 'label' => 'Cliente'],
            ];
        }
    }

    public function getAllPermissions(): array
    {
        $this->ensureUserSchema();
        try {
            return $this->query("SELECT * FROM permissions ORDER BY module, name");
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function softDelete(int $id): bool
    {
        return $this->execute(
            "UPDATE users SET status = 'inactive', deleted_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL",
            [date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]
        );
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
