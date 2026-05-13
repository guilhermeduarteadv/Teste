<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class ReportService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a case report (client or internal version).
     *
     * @return array ['success', 'file_path', 'html']
     */
    public function generateCaseReport(int $caseId, bool $internal, int $userId): array
    {
        try {
            // Load case
            $stmt = $this->db->prepare(
                "SELECT c.*, u.name AS responsavel_name
                 FROM cases c
                 LEFT JOIN users u ON u.id = c.responsavel_id
                 WHERE c.id = ? AND c.deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$caseId]);
            $case = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$case) {
                return ['success' => false, 'file_path' => '', 'html' => '', 'message' => 'Processo não encontrado.'];
            }

            $data = [];

            // Clients
            try {
                $st = $this->db->prepare(
                    "SELECT cl.name, cl.email, cl.cpf_cnpj FROM case_clients cc
                     LEFT JOIN clients cl ON cl.id = cc.client_id
                     WHERE cc.case_id = ?"
                );
                $st->execute([$caseId]);
                $data['clients'] = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $data['clients'] = []; }

            // Movements (filter visivel_cliente for non-internal)
            try {
                $movFilter = $internal ? '' : " AND cm.visivel_cliente = 1";
                $st = $this->db->prepare(
                    "SELECT * FROM case_movements cm
                     WHERE cm.case_id = ? AND cm.deleted_at IS NULL {$movFilter}
                     ORDER BY cm.data_movimento DESC"
                );
                $st->execute([$caseId]);
                $data['movements'] = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $data['movements'] = []; }

            // Deadlines (filter visivel_cliente for non-internal)
            try {
                $dlFilter = $internal ? '' : " AND cd.visivel_cliente = 1";
                $st = $this->db->prepare(
                    "SELECT * FROM case_deadlines cd
                     WHERE cd.case_id = ? AND cd.deleted_at IS NULL {$dlFilter}
                     ORDER BY cd.data_final ASC"
                );
                $st->execute([$caseId]);
                $data['deadlines'] = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $data['deadlines'] = []; }

            // Hearings (filter visivel_cliente for non-internal)
            try {
                $hrFilter = $internal ? '' : " AND ch.visivel_cliente = 1";
                $st = $this->db->prepare(
                    "SELECT * FROM case_hearings ch
                     WHERE ch.case_id = ? AND ch.deleted_at IS NULL {$hrFilter}
                     ORDER BY ch.data ASC"
                );
                $st->execute([$caseId]);
                $data['hearings'] = $st->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) { $data['hearings'] = []; }

            // Internal-only data
            if ($internal) {
                try {
                    $st = $this->db->prepare(
                        "SELECT * FROM process_strategy_notes WHERE case_id = ? LIMIT 1"
                    );
                    $st->execute([$caseId]);
                    $data['strategy'] = $st->fetch(PDO::FETCH_ASSOC) ?: [];
                } catch (\Throwable $e) { $data['strategy'] = []; }

                try {
                    $st = $this->db->prepare(
                        "SELECT fe.* FROM financial_entries fe
                         WHERE fe.case_id = ? AND fe.deleted_at IS NULL ORDER BY fe.vencimento DESC"
                    );
                    $st->execute([$caseId]);
                    $data['financial'] = $st->fetchAll(PDO::FETCH_ASSOC);
                } catch (\Throwable $e) { $data['financial'] = []; }
            }

            $html = $this->buildCaseHtml($case, $data, $internal);

            // Save file
            $dir = ROOT_PATH . '/storage/reports';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $type = $internal ? 'internal' : 'client';
            $filename = 'case_' . $caseId . '_' . $type . '_' . date('Ymd_His') . '.html';
            $filePath = $dir . '/' . $filename;
            file_put_contents($filePath, $html);

            $relativePath = 'storage/reports/' . $filename;

            $reportId = $this->saveReport(
                'case_' . $type,
                'case',
                $caseId,
                $relativePath,
                ['internal' => $internal, 'case_id' => $caseId],
                $userId
            );

            return [
                'success'   => true,
                'file_path' => $relativePath,
                'html'      => $html,
                'report_id' => $reportId,
                'message'   => 'Relatório gerado com sucesso.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'file_path' => '', 'html' => '', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Generate a financial report.
     *
     * @param array $filters ['date_from', 'date_to', 'client_id', 'status', 'tipo']
     * @return array ['success', 'file_path', 'html']
     */
    public function generateFinancialReport(array $filters, int $userId): array
    {
        try {
            $dateFrom = $filters['date_from'] ?? date('Y-m-01');
            $dateTo   = $filters['date_to'] ?? date('Y-m-d');
            $status   = $filters['status'] ?? '';
            $tipo     = $filters['tipo'] ?? '';
            $clientId = isset($filters['client_id']) ? (int)$filters['client_id'] : 0;

            $where  = "fe.deleted_at IS NULL AND fe.vencimento BETWEEN ? AND ?";
            $params = [$dateFrom, $dateTo];

            if ($status !== '') { $where .= " AND fe.status = ?"; $params[] = $status; }
            if ($tipo !== '')   { $where .= " AND fe.tipo = ?";   $params[] = $tipo; }
            if ($clientId > 0) { $where .= " AND fe.client_id = ?"; $params[] = $clientId; }

            $stmt = $this->db->prepare(
                "SELECT fe.*, cl.name AS client_name, c.numero_cnj
                 FROM financial_entries fe
                 LEFT JOIN clients cl ON cl.id = fe.client_id
                 LEFT JOIN cases c ON c.id = fe.case_id
                 WHERE {$where}
                 ORDER BY fe.vencimento DESC"
            );
            $stmt->execute($params);
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalPago    = 0;
            $totalPendente = 0;
            $totalVencido  = 0;
            foreach ($entries as $e) {
                if ($e['status'] === 'pago')     $totalPago     += (float)$e['valor'];
                if ($e['status'] === 'pendente') $totalPendente += (float)$e['valor'];
                if ($e['status'] === 'vencido')  $totalVencido  += (float)$e['valor'];
            }

            $html = $this->buildFinancialHtml($entries, [
                'date_from'     => $dateFrom,
                'date_to'       => $dateTo,
                'total_pago'    => $totalPago,
                'total_pendente'=> $totalPendente,
                'total_vencido' => $totalVencido,
                'filtros'       => $filters,
            ]);

            $dir = ROOT_PATH . '/storage/reports';
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $filename = 'financial_' . date('Ymd_His') . '.html';
            $filePath = $dir . '/' . $filename;
            file_put_contents($filePath, $html);

            $relativePath = 'storage/reports/' . $filename;

            $reportId = $this->saveReport('financial', 'financial', 0, $relativePath, $filters, $userId);

            return [
                'success'   => true,
                'file_path' => $relativePath,
                'html'      => $html,
                'report_id' => $reportId,
                'message'   => 'Relatório financeiro gerado.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'file_path' => '', 'html' => '', 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Build HTML for a case report.
     */
    public function buildCaseHtml(array $case, array $data, bool $internal): string
    {
        $label = $internal ? 'Relatório Interno do Processo' : 'Relatório do Processo para o Cliente';
        $numeroCnj = htmlspecialchars($case['numero_cnj'] ?? '', ENT_QUOTES, 'UTF-8');
        $titulo    = htmlspecialchars($case['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
        $status    = htmlspecialchars($case['status'] ?? '', ENT_QUOTES, 'UTF-8');
        $resp      = htmlspecialchars($case['responsavel_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $geradoEm  = date('d/m/Y H:i');

        $html  = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">';
        $html .= '<title>' . $label . '</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;margin:40px;color:#333;}';
        $html .= 'h1{color:#1a56db;border-bottom:2px solid #1a56db;padding-bottom:8px;}';
        $html .= 'h2{color:#374151;margin-top:30px;border-bottom:1px solid #e5e7eb;padding-bottom:6px;}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:12px;}';
        $html .= 'th{background:#f3f4f6;text-align:left;padding:8px;font-size:13px;color:#6b7280;text-transform:uppercase;}';
        $html .= 'td{padding:8px;border-bottom:1px solid #f3f4f6;font-size:14px;}';
        $html .= '.badge-success{background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:4px;font-size:12px;}';
        $html .= '.badge-warning{background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:4px;font-size:12px;}';
        $html .= '.badge-danger{background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:4px;font-size:12px;}';
        $html .= '.meta{color:#6b7280;font-size:13px;margin-bottom:20px;}';
        $html .= '@media print{body{margin:20px;}}';
        $html .= '</style></head><body>';

        $html .= '<h1>' . $label . '</h1>';
        $html .= '<div class="meta">Gerado em: ' . $geradoEm . ' &nbsp;|&nbsp; JurisControl</div>';

        $html .= '<h2>Dados do Processo</h2>';
        $html .= '<table><tr><th>Campo</th><th>Valor</th></tr>';
        $html .= '<tr><td>Número CNJ</td><td>' . $numeroCnj . '</td></tr>';
        $html .= '<tr><td>Título</td><td>' . $titulo . '</td></tr>';
        $html .= '<tr><td>Status</td><td>' . $status . '</td></tr>';
        $html .= '<tr><td>Responsável</td><td>' . $resp . '</td></tr>';
        if (!empty($case['tribunal'])) {
            $html .= '<tr><td>Tribunal</td><td>' . htmlspecialchars($case['tribunal'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (!empty($case['area'])) {
            $html .= '<tr><td>Área</td><td>' . htmlspecialchars($case['area'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        $html .= '</table>';

        // Clients
        if (!empty($data['clients'])) {
            $html .= '<h2>Partes</h2><table><tr><th>Nome</th><th>E-mail</th><th>CPF/CNPJ</th></tr>';
            foreach ($data['clients'] as $cl) {
                $html .= '<tr><td>' . htmlspecialchars($cl['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($cl['email'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($cl['cpf_cnpj'] ?? '', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            $html .= '</table>';
        }

        // Movements
        if (!empty($data['movements'])) {
            $html .= '<h2>Movimentações</h2><table><tr><th>Data</th><th>Tipo</th><th>Descrição</th></tr>';
            foreach ($data['movements'] as $mv) {
                $html .= '<tr><td>' . htmlspecialchars($mv['data_movimento'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($mv['tipo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($mv['descricao'] ?? $mv['conteudo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            $html .= '</table>';
        }

        // Deadlines
        if (!empty($data['deadlines'])) {
            $html .= '<h2>Prazos</h2><table><tr><th>Prazo</th><th>Tipo</th><th>Status</th></tr>';
            foreach ($data['deadlines'] as $dl) {
                $prazo = $dl['data_final'] ?? $dl['prazo'] ?? '';
                $html .= '<tr><td>' . htmlspecialchars($prazo, ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($dl['tipo'] ?? $dl['title'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($dl['status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            $html .= '</table>';
        }

        // Hearings
        if (!empty($data['hearings'])) {
            $html .= '<h2>Audiências</h2><table><tr><th>Data</th><th>Tipo</th><th>Local</th><th>Status</th></tr>';
            foreach ($data['hearings'] as $hr) {
                $html .= '<tr><td>' . htmlspecialchars($hr['data'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($hr['tipo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($hr['local'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars($hr['status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            $html .= '</table>';
        }

        // Internal-only sections
        if ($internal) {
            if (!empty($data['strategy'])) {
                $st = $data['strategy'];
                $html .= '<h2>Estratégia e Riscos</h2><table><tr><th>Campo</th><th>Conteúdo</th></tr>';
                if (!empty($st['tese_principal']))    $html .= '<tr><td>Tese Principal</td><td>'    . htmlspecialchars($st['tese_principal'],    ENT_QUOTES, 'UTF-8') . '</td></tr>';
                if (!empty($st['tese_subsidiaria']))  $html .= '<tr><td>Tese Subsidiária</td><td>'  . htmlspecialchars($st['tese_subsidiaria'],  ENT_QUOTES, 'UTF-8') . '</td></tr>';
                if (!empty($st['riscos']))             $html .= '<tr><td>Riscos</td><td>'             . htmlspecialchars($st['riscos'],             ENT_QUOTES, 'UTF-8') . '</td></tr>';
                if (!empty($st['proximos_passos']))   $html .= '<tr><td>Próximos Passos</td><td>'   . htmlspecialchars($st['proximos_passos'],   ENT_QUOTES, 'UTF-8') . '</td></tr>';
                if (isset($st['valor_provavel']))     $html .= '<tr><td>Valor Provável</td><td>R$ ' . number_format((float)$st['valor_provavel'], 2, ',', '.') . '</td></tr>';
                $html .= '</table>';
            }

            if (!empty($data['financial'])) {
                $html .= '<h2>Financeiro</h2><table><tr><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr>';
                foreach ($data['financial'] as $fe) {
                    $html .= '<tr><td>' . htmlspecialchars($fe['descricao'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>' . htmlspecialchars($fe['tipo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>R$ ' . number_format((float)($fe['valor'] ?? 0), 2, ',', '.') . '</td>';
                    $html .= '<td>' . htmlspecialchars($fe['vencimento'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                    $html .= '<td>' . htmlspecialchars($fe['status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td></tr>';
                }
                $html .= '</table>';
            }
        }

        $html .= '<div style="margin-top:40px;text-align:center;color:#9ca3af;font-size:12px;">';
        $html .= 'Documento gerado pelo JurisControl em ' . $geradoEm;
        $html .= ' &nbsp;|&nbsp; <a href="javascript:window.print()">Imprimir</a>';
        $html .= '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Build HTML for a financial report.
     */
    private function buildFinancialHtml(array $entries, array $meta): string
    {
        $geradoEm = date('d/m/Y H:i');
        $html  = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">';
        $html .= '<title>Relatório Financeiro</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;margin:40px;color:#333;}';
        $html .= 'h1{color:#1a56db;border-bottom:2px solid #1a56db;padding-bottom:8px;}';
        $html .= 'h2{color:#374151;margin-top:30px;}table{width:100%;border-collapse:collapse;margin-top:12px;}';
        $html .= 'th{background:#f3f4f6;text-align:left;padding:8px;font-size:13px;color:#6b7280;text-transform:uppercase;}';
        $html .= 'td{padding:8px;border-bottom:1px solid #f3f4f6;font-size:14px;}';
        $html .= '.summary{display:flex;gap:20px;margin:20px 0;}';
        $html .= '.sum-box{padding:16px;border-radius:8px;flex:1;text-align:center;}';
        $html .= '.sum-pago{background:#d1fae5;}.sum-pendente{background:#fef3c7;}.sum-vencido{background:#fee2e2;}';
        $html .= '@media print{body{margin:20px;}}';
        $html .= '</style></head><body>';

        $html .= '<h1>Relatório Financeiro</h1>';
        $html .= '<div style="color:#6b7280;font-size:13px;margin-bottom:20px;">';
        $html .= 'Período: ' . htmlspecialchars($meta['date_from'], ENT_QUOTES, 'UTF-8') . ' a ' . htmlspecialchars($meta['date_to'], ENT_QUOTES, 'UTF-8');
        $html .= ' &nbsp;|&nbsp; Gerado em: ' . $geradoEm . ' | JurisControl';
        $html .= '</div>';

        $html .= '<div class="summary">';
        $html .= '<div class="sum-box sum-pago"><strong>R$ ' . number_format($meta['total_pago'], 2, ',', '.') . '</strong><br>Pago</div>';
        $html .= '<div class="sum-box sum-pendente"><strong>R$ ' . number_format($meta['total_pendente'], 2, ',', '.') . '</strong><br>Pendente</div>';
        $html .= '<div class="sum-box sum-vencido"><strong>R$ ' . number_format($meta['total_vencido'], 2, ',', '.') . '</strong><br>Vencido</div>';
        $html .= '</div>';

        $html .= '<h2>Lançamentos</h2>';
        $html .= '<table><tr><th>Descrição</th><th>Tipo</th><th>Cliente</th><th>Processo</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr>';
        foreach ($entries as $e) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($e['descricao'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($e['tipo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($e['client_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($e['numero_cnj'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>R$ ' . number_format((float)($e['valor'] ?? 0), 2, ',', '.') . '</td>';
            $html .= '<td>' . htmlspecialchars($e['vencimento'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '<td>' . htmlspecialchars($e['status'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $html .= '<div style="margin-top:40px;text-align:center;color:#9ca3af;font-size:12px;">';
        $html .= 'Gerado pelo JurisControl em ' . $geradoEm;
        $html .= ' &nbsp;|&nbsp; <a href="javascript:window.print()">Imprimir</a>';
        $html .= '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Save a generated report record in generated_reports.
     */
    public function saveReport(string $type, string $entityType, int $entityId, string $filePath, array $params, int $userId): int
    {
        try {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `generated_reports` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `report_type` VARCHAR(50) NOT NULL,
                    `entity_type` VARCHAR(50) NULL,
                    `entity_id` INT NULL,
                    `file_path` VARCHAR(500) NOT NULL,
                    `parameters_json` TEXT NULL,
                    `created_by` INT NULL,
                    `created_at` DATETIME NOT NULL,
                    `deleted_at` DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $stmt = $this->db->prepare(
                "INSERT INTO generated_reports (report_type, entity_type, entity_id, file_path, parameters_json, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$type, $entityType, $entityId ?: null, $filePath, json_encode($params, JSON_UNESCAPED_UNICODE), $userId]);
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
