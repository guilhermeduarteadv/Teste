<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\SchemaGuardService;
use Core\Session;
use App\Models\LegalConsultancy;
use App\Models\Client;
use App\Models\User;

class LegalConsultancyController extends Controller
{
    private $model;

    public function __construct()
    {
        (new SchemaGuardService())->ensureV42Schema();
        $this->model = new LegalConsultancy();
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

        $this->render('consultancies/index', [
            'pageTitle' => 'Consultorias Jurídicas',
            'consultancies' => $result['data'],
            'pagination' => $result,
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        $this->render('consultancies/form', [
            'pageTitle' => 'Nova Consultoria',
            'consultancy' => [],
            'clients' => (new Client())->findAll(['status' => 'active'], 'name ASC'),
            'users' => (new User())->findAll(['status' => 'active'], 'name ASC'),
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $id = $this->model->insert($this->collectData());
        Session::flash('success', 'Consultoria cadastrada com sucesso.');
        $this->redirect('/consultancies/' . $id);
    }

    public function show(string $id): void
    {
        $consultancy = $this->model->queryOne(
            "SELECT lc.*, cl.name AS client_name, u.name AS responsavel_name
             FROM legal_consultancies lc
             LEFT JOIN clients cl ON cl.id = lc.client_id
             LEFT JOIN users u ON u.id = lc.responsavel_id
             WHERE lc.id = ? AND lc.deleted_at IS NULL LIMIT 1",
            [(int)$id]
        );

        if (!$consultancy) {
            Session::flash('error', 'Consultoria não encontrada.');
            $this->redirect('/consultancies');
        }

        $financial = $this->model->query(
            "SELECT * FROM financial_entries
             WHERE deleted_at IS NULL
             AND (consultancy_id = ? OR (entity_type = 'consultancy' AND entity_id = ?))
             ORDER BY vencimento DESC",
            [(int)$id, (int)$id]
        );

        $this->render('consultancies/show', [
            'pageTitle' => 'Consultoria Jurídica',
            'consultancy' => $consultancy,
            'financial' => $financial,
        ]);
    }

    public function edit(string $id): void
    {
        $consultancy = $this->model->findById((int)$id);
        if (!$consultancy) {
            Session::flash('error', 'Consultoria não encontrada.');
            $this->redirect('/consultancies');
        }

        $this->render('consultancies/form', [
            'pageTitle' => 'Editar Consultoria',
            'consultancy' => $consultancy,
            'clients' => (new Client())->findAll(['status' => 'active'], 'name ASC'),
            'users' => (new User())->findAll(['status' => 'active'], 'name ASC'),
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $this->model->update((int)$id, $this->collectData());
        Session::flash('success', 'Consultoria atualizada.');
        $this->redirect('/consultancies/' . $id);
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $this->model->delete((int)$id);
        Session::flash('success', 'Consultoria removida.');
        $this->redirect('/consultancies');
    }

    private function collectData(): array
    {
        return [
            'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
            'titulo' => trim($_POST['titulo'] ?? ''),
            'area' => trim($_POST['area'] ?? ''),
            'tipo_consultoria' => trim($_POST['tipo_consultoria'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'status' => trim($_POST['status'] ?? 'em_andamento'),
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'data_conclusao' => $_POST['data_conclusao'] ?? null,
            'valor_estimado' => str_replace(',', '.', $_POST['valor_estimado'] ?? '0'),
            'responsavel_id' => !empty($_POST['responsavel_id']) ? (int)$_POST['responsavel_id'] : null,
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'created_by' => Session::get('user_id'),
        ];
    }
}
