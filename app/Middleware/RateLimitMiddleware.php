<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Session;
use Core\Logger;

class RateLimitMiddleware
{
    private $maxAttempts;
    private $windowSeconds;

    public function __construct(int $maxAttempts = 60, int $windowSeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = '_rate_limit_' . md5($ip . $_SERVER['REQUEST_URI'] ?? '');
        $data = Session::get($key, ['count' => 0, 'reset' => time() + $this->windowSeconds]);

        if (time() > $data['reset']) {
            $data = ['count' => 0, 'reset' => time() + $this->windowSeconds];
        }

        $data['count']++;
        Session::set($key, $data);

        if ($data['count'] > $this->maxAttempts) {
            Logger::security('Rate limit exceeded', ['ip' => $ip, 'uri' => $_SERVER['REQUEST_URI'] ?? '']);
            http_response_code(429);
            echo json_encode(['error' => 'Muitas requisições. Tente novamente em alguns segundos.']);
            exit;
        }

        return true;
    }
}
