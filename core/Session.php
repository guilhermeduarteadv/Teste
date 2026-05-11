<?php
declare(strict_types=1);

namespace Core;

class Session
{
    private static bool $started = false;

    public static function start(array $config = []): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $name = $config['name'] ?? 'juriscontrol_session';
        $lifetime = $config['lifetime'] ?? 120;
        session_name($name);
        // samesite in array form requires PHP 7.3+; use ini_set for compatibility
        $cookiePath = (defined('APP_BASE_PATH') && APP_BASE_PATH !== '') ? APP_BASE_PATH . '/' : '/';
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => $lifetime * 60,
                'path'     => $cookiePath,
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params($lifetime * 60, $cookiePath, '', isset($_SERVER['HTTPS']), true);
        }
        session_start();
        self::$started = true;
        self::regenerateIfNeeded();
    }

    private static function regenerateIfNeeded(): void
    {
        if (!isset($_SESSION['_last_regeneration'])) {
            self::regenerate();
        } elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
            self::regenerate();
        }
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$started = false;
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::has('user_email');
    }

    public static function csrfToken(): string
    {
        if (!self::has('_csrf_token')) {
            self::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('_csrf_token');
    }

    public static function verifyCsrf(string $token): bool
    {
        $stored = self::get('_csrf_token', '');
        return hash_equals($stored, $token);
    }
}
