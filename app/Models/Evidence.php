<?php
declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Evidence extends Model
{
    protected $table = 'case_evidence';

    public function findByCase(int $caseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.name AS created_by_name, d.original_name AS document_name
             FROM case_evidence e
             LEFT JOIN users u ON u.id = e.created_by
             LEFT JOIN documents d ON d.id = e.document_id
             WHERE e.case_id = ? AND e.deleted_at IS NULL
             ORDER BY e.created_at DESC"
        );
        $stmt->execute([$caseId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByType(int $caseId, string $type): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, u.name AS created_by_name
             FROM case_evidence e
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.case_id = ? AND e.evidence_type = ? AND e.deleted_at IS NULL
             ORDER BY e.created_at DESC"
        );
        $stmt->execute([$caseId, $type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
