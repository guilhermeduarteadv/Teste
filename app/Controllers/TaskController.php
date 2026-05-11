<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Models\Task;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\SystemLogService;
use App\Helpers\ValidationHelper;

class TaskController extends Controller
{
    private Task $model;

    public function __construct()
    {
        $this->model = new Task();
    }

    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $user    = Session::get('user');
        $filters = [
            'status'        => $this->input('status', ''),
            'prioridade'    => $this->input('prioridade', ''),
            'responsavel_id' => $this->input('responsavel_id', ''),
            'search'        => $this->input('search', ''),
        ];
        if (($user['role'] ?? '') !== 'admin' && empty($filters['responsavel_id'])) {
            $filters['responsavel_id'] = $user['id'];
        }
        $result = $this->model->findAllPaginated($page, $perPage, $filters);
        $stats  = $this->model->getStats();
        $users  = (new User())->findAllWithRoles(['status' => 'active']);

        $this->render('tasks/index', [
            'pageTitle'  => 'Tarefas',
            'tasks'      => $result['data'],
            'pagination' => $result,
            'filters'    => $filters,
            'stats'      => $stats,
            'users'      => $users,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('tasks.create');
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $cases   = (new LegalCase())->findAll(['status' => 'ativo'], 'numero_cnj ASC');
        $users   = (new User())->findAllWithRoles(['status' => 'active']);

        $this->render('tasks/create', [
            'pageTitle' => 'Nova Tarefa',
            'clients'   => $clients,
            'cases'     => $cases,
            'users'     => $users,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('tasks.create');
        $this->validateCsrf();

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)->required('title', 'Título');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect('/tasks/create');
        }

        $data['created_by'] = Session::get('user_id');
        if (empty($data['responsavel_id'])) {
            $data['responsavel_id'] = Session::get('user_id');
        }

        $id = $this->model->insert($data);
        SystemLogService::create('tasks', 'task', $id, "Tarefa criada: {$data['title']}");
        Session::flash('success', 'Tarefa criada com sucesso!');
        $this->redirect('/tasks');
    }

    public function edit(string $id): void
    {
        $task = $this->model->findById((int)$id);
        if (!$task) {
            Session::flash('error', 'Tarefa não encontrada.');
            $this->redirect('/tasks');
        }
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $cases   = (new LegalCase())->findAll([], 'numero_cnj ASC');
        $users   = (new User())->findAllWithRoles(['status' => 'active']);

        $this->render('tasks/edit', [
            'pageTitle' => 'Editar Tarefa',
            'task'      => $task,
            'clients'   => $clients,
            'cases'     => $cases,
            'users'     => $users,
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $task = $this->model->findById((int)$id);
        if (!$task) {
            Session::flash('error', 'Tarefa não encontrada.');
            $this->redirect('/tasks');
        }

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)->required('title', 'Título');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/tasks/{$id}/edit");
        }

        $this->model->update((int)$id, $data);
        SystemLogService::update('tasks', 'task', (int)$id, $task, $data);
        Session::flash('success', 'Tarefa atualizada com sucesso!');
        $this->redirect('/tasks');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $task = $this->model->findById((int)$id);
        if (!$task) {
            $this->json(['success' => false, 'message' => 'Tarefa não encontrada.']);
        }
        $this->model->softDelete((int)$id);
        SystemLogService::delete('tasks', 'task', (int)$id);
        $this->json(['success' => true, 'message' => 'Tarefa excluída com sucesso.']);
    }

    public function complete(string $id): void
    {
        $this->validateCsrf();
        $task = $this->model->findById((int)$id);
        if (!$task) {
            $this->json(['success' => false, 'message' => 'Tarefa não encontrada.']);
        }
        $this->model->complete((int)$id);
        SystemLogService::update('tasks', 'task', (int)$id, $task, ['status' => 'concluida']);
        $this->json(['success' => true, 'message' => 'Tarefa concluída com sucesso!']);
    }

    private function collectFormData(): array
    {
        return [
            'title'         => $this->input('title', ''),
            'descricao'     => $this->input('descricao', ''),
            'tipo'          => $this->input('tipo', 'outros'),
            'case_id'       => (int)$this->input('case_id', '0') ?: null,
            'client_id'     => (int)$this->input('client_id', '0') ?: null,
            'responsavel_id' => (int)$this->input('responsavel_id', '0') ?: null,
            'prazo'         => $this->input('prazo', '') ?: null,
            'prioridade'    => $this->input('prioridade', 'media'),
            'status'        => $this->input('status', 'pendente'),
            'observacoes'   => $this->input('observacoes', ''),
        ];
    }
}
