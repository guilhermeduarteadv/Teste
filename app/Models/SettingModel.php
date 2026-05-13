<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class SettingModel extends Model
{
    protected $table = 'settings';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chave VARCHAR(150) NOT NULL UNIQUE,
                    valor LONGTEXT NULL,
                    tipo VARCHAR(50) DEFAULT 'string',
                    descricao TEXT NULL,
                    grupo VARCHAR(100) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    deleted_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {}

        $this->addColumnIfMissing('tipo', "VARCHAR(50) DEFAULT 'string'");
        $this->addColumnIfMissing('descricao', "TEXT NULL");
        $this->addColumnIfMissing('grupo', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('created_at', "DATETIME NULL");
        $this->addColumnIfMissing('updated_at', "DATETIME NULL");
        $this->addColumnIfMissing('deleted_at', "DATETIME NULL");

        try {
            $this->db->exec("UPDATE settings SET tipo = 'string' WHERE tipo IS NULL OR tipo = ''");
        } catch (\Throwable $e) {}
    }

    private function addColumnIfMissing(string $column, string $definition): void
    {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM settings LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt->fetch()) {
                $this->db->exec("ALTER TABLE settings ADD COLUMN {$column} {$definition}");
            }
        } catch (\Throwable $e) {}
    }

    public function get(string $key, $default = null)
    {
        $this->ensureSchema();

        try {
            $row = $this->queryOne("SELECT valor, COALESCE(tipo, 'string') AS tipo FROM settings WHERE chave = ? LIMIT 1", [$key]);
        } catch (\Throwable $e) {
            $row = $this->queryOne("SELECT valor FROM settings WHERE chave = ? LIMIT 1", [$key]);
            if ($row) {
                $row['tipo'] = 'string';
            }
        }

        if (!$row) {
            return $default;
        }

        return $this->castValue($row['valor'] ?? null, $row['tipo'] ?? 'string');
    }

    public function set(string $key, $value): bool
    {
        $this->ensureSchema();

        $existing = $this->queryOne("SELECT id FROM settings WHERE chave = ? LIMIT 1", [$key]);
        $stored = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $tipo = is_array($value) ? 'json' : 'string';

        if ($existing) {
            return $this->execute(
                "UPDATE settings SET valor = ?, tipo = ?, updated_at = NOW() WHERE chave = ?",
                [$stored, $tipo, $key]
            );
        }

        return $this->execute(
            "INSERT INTO settings (chave, valor, tipo, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
            [$key, $stored, $tipo]
        );
    }

    public function getAll(): array
    {
        $this->ensureSchema();

        try {
            $rows = $this->query("SELECT chave, valor, COALESCE(tipo, 'string') AS tipo, descricao FROM settings ORDER BY chave");
        } catch (\Throwable $e) {
            $rows = $this->query("SELECT chave, valor FROM settings ORDER BY chave");
            foreach ($rows as &$row) {
                $row['tipo'] = 'string';
                $row['descricao'] = '';
            }
            unset($row);
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['chave']] = [
                'value'     => $this->castValue($row['valor'] ?? null, $row['tipo'] ?? 'string'),
                'tipo'      => $row['tipo'] ?? 'string',
                'descricao' => $row['descricao'] ?? '',
            ];
        }

        return $settings;
    }

    public function getGroup(string $prefix): array
    {
        $this->ensureSchema();

        try {
            $rows = $this->query(
                "SELECT chave, valor, COALESCE(tipo, 'string') AS tipo FROM settings WHERE chave LIKE ? ORDER BY chave",
                [$prefix . '%']
            );
        } catch (\Throwable $e) {
            $rows = $this->query(
                "SELECT chave, valor FROM settings WHERE chave LIKE ? ORDER BY chave",
                [$prefix . '%']
            );
            foreach ($rows as &$row) {
                $row['tipo'] = 'string';
            }
            unset($row);
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['chave']] = $this->castValue($row['valor'] ?? null, $row['tipo'] ?? 'string');
        }

        return $settings;
    }

    private function castValue(?string $value, string $tipo)
    {
        if ($value === null) {
            return null;
        }

        switch ($tipo) {
            case 'integer':
                return (int)$value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                $decoded = json_decode($value, true);
                return $decoded === null ? [] : $decoded;
            default:
                return $value;
        }
    }

    public function bulkUpdate(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
