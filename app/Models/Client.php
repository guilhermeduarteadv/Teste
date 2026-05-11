<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Client extends Model
{
    protected string $table = 'clients';

    public function findAllPaginated(int $page, int $perPage, array $filters = []): array
    {
        $where = 'c.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['search'])) {
            $where .= ' AND (c.name LIKE ? OR c.cpf LIKE ? OR c.cnpj LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if (!empty($filters['status'])) {
            $where .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['tipo_pessoa'])) {
            $where .= ' AND c.tipo_pessoa = ?';
            $params[] = $filters['tipo_pessoa'];
        }

        $countSql = "SELECT COUNT(*) FROM clients c WHERE {$where}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT c.*, (SELECT COUNT(*) FROM case_clients cc WHERE cc.client_id = c.id) as total_cases FROM clients c WHERE {$where} ORDER BY c.name ASC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    public function getCases(int $clientId): array
    {
        return $this->query(
            "SELECT c.*, cc.tipo as participacao FROM cases c
             JOIN case_clients cc ON cc.case_id = c.id
             WHERE cc.client_id = ? AND c.deleted_at IS NULL
             ORDER BY c.created_at DESC",
            [$clientId]
        );
    }

    public function getFinancial(int $clientId): array
    {
        return $this->query(
            "SELECT fe.*, ca.numero_cnj FROM financial_entries fe
             LEFT JOIN cases ca ON fe.case_id = ca.id
             WHERE fe.client_id = ? AND fe.deleted_at IS NULL
             ORDER BY fe.vencimento DESC",
            [$clientId]
        );
    }

    public function getDocuments(int $clientId): array
    {
        return $this->query(
            "SELECT * FROM documents WHERE entity_type = 'client' AND entity_id = ? AND deleted_at IS NULL ORDER BY created_at DESC",
            [$clientId]
        );
    }

    public function getTasks(int $clientId): array
    {
        return $this->query(
            "SELECT t.*, u.name as responsavel_name FROM tasks t LEFT JOIN users u ON t.responsavel_id = u.id WHERE t.client_id = ? AND t.deleted_at IS NULL ORDER BY t.prazo ASC",
            [$clientId]
        );
    }

    public function documentExists(string $field, string $value, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM clients WHERE {$field} = ? AND deleted_at IS NULL";
        $params = [$value];
        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function enablePortalAccess(int $clientId, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("UPDATE clients SET portal_access = 1, portal_password = ?, portal_token = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hash, $token, $clientId]);
    }

    public function disablePortalAccess(int $clientId): bool
    {
        $stmt = $this->db->prepare("UPDATE clients SET portal_access = 0, portal_password = NULL, portal_token = NULL, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$clientId]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne("SELECT * FROM clients WHERE email = ? AND deleted_at IS NULL LIMIT 1", [$email]);
    }

    public function findByPortalToken(string $token): ?array
    {
        return $this->queryOne("SELECT * FROM clients WHERE portal_token = ? AND portal_access = 1 AND deleted_at IS NULL LIMIT 1", [$token]);
    }

    public function getStats(): array
    {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM clients WHERE deleted_at IS NULL")->fetchColumn();
        $active = (int)$this->db->query("SELECT COUNT(*) FROM clients WHERE status='active' AND deleted_at IS NULL")->fetchColumn();
        return ['total' => $total, 'active' => $active, 'inactive' => $total - $active];
    }
}
