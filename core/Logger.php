<?php
declare(strict_types=1);

namespace Core;

class Logger
{
    private static $logPath = '';

    public static function init(string $logPath): void
    {
        self::$logPath = rtrim($logPath, '/');
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::write('DEBUG', $message, $context);
        }
    }

    public static function security(string $message, array $context = []): void
    {
        self::write('SECURITY', $message, $context, 'security');
    }

    public static function api(string $message, array $context = []): void
    {
        self::write('API', $message, $context, 'api');
    }

    public static function audit(string $message, array $context = []): void
    {
        self::write('AUDIT', $message, $context, 'audit');
    }

    private static function write(string $level, string $message, array $context = [], string $channel = 'app'): void
    {
        $logPath = self::$logPath ?: (defined('ROOT_PATH') ? ROOT_PATH . '/logs' : sys_get_temp_dir());
        $filename = $logPath . '/' . $channel . '-' . date('Y-m-d') . '.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf(
            "[%s] [%s] [IP: %s] [User: %s] %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            $ip,
            $userId,
            $message,
            $contextStr
        );
        error_log($line, 3, $filename);
    }
}
