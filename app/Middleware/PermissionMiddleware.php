<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Session;
use Core\Logger;

class PermissionMiddleware
{
    private $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(): bool
    {
        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }

        // Admin has all permissions
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        $permissions = $user['permissions'] ?? [];
        if (!isset($permissions[$this->permission]) || !$permissions[$this->permission]) {
            Logger::security("Permission denied: {$this->permission}", [
                'user_id' => $user['id'] ?? 0,
                'uri'     => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            Session::flash('error', 'Você não tem permissão para realizar esta ação.');
            header('Location: /dashboard');
            exit;
        }

        return true;
    }
}
