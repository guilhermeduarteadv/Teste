<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class DocumentTemplateService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a document from a template for a given entity.
     *
     * @return array ['success', 'content', 'file_path', 'doc_id']
     */
    public function generate(int $templateId, string $entityType, int $entityId, int $userId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM legal_templates WHERE id = ? AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                return ['success' => false, 'error' => 'Modelo não encontrado.', 'content' => '', 'file_path' => '', 'doc_id' => 0];
            }

            $vars    = $this->getVariables($entityType, $entityId);
            $content = $this->substituteVariables((string)$template['conteudo'], $vars);

            // Determine client_id and case_id from entity
            $clientId = 0;
            $caseId   = 0;

            if ($entityType === 'client') {
                $clientId = $entityId;
            } elseif ($entityType === 'case') {
                $caseId   = $entityId;
                $clientId = $this->getClientIdFromCase($entityId);
            }

            $title  = $template['titulo'] . ' — ' . date('d/m/Y');
            $docId  = $this->saveGeneratedDocument(
                $templateId,
                $entityType,
                $entityId,
                $clientId,
                $caseId,
                $title,
                $content,
                $userId
            );

            return [
                'success'   => true,
                'content'   => $content,
                'file_path' => '',
                'doc_id'    => $docId,
                'title'     => $title,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'content' => '', 'file_path' => '', 'doc_id' => 0];
        }
    }

    public function substituteVariables(string $content, array $vars): string
    {
        foreach ($vars as $placeholder => $value) {
            $content = str_replace($placeholder, (string)$value, $content);
        }
        return $content;
    }

    public function getVariables(string $entityType, int $entityId): array
    {
        $vars = [
            '{data_hoje}'      => date('d/m/Y'),
            '{data_por_extenso}' => $this->datePorExtenso(date('Y-m-d')),
            '{ano_atual}'      => date('Y'),
            '{mes_atual}'      => $this->mesNome((int)date('m')),
        ];

        if ($entityType === 'client') {
            $vars = array_merge($vars, $this->getClientVars($entityId));
        } elseif ($entityType === 'case') {
            $vars = array_merge($vars, $this->getCaseVars($entityId));
            $clientId = $this->getClientIdFromCase($entityId);
            if ($clientId > 0) {
                $vars = array_merge($vars, $this->getClientVars($clientId));
            }
        }

        return $vars;
    }

    public function getClientVars(int $clientId): array
    {
        $vars = [
            '{cliente_nome}'        => '',
            '{cliente_cpf}'         => '',
            '{cliente_cnpj}'        => '',
            '{cliente_rg}'          => '',
            '{cliente_email}'       => '',
            '{cliente_telefone}'    => '',
            '{cliente_endereco}'    => '',
            '{cliente_cidade}'      => '',
            '{cliente_estado}'      => '',
            '{cliente_cep}'         => '',
            '{cliente_estado_civil}' => '',
            '{cliente_profissao}'   => '',
            '{cliente_nacionalidade}' => 'brasileiro(a)',
        ];

        if ($clientId <= 0) {
            return $vars;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM clients WHERE id = ? LIMIT 1");
            $stmt->execute([$clientId]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $vars['{cliente_nome}']        = $c['name'] ?? '';
                $vars['{cliente_cpf}']         = $this->formatCpf($c['cpf'] ?? '');
                $vars['{cliente_cnpj}']        = $this->formatCnpj($c['cnpj'] ?? '');
                $vars['{cliente_rg}']          = $c['rg'] ?? '';
                $vars['{cliente_email}']       = $c['email'] ?? '';
                $vars['{cliente_telefone}']    = $c['phone'] ?? $c['whatsapp'] ?? '';
                $vars['{cliente_estado_civil}'] = $c['estado_civil'] ?? '';
                $vars['{cliente_profissao}']   = $c['profissao'] ?? '';
                $endereco = trim(($c['endereco'] ?? '') . ', ' . ($c['numero'] ?? ''));
                $endereco = rtrim($endereco, ', ');
                if (!empty($c['complemento'])) {
                    $endereco .= ' — ' . $c['complemento'];
                }
                if (!empty($c['bairro'])) {
                    $endereco .= ', ' . $c['bairro'];
                }
                $vars['{cliente_endereco}'] = $endereco;
                $vars['{cliente_cidade}']   = $c['cidade'] ?? '';
                $vars['{cliente_estado}']   = $c['estado'] ?? '';
                $vars['{cliente_cep}']      = $this->formatCep($c['cep'] ?? '');
            }
        } catch (\Throwable $e) {}

        return $vars;
    }

    public function getCaseVars(int $caseId): array
    {
        $vars = [
            '{processo_numero}'      => '',
            '{processo_numero_cnj}'  => '',
            '{processo_area}'        => '',
            '{processo_titulo}'      => '',
            '{processo_vara}'        => '',
            '{processo_tribunal}'    => '',
            '{processo_status}'      => '',
            '{processo_valor}'       => '',
            '{parte_contraria}'      => '',
        ];

        if ($caseId <= 0) {
            return $vars;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM cases WHERE id = ? LIMIT 1");
            $stmt->execute([$caseId]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $vars['{processo_numero}']     = $c['numero_cnj'] ?? $c['numero_processo'] ?? '';
                $vars['{processo_numero_cnj}'] = $c['numero_cnj'] ?? '';
                $vars['{processo_area}']       = $c['area'] ?? '';
                $vars['{processo_titulo}']     = $c['titulo'] ?? '';
                $vars['{processo_vara}']       = $c['vara'] ?? '';
                $vars['{processo_tribunal}']   = $c['tribunal'] ?? '';
                $vars['{processo_status}']     = $c['status'] ?? '';
                $vars['{processo_valor}']      = isset($c['valor_causa'])
                    ? 'R$ ' . number_format((float)$c['valor_causa'], 2, ',', '.')
                    : '';
                $vars['{parte_contraria}']     = $c['parte_contraria'] ?? '';
            }
        } catch (\Throwable $e) {}

        return $vars;
    }

    public function saveGeneratedDocument(
        int    $templateId,
        string $entityType,
        int    $entityId,
        int    $clientId,
        int    $caseId,
        string $title,
        string $content,
        int    $userId
    ): int {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO generated_documents
                 (template_id, entity_type, entity_id, client_id, case_id, title, output_format, file_path, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'html', '', ?, NOW())"
            );
            $stmt->execute([
                $templateId,
                $entityType,
                $entityId,
                $clientId ?: null,
                $caseId   ?: null,
                $title,
                $userId,
            ]);
            return (int)$this->db->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getClientIdFromCase(int $caseId): int
    {
        try {
            // Try case_clients table first
            $stmt = $this->db->prepare(
                "SELECT client_id FROM case_clients WHERE case_id = ? ORDER BY id ASC LIMIT 1"
            );
            $stmt->execute([$caseId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['client_id'])) {
                return (int)$row['client_id'];
            }
            // Fallback: cases.client_id if exists
            $stmt2 = $this->db->prepare("SELECT client_id FROM cases WHERE id = ? LIMIT 1");
            $stmt2->execute([$caseId]);
            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($row2 && !empty($row2['client_id'])) {
                return (int)$row2['client_id'];
            }
        } catch (\Throwable $e) {}
        return 0;
    }

    private function formatCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        return $cpf;
    }

    private function formatCnpj(string $cnpj): string
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) === 14) {
            return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
        }
        return $cnpj;
    }

    private function formatCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
        }
        return $cep;
    }

    private function mesNome(int $mes): string
    {
        $meses = [
            1  => 'janeiro',    2  => 'fevereiro', 3  => 'março',
            4  => 'abril',      5  => 'maio',       6  => 'junho',
            7  => 'julho',      8  => 'agosto',     9  => 'setembro',
            10 => 'outubro',    11 => 'novembro',   12 => 'dezembro',
        ];
        return $meses[$mes] ?? '';
    }

    private function datePorExtenso(string $date): string
    {
        $parts = explode('-', $date);
        if (count($parts) !== 3) {
            return $date;
        }
        $dia = (int)$parts[2];
        $mes = $this->mesNome((int)$parts[1]);
        $ano = $parts[0];
        return $dia . ' de ' . $mes . ' de ' . $ano;
    }
}
