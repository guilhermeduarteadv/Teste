<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Logger;

class TribunalConnectionService
{
    private $db;
    private $key;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $config = require ROOT_PATH . '/config/app.php';
        $seed = (string)($config['key'] ?? '');
        if ($seed === '') {
            $seed = __FILE__ . php_uname();
        }
        $this->key = hash('sha256', $seed, true);
    }

    public function all(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT id, user_id, tribunal, sistema, username, oab_number, oab_state, has_password, has_cookies, status, last_test_at, last_sync_at, last_error, expires_at, created_at, updated_at FROM tribunal_connections WHERE user_id = ? ORDER BY tribunal, sistema");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function find(int $userId, string $tribunal, string $sistema): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tribunal_connections WHERE user_id = ? AND tribunal = ? AND sistema = ? LIMIT 1");
        $stmt->execute([$userId, strtolower($tribunal), strtolower($sistema)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getBestConnection(int $userId, string $tribunal = 'tjsp'): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tribunal_connections WHERE user_id = ? AND tribunal = ? AND status IN ('ativo','pendente') ORDER BY CASE WHEN has_cookies = 1 THEN 0 ELSE 1 END, updated_at DESC LIMIT 1");
        $stmt->execute([$userId, strtolower($tribunal)]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function save(int $userId, array $data): int
    {
        $tribunal = strtolower(trim((string)($data['tribunal'] ?? 'tjsp')));
        $sistema = strtolower(trim((string)($data['sistema'] ?? 'eproc')));
        $username = trim((string)($data['username'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $cookies = trim((string)($data['cookies'] ?? ''));
        $oabNumber = preg_replace('/\D/', '', (string)($data['oab_number'] ?? ''));
        $oabState = strtoupper(trim((string)($data['oab_state'] ?? '')));

        $existing = $this->find($userId, $tribunal, $sistema);
        $encryptedPassword = $password !== '' ? $this->encrypt($password) : ($existing['encrypted_password'] ?? null);
        $encryptedCookies = $cookies !== '' ? $this->encrypt($cookies) : ($existing['encrypted_cookies'] ?? null);
        $hasPassword = $encryptedPassword ? 1 : 0;
        $hasCookies = $encryptedCookies ? 1 : 0;
        $status = $hasCookies ? 'ativo' : ($hasPassword ? 'pendente' : 'pendente');

        if ($existing) {
            $stmt = $this->db->prepare("UPDATE tribunal_connections SET username=?, encrypted_password=?, encrypted_cookies=?, oab_number=?, oab_state=?, has_password=?, has_cookies=?, status=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$username, $encryptedPassword, $encryptedCookies, $oabNumber, $oabState, $hasPassword, $hasCookies, $status, (int)$existing['id']]);
            return (int)$existing['id'];
        }

        $stmt = $this->db->prepare("INSERT INTO tribunal_connections (user_id, tribunal, sistema, username, encrypted_password, encrypted_cookies, oab_number, oab_state, has_password, has_cookies, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$userId, $tribunal, $sistema, $username, $encryptedPassword, $encryptedCookies, $oabNumber, $oabState, $hasPassword, $hasCookies, $status]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $userId, int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tribunal_connections WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function revealCookies(array $connection): string
    {
        return !empty($connection['encrypted_cookies']) ? $this->decrypt((string)$connection['encrypted_cookies']) : '';
    }

    public function revealPassword(array $connection): string
    {
        return !empty($connection['encrypted_password']) ? $this->decrypt((string)$connection['encrypted_password']) : '';
    }

    public function markTestResult(int $id, bool $ok, ?string $error = null): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tribunal_connections SET status=?, last_error=?, last_test_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$ok ? 'ativo' : 'erro', $error, $id]);
        } catch (\Throwable $e) {
            Logger::error('Failed to update tribunal connection status: '.$e->getMessage());
        }
    }

    public function markSync(int $id, ?string $error = null): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tribunal_connections SET last_error=?, last_sync_at=NOW(), updated_at=NOW() WHERE id=?");
            $stmt->execute([$error, $id]);
        } catch (\Throwable $e) {
            Logger::error('Failed to update tribunal connection sync: '.$e->getMessage());
        }
    }

    private function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    private function decrypt(string $payload): string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 17) return '';
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }
}
