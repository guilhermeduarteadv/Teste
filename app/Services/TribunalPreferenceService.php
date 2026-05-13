<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class TribunalPreferenceService
{
    private $db;

    public function __construct()
    {
        (new SchemaGuardService())->ensureV49Schema();
        $this->db = Database::getInstance();
    }

    public function allKnown(): array
    {
        return [
            'TJAC','TJAL','TJAP','TJAM','TJBA','TJCE','TJDF','TJES','TJGO','TJMA','TJMT','TJMS','TJMG','TJPA',
            'TJPB','TJPR','TJPE','TJPI','TJRJ','TJRN','TJRS','TJRO','TJRR','TJSC','TJSP','TJSE','TJTO',
            'TRF1','TRF2','TRF3','TRF4','TRF5','TRF6','TRT1','TRT2','TRT3','TRT4','TRT5','TRT6','TRT7','TRT8',
            'TRT9','TRT10','TRT11','TRT12','TRT13','TRT14','TRT15','TRT16','TRT17','TRT18','TRT19','TRT20','TRT21',
            'TRT22','TRT23','TRT24','STJ','STF','TST','TSE','STM'
        ];
    }

    public function enabled(): array
    {
        $stmt = $this->db->query("SELECT code FROM enabled_tribunals WHERE enabled = 1 ORDER BY ordem ASC, code ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $rows ?: ['TJSP'];
    }

    public function records(): array
    {
        $stmt = $this->db->query("SELECT * FROM enabled_tribunals ORDER BY ordem ASC, code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(array $codes): void
    {
        $codes = array_values(array_unique(array_map('strtoupper', array_filter($codes))));
        $known = $this->allKnown();

        $this->db->exec("UPDATE enabled_tribunals SET enabled = 0, updated_at = NOW()");

        $stmt = $this->db->prepare("
            INSERT INTO enabled_tribunals (code, name, enabled, ordem, created_at, updated_at)
            VALUES (?, ?, 1, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), ordem = VALUES(ordem), updated_at = NOW()
        ");

        foreach ($codes as $i => $code) {
            if (!in_array($code, $known, true)) {
                continue;
            }
            $stmt->execute([$code, $code, $i + 1]);
        }
    }
}
