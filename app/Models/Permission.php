<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    public function getGroupedByModule(): array
    {
        $rows = $this->query("SELECT * FROM permissions ORDER BY module ASC, label ASC");
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }
        return $grouped;
    }

    public function getAllWithUserStatus(int $userId): array
    {
        return $this->query(
            "SELECT p.*, COALESCE(up.granted, 0) as granted FROM permissions p LEFT JOIN user_permissions up ON up.permission_id = p.id AND up.user_id = ? ORDER BY p.module ASC, p.label ASC",
            [$userId]
        );
    }
}
