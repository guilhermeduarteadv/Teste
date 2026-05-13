<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\SchemaGuardService;
use Core\Session;
use App\Models\AdministrativeProcedure;
use App\Models\Client;
use App\Models\User;

class AdministrativeProcedureController extends Controller
{
    private $model;

    public function __construct()
    {
        (new SchemaGuardService())->ensureV49Schema();
        $this->model = new AdministrativeProcedure();
    }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'client_id' => $_GET['client_id'] ?? '',
        ];

        $result = $this->model->findAllPaginated($page, 20, $filters);

        $this->render('administrative/index', [
            'pageTitle' => 'Processos Administrativos',
            'procedures' => $result['data'],
            'pagination' => $result,
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->render('administrative/form', [
            'pageTitle' => 'Novo Processo Administrativo',
            'procedure' => [],
            'clients' => (new Client())->findAll(['status' => 'active'], 'name ASC'),
            'users' => (new User())->findAll(['status' => 'active'], 'name ASC'),
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();

        $data = $this->collectData();
        $id = $this->model->insert($data);

        Session::flash('success', 'Processo administrativo cadastrado com sucesso.');
        $this->redirect('/administrative-procedures/' . $id);
    }

    public function show(string $id): void
    {
        $procedure = $this->model->queryOne(
            "SELECT ap.*, cl.name AS client_name, u.name AS responsavel_name
             FROM administrative_procedures ap
             LEFT JOIN clients cl ON cl.id = ap.client_id
             LEFT JOIN users u ON u.id = ap.responsavel_id
             WHERE ap.id = ? AND ap.deleted_at IS NULL LIMIT 1",
            [(int)$id]
        );

        if (!$procedure) {
            Session::flash('error', 'Procedimento administrativo não encontrado.');
            $this->redirect('/administrative-procedures');
        }

        $financial = $this->model->query(
            "SELECT * FROM financial_entries
             WHERE deleted_at IS NULL
             AND (administrative_procedure_id = ? OR (entity_type = 'administrative_procedure' AND entity_id = ?))
             ORDER BY vencimento DESC",
            [(int)$id, (int)$id]
        );

        $this->render('administrative/show', [
            'pageTitle' => 'Processo Administrativo',
            'procedure' => $procedure,
            'financial' => $financial,
        ]);
    }

    public function edit(string $id): void
    {
        $procedure = $this->model->findById((int)$id);
        if (!$procedure) {
            Session::flash('error', 'Procedimento não encontrado.');
            $this->redirect('/administrative-procedures');
        }

        $this->render('administrative/form', [
            'pageTitle' => 'Editar Processo Administrativo',
            'procedure' => $procedure,
            'clients' => (new Client())->findAll(['status' => 'active'], 'name ASC'),
            'users' => (new User())->findAll(['status' => 'active'], 'name ASC'),
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $this->model->update((int)$id, $this->collectData());
        Session::flash('success', 'Processo administrativo atualizado.');
        $this->redirect('/administrative-procedures/' . $id);
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $this->model->delete((int)$id);
        Session::flash('success', 'Processo administrativo removido.');
        $this->redirect('/administrative-procedures');
    }

    private function collectData(): array
    {
        return [
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'natureza' => trim($_POST['natureza'] ?? 'administracao_publica'),
            'titulo' => trim($_POST['titulo'] ?? ''),
            'numero_processo' => trim($_POST['numero_processo'] ?? ''),
            'prefeitura' => trim($_POST['prefeitura'] ?? ''),
            'secretaria' => trim($_POST['secretaria'] ?? ''),
            'setor' => trim($_POST['setor'] ?? ''),
            'tipo_procedimento' => trim($_POST['tipo_procedimento'] ?? ''),
            'assunto' => trim($_POST['assunto'] ?? ''),
            'status' => trim($_POST['status'] ?? 'ativo'),
            'data_protocolo' => $_POST['data_protocolo'] ?? null,
            'prazo_resposta' => $_POST['prazo_resposta'] ?? null,
            'valor_estimado' => str_replace(',', '.', $_POST['valor_estimado'] ?? '0'),
            'portal_url' => trim($_POST['portal_url'] ?? ''),
            'portal_login' => trim($_POST['portal_login'] ?? ''),
            'portal_password_encrypted' => trim($_POST['portal_password'] ?? ''), // MVP: não criptografar aqui; ideal criptografia em produção.
            'cartorio_nome' => trim($_POST['cartorio_nome'] ?? ''),
            'cartorio_cnpj' => trim($_POST['cartorio_cnpj'] ?? ''),
            'cartorio_oficial' => trim($_POST['cartorio_oficial'] ?? ''),
            'cartorio_livro' => trim($_POST['cartorio_livro'] ?? ''),
            'cartorio_folha' => trim($_POST['cartorio_folha'] ?? ''),
            'cartorio_matricula' => trim($_POST['cartorio_matricula'] ?? ''),
            'cartorio_ato' => trim($_POST['cartorio_ato'] ?? ''),
            'responsavel_id' => !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null,
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'created_by' => Session::get('user_id'),
        ];
    }
}
