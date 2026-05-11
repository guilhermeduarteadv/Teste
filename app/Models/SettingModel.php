<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class SettingModel extends Model
{
    protected $table = 'settings';

    public function get(string $key, $default = null)
    {
        $row = $this->queryOne("SELECT valor, tipo FROM settings WHERE chave = ? LIMIT 1", [$key]);
        if (!$row) return $default;
        return $this->castValue($row['valor'], $row['tipo']);
    }

    public function set(string $key, $value): bool
    {
        $existing = $this->queryOne("SELECT id FROM settings WHERE chave = ? LIMIT 1", [$key]);
        if ($existing) {
            return $this->execute(
                "UPDATE settings SET valor = ?, updated_at = NOW() WHERE chave = ?",
                [is_array($value) ? json_encode($value) : (string)$value, $key]
            );
        }
        return $this->execute(
            "INSERT INTO settings (chave, valor) VALUES (?, ?)",
            [$key, is_array($value) ? json_encode($value) : (string)$value]
        );
    }

    public function getAll(): array
    {
        $rows = $this->query("SELECT chave, valor, tipo, descricao FROM settings ORDER BY chave");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['chave']] = [
                'value'       => $this->castValue($row['valor'], $row['tipo']),
                'tipo'        => $row['tipo'],
                'descricao'   => $row['descricao'],
            ];
        }
        return $settings;
    }

    public function getGroup(string $prefix): array
    {
        $rows = $this->query(
            "SELECT chave, valor, tipo FROM settings WHERE chave LIKE ? ORDER BY chave",
            [$prefix . '%']
        );
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['chave']] = $this->castValue($row['valor'], $row['tipo']);
        }
        return $settings;
    }

    private function castValue(?string $value, string $tipo)
    {
        if ($value === null) return null;
        switch ($tipo) {
            case 'integer': return (int)$value;
            case 'boolean': return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':    return json_decode($value, true);
            default:        return $value;
        }
    }

    public function bulkUpdate(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
