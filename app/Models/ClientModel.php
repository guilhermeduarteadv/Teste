<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ClientModel extends Model
{
    protected $table = 'clients';

    public function search(string $term, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $like = "%{$term}%";
        $total = (int)$this->queryOne(
            "SELECT COUNT(*) AS cnt FROM clients WHERE deleted_at IS NULL AND (name LIKE ? OR cpf LIKE ? OR cnpj LIKE ? OR email LIKE ? OR phone LIKE ?)",
            [$like, $like, $like, $like, $like]
        )['cnt'];
        $items = $this->query(
            "SELECT * FROM clients WHERE deleted_at IS NULL AND (name LIKE ? OR cpf LIKE ? OR cnpj LIKE ? OR email LIKE ? OR phone LIKE ?)
             ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
            [$like, $like, $like, $like, $like]
        );
        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
        ];
    }

    public function findByCpf(string $cpf): ?array
    {
        return $this->queryOne(
            "SELECT * FROM clients WHERE cpf = ? AND deleted_at IS NULL LIMIT 1",
            [$cpf]
        );
    }

    public function findByCnpj(string $cnpj): ?array
    {
        return $this->queryOne(
            "SELECT * FROM clients WHERE cnpj = ? AND deleted_at IS NULL LIMIT 1",
            [$cnpj]
        );
    }

    public function getClientCases(int $clientId): array
    {
        return $this->query(
            "SELECT c.*, cc.tipo AS polo,
                    u.name AS responsavel_name
             FROM cases c
             JOIN case_clients cc ON c.id = cc.case_id
             LEFT JOIN users u ON c.responsavel_id = u.id
             WHERE cc.client_id = ? AND c.deleted_at IS NULL
             ORDER BY c.created_at DESC",
            [$clientId]
        );
    }

    public function getClientFinancial(int $clientId): array
    {
        return $this->query(
            "SELECT fe.*, c.numero_cnj
             FROM financial_entries fe
             LEFT JOIN cases c ON fe.case_id = c.id
             WHERE fe.client_id = ? AND fe.deleted_at IS NULL
             ORDER BY fe.vencimento DESC",
            [$clientId]
        );
    }

    public function getClientDocuments(int $clientId): array
    {
        return $this->query(
            "SELECT d.*, u.name AS uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.entity_type = 'client' AND d.entity_id = ? AND d.deleted_at IS NULL
             ORDER BY d.created_at DESC",
            [$clientId]
        );
    }

    public function setPortalAccess(int $clientId, bool $enable, string $password = ''): bool
    {
        if ($enable) {
            $token = bin2hex(random_bytes(32));
            $hashedPw = password_hash($password, PASSWORD_BCRYPT);
            return $this->execute(
                "UPDATE clients SET portal_access = 1, portal_password = ?, portal_token = ? WHERE id = ?",
                [$hashedPw, $token, $clientId]
            );
        }
        return $this->execute(
            "UPDATE clients SET portal_access = 0, portal_password = NULL, portal_token = NULL WHERE id = ?",
            [$clientId]
        );
    }

    public function findByPortalToken(string $token): ?array
    {
        return $this->queryOne(
            "SELECT * FROM clients WHERE portal_token = ? AND portal_access = 1 AND deleted_at IS NULL LIMIT 1",
            [$token]
        );
    }

    public function getActiveCount(): int
    {
        return $this->count(['status' => 'active']);
    }

    public function getForSelect(): array
    {
        return $this->query(
            "SELECT id, name, cpf, cnpj, tipo_pessoa FROM clients WHERE deleted_at IS NULL AND status = 'active' ORDER BY name ASC"
        );
    }
}
