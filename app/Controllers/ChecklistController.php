<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;

class ChecklistController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $this->seedInitialTemplates();
        $templates = $this->db->query(
            "SELECT * FROM checklist_templates WHERE deleted_at IS NULL ORDER BY module ASC, name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('checklists/index', [
            'pageTitle' => 'Checklists Automáticos',
            'templates' => $templates,
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $name          = trim($_POST['name'] ?? '');
        $module        = trim($_POST['module'] ?? '');
        $procedureType = trim($_POST['procedure_type'] ?? '');
        $itemsRaw      = trim($_POST['items_raw'] ?? '');

        if ($name === '') {
            $this->json(['success' => false, 'message' => 'Nome é obrigatório.']);
            return;
        }

        $items = [];
        foreach (explode("\n", $itemsRaw) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $items[] = ['title' => $line, 'required' => false, 'description' => ''];
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO checklist_templates (name, module, procedure_type, items_json, active, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())"
        );
        $stmt->execute([$name, $module, $procedureType, json_encode($items, JSON_UNESCAPED_UNICODE)]);

        $this->json(['success' => true, 'message' => 'Template criado com sucesso.']);
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $this->db->prepare(
            "UPDATE checklist_templates SET deleted_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([(int)$id]);
        $this->json(['success' => true, 'message' => 'Template removido.']);
    }

    public function caseChecklist(string $caseId): void
    {
        $caseId = (int)$caseId;
        $case   = $this->db->prepare(
            "SELECT * FROM cases WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $case->execute([$caseId]);
        $caseData = $case->fetch(\PDO::FETCH_ASSOC);

        if (!$caseData) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
            return;
        }

        $items = $this->db->prepare(
            "SELECT ci.*, u.name AS done_by_name
             FROM case_checklist_items ci
             LEFT JOIN users u ON ci.done_by = u.id
             WHERE ci.case_id = ? AND ci.deleted_at IS NULL
             ORDER BY ci.id ASC"
        );
        $items->execute([$caseId]);
        $checklistItems = $items->fetchAll(\PDO::FETCH_ASSOC);

        $total    = count($checklistItems);
        $done     = 0;
        foreach ($checklistItems as $item) {
            if ($item['done']) {
                $done++;
            }
        }
        $progress = $total > 0 ? round(($done / $total) * 100) : 0;

        $appliedTemplates = $this->db->prepare(
            "SELECT DISTINCT template_id FROM case_checklist_items WHERE case_id = ? AND deleted_at IS NULL"
        );
        $appliedTemplates->execute([$caseId]);
        $applied = $appliedTemplates->fetchAll(\PDO::FETCH_COLUMN);

        $templates = $this->db->query(
            "SELECT id, name, module, procedure_type FROM checklist_templates WHERE deleted_at IS NULL AND active = 1 ORDER BY name ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('cases/checklist', [
            'pageTitle'        => 'Checklist do Processo',
            'case'             => $caseData,
            'checklistItems'   => $checklistItems,
            'progress'         => $progress,
            'total'            => $total,
            'done'             => $done,
            'templates'        => $templates,
            'appliedTemplates' => $applied,
            'csrf_token'       => Session::csrfToken(),
        ]);
    }

    public function apply(string $caseId): void
    {
        $this->validateCsrf();
        $caseId     = (int)$caseId;
        $templateId = (int)($_POST['template_id'] ?? 0);

        if (!$templateId) {
            $this->json(['success' => false, 'message' => 'Selecione um template.']);
            return;
        }

        // Check if already applied
        $check = $this->db->prepare(
            "SELECT COUNT(*) FROM case_checklist_items WHERE case_id = ? AND template_id = ? AND deleted_at IS NULL"
        );
        $check->execute([$caseId, $templateId]);
        if ((int)$check->fetchColumn() > 0) {
            $this->json(['success' => false, 'message' => 'Este template já foi aplicado a este processo.']);
            return;
        }

        $tpl = $this->db->prepare(
            "SELECT * FROM checklist_templates WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $tpl->execute([$templateId]);
        $template = $tpl->fetch(\PDO::FETCH_ASSOC);

        if (!$template) {
            $this->json(['success' => false, 'message' => 'Template não encontrado.']);
            return;
        }

        $items = json_decode($template['items_json'] ?? '[]', true);
        if (!is_array($items)) {
            $items = [];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO case_checklist_items (case_id, template_id, title, description, required, done, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())"
        );

        foreach ($items as $item) {
            $title       = $item['title'] ?? '';
            $description = $item['description'] ?? '';
            $required    = isset($item['required']) && $item['required'] ? 1 : 0;
            $stmt->execute([$caseId, $templateId, $title, $description, $required]);
        }

        $this->json(['success' => true, 'message' => 'Template aplicado com sucesso.']);
    }

    public function toggleItem(string $id): void
    {
        $this->validateCsrf();
        $id     = (int)$id;
        $userId = (int)Session::get('user_id');

        $item = $this->db->prepare(
            "SELECT * FROM case_checklist_items WHERE id = ? AND deleted_at IS NULL LIMIT 1"
        );
        $item->execute([$id]);
        $row = $item->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'Item não encontrado.']);
            return;
        }

        $newDone  = $row['done'] ? 0 : 1;
        $doneAt   = $newDone ? date('Y-m-d H:i:s') : null;
        $doneBy   = $newDone ? $userId : null;

        $this->db->prepare(
            "UPDATE case_checklist_items SET done = ?, done_at = ?, done_by = ?, updated_at = NOW() WHERE id = ?"
        )->execute([$newDone, $doneAt, $doneBy, $id]);

        $this->json(['success' => true, 'done' => (bool)$newDone]);
    }

    private function seedInitialTemplates(): void
    {
        try {
            $count = (int)$this->db->query(
                "SELECT COUNT(*) FROM checklist_templates WHERE deleted_at IS NULL"
            )->fetchColumn();
        } catch (\Throwable $e) {
            // deleted_at column may not exist yet — use fallback
            try {
                $count = (int)$this->db->query("SELECT COUNT(*) FROM checklist_templates")->fetchColumn();
            } catch (\Throwable $e2) {
                return;
            }
        }

        if ($count > 0) {
            return;
        }

        $templates = [
            [
                'name'           => 'Plano de Saúde - Negativa de Cobertura',
                'module'         => 'cases',
                'procedure_type' => 'plano_saude',
                'items'          => [
                    'Reunir apólice do plano de saúde',
                    'Obter negativa de cobertura por escrito',
                    'Coletar laudos e prescrições médicas',
                    'Verificar ANS - Rol de Procedimentos',
                    'Notificação extrajudicial à operadora',
                    'Ajuizamento de ação (se necessário)',
                    'Pedido de tutela de urgência',
                ],
            ],
            [
                'name'           => 'Ação de Alimentos',
                'module'         => 'cases',
                'procedure_type' => 'alimentos',
                'items'          => [
                    'Documentos pessoais do alimentando',
                    'Comprovante de filiação (certidão de nascimento)',
                    'Comprovante de renda do alimentante',
                    'Levantamento das necessidades do alimentando',
                    'Petição inicial',
                    'Pedido de alimentos provisórios',
                    'Intimação do réu para audiência',
                    'Audiência de conciliação',
                ],
            ],
            [
                'name'           => 'Guarda e Visitas',
                'module'         => 'cases',
                'procedure_type' => 'guarda',
                'items'          => [
                    'Certidão de nascimento das crianças',
                    'Documentos pessoais dos genitores',
                    'Comprovante de residência',
                    'Estudo social (se necessário)',
                    'Proposta de guarda e regulamentação de visitas',
                    'Audiência de conciliação',
                    'Sentença / homologação de acordo',
                ],
            ],
            [
                'name'           => 'Reclamação Trabalhista',
                'module'         => 'cases',
                'procedure_type' => 'trabalhista',
                'items'          => [
                    'CTPS (original e cópias)',
                    'Contracheques e holerites',
                    'Contrato de trabalho (se houver)',
                    'Comprovante de rescisão / TRCT',
                    'Extrato FGTS',
                    'Cálculo das verbas rescisórias',
                    'Petição inicial (reclamação trabalhista)',
                    'Audiência inaugural',
                    'Instrução processual',
                ],
            ],
            [
                'name'           => 'Usucapião',
                'module'         => 'cases',
                'procedure_type' => 'usucapiao',
                'items'          => [
                    'Certidão negativa de ônus do imóvel',
                    'Matrícula atualizada do imóvel',
                    'Documentos pessoais do requerente',
                    'Comprovante de posse mansa e pacífica',
                    'Testemunhas (mínimo 2)',
                    'Planta e memorial descritivo do imóvel',
                    'Notificação dos confrontantes',
                    'Petição inicial',
                    'Publicação de editais',
                    'Sentença de procedência',
                    'Registro no cartório de imóveis',
                ],
            ],
            [
                'name'           => 'Inventário Extrajudicial',
                'module'         => 'cases',
                'procedure_type' => 'inventario_extrajudicial',
                'items'          => [
                    'Certidão de óbito do falecido',
                    'Documentos pessoais dos herdeiros',
                    'Certidão de nascimento / casamento dos herdeiros',
                    'Documentos dos bens do espólio',
                    'Certidão negativa de testamento (RCPN)',
                    'Certidão negativa de débitos federais (CND)',
                    'Certidão negativa de débitos estaduais',
                    'Certidão negativa de débitos municipais (imóveis)',
                    'Avaliação dos bens',
                    'ITCMD - recolhimento',
                    'Escritura de inventário no cartório',
                    'Formal de partilha',
                    'Transferência de bens (DETRAN, cartório etc.)',
                ],
            ],
        ];

        $stmt = $this->db->prepare(
            "INSERT INTO checklist_templates (name, module, procedure_type, items_json, active, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())"
        );

        foreach ($templates as $tpl) {
            $itemsJson = [];
            foreach ($tpl['items'] as $title) {
                $itemsJson[] = ['title' => $title, 'required' => false, 'description' => ''];
            }
            $stmt->execute([
                $tpl['name'],
                $tpl['module'],
                $tpl['procedure_type'],
                json_encode($itemsJson, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
}
