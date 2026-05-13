<?php
declare(strict_types=1);
namespace App\Controllers;
use Core\Controller;
use Core\Database;
use Core\Session;
use App\Services\DocumentTemplateService;

class TemplateController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $rows = $db->query("SELECT * FROM legal_templates WHERE deleted_at IS NULL ORDER BY area, tipo, titulo")->fetchAll(\PDO::FETCH_ASSOC);
        $this->render('templates/index', ['pageTitle' => 'Modelos de Peças', 'templates' => $rows]);
    }

    public function create(): void
    {
        $availableVars = [
            '{cliente_nome}', '{cliente_cpf}', '{cliente_cnpj}', '{cliente_rg}',
            '{cliente_email}', '{cliente_telefone}', '{cliente_endereco}',
            '{cliente_cidade}', '{cliente_estado}', '{cliente_cep}',
            '{cliente_estado_civil}', '{cliente_profissao}', '{cliente_nacionalidade}',
            '{processo_numero}', '{processo_numero_cnj}', '{processo_area}',
            '{processo_titulo}', '{processo_vara}', '{processo_tribunal}',
            '{processo_status}', '{processo_valor}', '{parte_contraria}',
            '{data_hoje}', '{data_por_extenso}', '{ano_atual}', '{mes_atual}',
        ];
        $this->render('templates/create', [
            'pageTitle'      => 'Novo Modelo de Documento',
            'availableVars'  => $availableVars,
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $titulo     = $this->input('titulo', '');
        $area       = $this->input('area', '');
        $tipo       = $this->input('tipo', 'outro');
        $conteudo   = $this->input('conteudo', '');
        $active     = $this->input('active', '1') === '1' ? 1 : 0;
        $varsJson   = null;

        if (!$titulo) {
            Session::flash('error', 'Título obrigatório.');
            $this->redirect('/templates/create');
        }

        $st = $db->prepare(
            "INSERT INTO legal_templates (titulo, area, tipo, conteudo, variables_json, active, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $st->execute([$titulo, $area, $tipo, $conteudo, $varsJson, $active, Session::get('user_id')]);
        Session::flash('success', 'Modelo salvo com sucesso.');
        $this->redirect('/templates');
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $st = $db->prepare("SELECT * FROM legal_templates WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $st->execute([(int)$id]);
        $template = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$template) {
            Session::flash('error', 'Modelo não encontrado.');
            $this->redirect('/templates');
        }
        $availableVars = [
            '{cliente_nome}', '{cliente_cpf}', '{cliente_cnpj}', '{cliente_rg}',
            '{cliente_email}', '{cliente_telefone}', '{cliente_endereco}',
            '{cliente_cidade}', '{cliente_estado}', '{cliente_cep}',
            '{cliente_estado_civil}', '{cliente_profissao}', '{cliente_nacionalidade}',
            '{processo_numero}', '{processo_numero_cnj}', '{processo_area}',
            '{processo_titulo}', '{processo_vara}', '{processo_tribunal}',
            '{processo_status}', '{processo_valor}', '{parte_contraria}',
            '{data_hoje}', '{data_por_extenso}', '{ano_atual}', '{mes_atual}',
        ];
        $this->render('templates/edit', [
            'pageTitle'     => 'Editar Modelo',
            'template'      => $template,
            'availableVars' => $availableVars,
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $st = $db->prepare("SELECT id FROM legal_templates WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $st->execute([(int)$id]);
        if (!$st->fetch()) {
            Session::flash('error', 'Modelo não encontrado.');
            $this->redirect('/templates');
        }
        $titulo   = $this->input('titulo', '');
        $area     = $this->input('area', '');
        $tipo     = $this->input('tipo', 'outro');
        $conteudo = $this->input('conteudo', '');
        $active   = $this->input('active', '1') === '1' ? 1 : 0;

        if (!$titulo) {
            Session::flash('error', 'Título obrigatório.');
            $this->redirect("/templates/{$id}/edit");
        }

        $upd = $db->prepare(
            "UPDATE legal_templates SET titulo=?, area=?, tipo=?, conteudo=?, active=?, updated_at=NOW() WHERE id=?"
        );
        $upd->execute([$titulo, $area, $tipo, $conteudo, $active, (int)$id]);
        Session::flash('success', 'Modelo atualizado com sucesso.');
        $this->redirect('/templates');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $st = $db->prepare("UPDATE legal_templates SET deleted_at=NOW() WHERE id=?");
        $st->execute([(int)$id]);
        Session::flash('success', 'Modelo excluído.');
        $this->redirect('/templates');
    }

    public function generate(string $id): void
    {
        $this->validateCsrf();
        $service    = new DocumentTemplateService();
        $entityType = $this->input('entity_type', 'client');
        $entityId   = (int)$this->input('entity_id', '0');
        $userId     = (int)Session::get('user_id');

        $result = $service->generate((int)$id, $entityType, $entityId, $userId);

        if (!$result['success']) {
            Session::flash('error', $result['error'] ?? 'Erro ao gerar documento.');
            $this->redirect("/templates/{$id}/generate-form");
        }

        // Render preview
        $db = Database::getInstance();
        $st = $db->prepare("SELECT * FROM legal_templates WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $st->execute([(int)$id]);
        $template = $st->fetch(\PDO::FETCH_ASSOC);

        $this->render('templates/generate', [
            'pageTitle'  => 'Documento Gerado',
            'template'   => $template,
            'result'     => $result,
            'entityType' => $entityType,
            'entityId'   => $entityId,
        ]);
    }

    public function generateForm(string $id): void
    {
        $db = Database::getInstance();
        $st = $db->prepare("SELECT * FROM legal_templates WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $st->execute([(int)$id]);
        $template = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$template) {
            Session::flash('error', 'Modelo não encontrado.');
            $this->redirect('/templates');
        }

        // Load clients and cases for selection
        $clients = [];
        $cases   = [];
        try {
            $clients = $db->query("SELECT id, name FROM clients WHERE deleted_at IS NULL ORDER BY name ASC LIMIT 200")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {}
        try {
            $cases = $db->query("SELECT id, titulo, numero_cnj FROM cases WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 200")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {}

        $this->render('templates/generate', [
            'pageTitle' => 'Gerar Documento — ' . $template['titulo'],
            'template'  => $template,
            'clients'   => $clients,
            'cases'     => $cases,
            'result'    => null,
        ]);
    }

    public function generated(): void
    {
        $db = Database::getInstance();
        try {
            $rows = $db->query(
                "SELECT gd.*, lt.titulo AS template_titulo, u.name AS created_by_name
                 FROM generated_documents gd
                 LEFT JOIN legal_templates lt ON lt.id = gd.template_id
                 LEFT JOIN users u ON u.id = gd.created_by
                 WHERE gd.deleted_at IS NULL
                 ORDER BY gd.created_at DESC
                 LIMIT 200"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $rows = [];
        }
        $this->render('templates/generated', [
            'pageTitle' => 'Documentos Gerados',
            'documents' => $rows,
        ]);
    }
}
