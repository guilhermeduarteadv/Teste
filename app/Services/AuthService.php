<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Session;
use Core\Logger;
use App\Helpers\SecurityHelper;
use PDO;

class AuthService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function attempt(string $email, string $password, string $ip): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.name as role FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.email = ? AND u.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Logger::security('Login failed: user not found', ['email' => $email, 'ip' => $ip]);
            $this->logAction('login_failed', $email, $ip, null);
            return ['success' => false, 'message' => 'E-mail ou senha incorretos.'];
        }

        // Check if blocked
        if ($user['status'] === 'blocked') {
            Logger::security('Login blocked: account blocked', ['email' => $email, 'ip' => $ip]);
            return ['success' => false, 'message' => 'Conta bloqueada. Entre em contato com o administrador.'];
        }

        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'Conta inativa. Entre em contato com o administrador.'];
        }

        // Check temporary block
        if ($user['blocked_until'] && strtotime($user['blocked_until']) > time()) {
            $minutes = ceil((strtotime($user['blocked_until']) - time()) / 60);
            Logger::security('Login blocked: temporary block', ['email' => $email, 'ip' => $ip]);
            return ['success' => false, 'message' => "Conta temporariamente bloqueada. Tente novamente em {$minutes} minuto(s)."];
        }

        // Verify password
        if (!SecurityHelper::verifyPassword($password, $user['password'])) {
            $this->incrementLoginAttempts((int)$user['id']);
            $this->logAction('login_failed', $email, $ip, (int)$user['id']);
            Logger::security('Login failed: wrong password', ['email' => $email, 'ip' => $ip]);

            $remaining = max(0, (int)getenv('MAX_LOGIN_ATTEMPTS') ?: 5 - ($user['login_attempts'] + 1));
            $msg = 'E-mail ou senha incorretos.';
            if ($remaining > 0 && $remaining <= 2) {
                $msg .= " {$remaining} tentativa(s) restante(s).";
            }
            return ['success' => false, 'message' => $msg];
        }

        // Success
        $this->resetLoginAttempts((int)$user['id'], $ip);
        $permissions = $this->loadPermissions((int)$user['id'], $user['role'] ?? '');
        $this->startSession($user, $permissions);
        $this->logAction('login', $email, $ip, (int)$user['id']);
        Logger::audit("Login bem-sucedido: {$email}", ['ip' => $ip]);

        return ['success' => true, 'message' => 'Login realizado com sucesso.', 'user' => $user];
    }

    public function logout(int $userId, string $email): void
    {
        Logger::audit("Logout: {$email}", ['user_id' => $userId]);
        $this->logAction('logout', $email, SecurityHelper::getClientIp(), $userId);
        Session::destroy();
    }

    public function generatePasswordResetToken(string $email): array
    {
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE email = ? AND deleted_at IS NULL AND status = 'active' LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Return success anyway to prevent email enumeration
            return ['success' => true, 'message' => 'Se o e-mail estiver cadastrado, você receberá as instruções em breve.'];
        }

        $token = SecurityHelper::generateToken(32);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        Logger::audit("Password reset token generated for: {$email}");

        return [
            'success' => true,
            'message' => 'Se o e-mail estiver cadastrado, você receberá as instruções em breve.',
            'token'   => $token,
            'user'    => $user,
        ];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, email FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Token inválido ou expirado.'];
        }

        $errors = SecurityHelper::validatePasswordStrength($newPassword);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode(' ', $errors)];
        }

        $hash = SecurityHelper::hashPassword($newPassword);
        $stmt = $this->db->prepare(
            "UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL, updated_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$hash, $user['id']]);

        Logger::audit("Password reset successful", ['user_id' => $user['id'], 'email' => $user['email']]);
        $this->logAction('password_reset', $user['email'], SecurityHelper::getClientIp(), (int)$user['id']);

        return ['success' => true, 'message' => 'Senha redefinida com sucesso. Faça login com sua nova senha.'];
    }

    private function loadPermissions(int $userId, string $role): array
    {
        if ($role === 'admin') {
            $stmt = $this->db->prepare("SELECT name FROM permissions");
            $stmt->execute();
            $all = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return array_fill_keys($all, true);
        }

        $stmt = $this->db->prepare(
            "SELECT p.name, up.granted FROM user_permissions up
             JOIN permissions p ON up.permission_id = p.id
             WHERE up.user_id = ?"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $permissions = [];
        foreach ($rows as $row) {
            $permissions[$row['name']] = (bool)$row['granted'];
        }
        return $permissions;
    }

    private function startSession(array $user, array $permissions): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user', [
            'id'          => $user['id'],
            'name'        => $user['name'],
            'email'       => $user['email'],
            'role'        => $user['role'],
            'cargo'       => $user['cargo'],
            'avatar'      => $user['avatar'],
            'status'      => $user['status'],
            'permissions' => $permissions,
        ]);
        Session::set('_last_activity', time());
    }

    private function incrementLoginAttempts(int $userId): void
    {
        $maxAttempts = (int)getenv('MAX_LOGIN_ATTEMPTS') ?: 5;
        $blockMinutes = (int)getenv('LOGIN_BLOCK_MINUTES') ?: 15;

        $stmt = $this->db->prepare("SELECT login_attempts FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $current = (int)$stmt->fetchColumn();

        if ($current + 1 >= $maxAttempts) {
            $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$blockMinutes} minutes"));
            $stmt = $this->db->prepare("UPDATE users SET login_attempts = login_attempts + 1, blocked_until = ? WHERE id = ?");
            $stmt->execute([$blockedUntil, $userId]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
            $stmt->execute([$userId]);
        }
    }

    private function resetLoginAttempts(int $userId, string $ip): void
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET login_attempts = 0, blocked_until = NULL, last_login = NOW(), last_login_ip = ? WHERE id = ?"
        );
        $stmt->execute([$ip, $userId]);
    }

    private function logAction(string $action, string $email, string $ip, ?int $userId): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO system_logs (user_id, action, module, description, ip_address, user_agent, created_at)
                 VALUES (?, ?, 'auth', ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $action,
                "Ação: {$action} | Email: {$email}",
                $ip,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to log auth action: ' . $e->getMessage());
        }
    }
}
