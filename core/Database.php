<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    private static function connect(): void
    {
        $config = self::getConfig();
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        try {
            self::$instance = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            Logger::critical('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Falha na conexão com o banco de dados: ' . $e->getMessage());
        }
    }

    public static function testConnection(array $params): bool
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $params['host'],
                $params['port'],
                $params['database']
            );
            $pdo = new PDO($dsn, $params['username'], $params['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private static function getConfig(): array
    {
        if (empty(self::$config)) {
            $dbConfig = require ROOT_PATH . '/config/database.php';
            self::$config = $dbConfig['connections']['mysql'];
        }
        return self::$config;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
