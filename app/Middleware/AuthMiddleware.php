<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Session;
use Core\Logger;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Sua sessão expirou. Faça login novamente.');
            Logger::security('Acesso não autenticado bloqueado', ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
            header('Location: ' . ((defined('APP_BASE_PATH') && APP_BASE_PATH !== '') ? APP_BASE_PATH : '') . '/login');
            exit;
        }

        $user = Session::get('user');
        if (!$user || ($user['status'] ?? '') !== 'active') {
            Session::destroy();
            Session::flash('error', 'Conta inativa ou bloqueada. Entre em contato com o administrador.');
            header('Location: ' . ((defined('APP_BASE_PATH') && APP_BASE_PATH !== '') ? APP_BASE_PATH : '') . '/login');
            exit;
        }

        // Check session timeout
        $lastActivity = Session::get('_last_activity', time());
        $timeout = (int)($_ENV['SESSION_LIFETIME'] ?? 120) * 60;
        if (time() - $lastActivity > $timeout) {
            Session::destroy();
            header('Location: ' . ((defined('APP_BASE_PATH') && APP_BASE_PATH !== '') ? APP_BASE_PATH : '') . '/login?expired=1');
            exit;
        }
        Session::set('_last_activity', time());

        return true;
    }
}
