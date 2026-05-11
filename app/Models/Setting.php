<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';

    public function get(string $key, $default = null)
    {
        $row = $this->queryOne("SELECT valor, tipo FROM settings WHERE chave = ? LIMIT 1", [$key]);
        if (!$row) return $default;
        return $this->cast($row['valor'], $row['tipo']);
    }

    public function set(string $key, $value): bool
    {
        $stmt = $this->db->prepare("UPDATE settings SET valor = ? WHERE chave = ?");
        return $stmt->execute([(string)$value, $key]);
    }

    public function getAll(): array
    {
        $rows = $this->query("SELECT chave, valor, tipo, descricao FROM settings ORDER BY chave ASC");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['chave']] = [
                'value'       => $this->cast($row['valor'], $row['tipo']),
                'raw'         => $row['valor'],
                'description' => $row['descricao'],
            ];
        }
        return $result;
    }

    public function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    private function cast($value, string $tipo)
    {
        if ($value === null) return null;
        switch ($tipo) {
            case 'integer': return (int)$value;
            case 'boolean': return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':    return json_decode($value, true);
            default:        return (string)$value;
        }
    }
}
