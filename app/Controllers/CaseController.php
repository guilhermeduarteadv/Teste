<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\LegalCase;
use App\Models\Client;
use App\Models\User;
use App\Services\CNJService;
use App\Services\SystemLogService;
use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;

class CaseController extends Controller
{
    private LegalCase $model;

    public function __construct()
    {
        $this->model = new LegalCase();
    }

    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = [
            'search'    => $this->input('search', ''),
            'status'    => $this->input('status', ''),
            'tribunal'  => $this->input('tribunal', ''),
        ];
        $result = $this->model->findAllPaginated($page, $perPage, $filters);
        $config = require ROOT_PATH . '/config/api.php';

        $this->render('cases/index', [
            'pageTitle'  => 'Processos',
            'cases'      => $result['data'],
            'pagination' => $result,
            'filters'    => $filters,
            'tribunais'  => $config['cnj']['tribunais'],
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('cases.create');
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $users   = (new User())->findAllWithRoles(['status' => 'active']);
        $config  = require ROOT_PATH . '/config/api.php';

        $this->render('cases/create', [
            'pageTitle' => 'Novo Processo',
            'clients'   => $clients,
            'users'     => $users,
            'tribunais' => $config['cnj']['tribunais'],
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('cases.create');
        $this->validateCsrf();

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)
            ->required('assunto', 'Assunto')
            ->numeric('valor_causa', 'Valor da Causa');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::flash('old', $data);
            $this->redirect('/cases/create');
        }

        $data['responsavel_id'] = $data['responsavel_id'] ?: Session::get('user_id');
        $id = $this->model->insert($data);

        // Add clients to case
        $clientIds = $_POST['client_ids'] ?? [];
        foreach ($clientIds as $clientId) {
            $this->model->addClient($id, (int)$clientId, $_POST['client_tipos'][$clientId] ?? 'autor');
        }

        SystemLogService::create('cases', 'case', $id, "Novo processo criado: {$data['numero_cnj']}");
        Logger::audit("Processo criado: {$data['numero_cnj']} ID #{$id}");

        Session::flash('success', 'Processo cadastrado com sucesso!');
        $this->redirect("/cases/{$id}");
    }

    public function show(string $id): void
    {
        $case = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $clients   = $this->model->getClients((int)$id);
        $movements = $this->model->getMovements((int)$id);
        $deadlines = $this->model->getDeadlines((int)$id);
        $hearings  = $this->model->getHearings((int)$id);
        $documents = $this->model->getDocuments((int)$id);
        $financial = $this->model->getFinancial((int)$id);
        $tasks     = $this->model->getTasks((int)$id);

        $this->render('cases/show', [
            'pageTitle' => 'Processo: ' . ($case['numero_cnj'] ?: $case['assunto']),
            'case'      => $case,
            'clients'   => $clients,
            'movements' => $movements,
            'deadlines' => $deadlines,
            'hearings'  => $hearings,
            'documents' => $documents,
            'financial' => $financial,
            'tasks'     => $tasks,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('cases.edit');
        $case    = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }
        $clients     = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $caseClients = $this->model->getClients((int)$id);
        $users       = (new User())->findAllWithRoles(['status' => 'active']);
        $config      = require ROOT_PATH . '/config/api.php';

        $this->render('cases/edit', [
            'pageTitle'   => 'Editar Processo',
            'case'        => $case,
            'clients'     => $clients,
            'caseClients' => $caseClients,
            'users'       => $users,
            'tribunais'   => $config['cnj']['tribunais'],
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('cases.edit');
        $this->validateCsrf();

        $case = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)->required('assunto', 'Assunto');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/cases/{$id}/edit");
        }

        $this->model->update((int)$id, $data);

        // Sync clients
        $db = \Core\Database::getInstance();
        $db->prepare("DELETE FROM case_clients WHERE case_id = ?")->execute([(int)$id]);
        $clientIds = $_POST['client_ids'] ?? [];
        foreach ($clientIds as $clientId) {
            $this->model->addClient((int)$id, (int)$clientId, $_POST['client_tipos'][$clientId] ?? 'autor');
        }

        SystemLogService::update('cases', 'case', (int)$id, $case, $data);
        Logger::audit("Processo atualizado ID #{$id}");

        Session::flash('success', 'Processo atualizado com sucesso!');
        $this->redirect("/cases/{$id}");
    }

    public function delete(string $id): void
    {
        $this->requirePermission('cases.delete');
        $this->validateCsrf();

        $case = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $this->model->softDelete((int)$id);
        SystemLogService::delete('cases', 'case', (int)$id);
        Logger::audit("Processo excluído ID #{$id}");

        Session::flash('success', 'Processo excluído com sucesso.');
        $this->redirect('/cases');
    }

    public function syncCnj(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findWithDetails((int)$id);
        if (!$case || empty($case['numero_cnj'])) {
            $this->json(['success' => false, 'message' => 'Processo sem número CNJ para sincronizar.']);
        }

        $cnj = new CNJService();
        $result = $cnj->updateProcess((int)$id, $case['numero_cnj'], $case['tribunal'] ?? 'TJSP');
        $this->json($result);
    }

    public function addMovement(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findById((int)$id);
        if (!$case) {
            $this->json(['success' => false, 'message' => 'Processo não encontrado.']);
        }

        $data = [
            'data_movimento'  => $this->input('data_movimento', date('Y-m-d H:i:s')),
            'tipo'            => $this->input('tipo', ''),
            'descricao'       => $this->input('descricao', ''),
            'fonte'           => 'manual',
            'visivel_cliente' => isset($_POST['visivel_cliente']) ? 1 : 0,
            'created_by'      => Session::get('user_id'),
        ];

        if (empty($data['descricao'])) {
            $this->json(['success' => false, 'message' => 'Descrição é obrigatória.']);
        }

        $movId = $this->model->addMovement((int)$id, $data);
        SystemLogService::create('cases', 'movement', $movId, "Movimentação adicionada ao processo #{$id}");

        $this->json(['success' => true, 'message' => 'Movimentação adicionada com sucesso!']);
    }

    public function addDeadline(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findById((int)$id);
        if (!$case) {
            $this->json(['success' => false, 'message' => 'Processo não encontrado.']);
        }

        $tipo       = $this->input('tipo', 'processual');
        $descricao  = $this->input('descricao', '');
        $dataInicio = $this->input('data_inicio', date('Y-m-d'));
        $prazoDias  = (int)$this->input('prazo_dias', '0');
        $dataFinal  = $this->input('data_final', '');

        if (empty($descricao)) {
            $this->json(['success' => false, 'message' => 'Descrição é obrigatória.']);
        }

        // Calculate deadline if days provided
        $dataFinalCalculada = null;
        if ($prazoDias > 0) {
            $dataFinalCalculada = DateHelper::calculateDeadline($dataInicio, $prazoDias, $tipo);
            if (empty($dataFinal)) $dataFinal = $dataFinalCalculada;
        }

        if (empty($dataFinal)) {
            $this->json(['success' => false, 'message' => 'Data final é obrigatória.']);
        }

        $data = [
            'tipo'                => $tipo,
            'descricao'           => $descricao,
            'data_inicio'         => $dataInicio,
            'prazo_dias'          => $prazoDias,
            'data_final'          => $dataFinal,
            'data_final_calculada' => $dataFinalCalculada,
            'confirmado'          => isset($_POST['confirmado']) ? 1 : 0,
            'visivel_cliente'     => isset($_POST['visivel_cliente']) ? 1 : 0,
            'observacoes'         => $this->input('observacoes', ''),
            'created_by'          => Session::get('user_id'),
        ];

        $deadlineId = $this->model->addDeadline((int)$id, $data);
        SystemLogService::create('cases', 'deadline', $deadlineId, "Prazo adicionado ao processo #{$id}");

        $this->json(['success' => true, 'message' => 'Prazo adicionado com sucesso!', 'data_final_calculada' => $dataFinalCalculada]);
    }

    public function addHearing(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findById((int)$id);
        if (!$case) {
            $this->json(['success' => false, 'message' => 'Processo não encontrado.']);
        }

        $titulo = $this->input('titulo', '');
        $data   = $this->input('data', '');
        $hora   = $this->input('hora', '');
        if (empty($titulo) || empty($data) || empty($hora)) {
            $this->json(['success' => false, 'message' => 'Título, data e hora são obrigatórios.']);
        }

        $db = \Core\Database::getInstance();
        $stmt = $db->prepare("INSERT INTO case_hearings (case_id, tipo, titulo, data, hora, local, observacoes, status, visivel_cliente, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, NOW(), NOW())");
        $stmt->execute([
            (int)$id,
            $this->input('tipo', 'conciliacao'),
            $titulo, $data, $hora,
            $this->input('local', ''),
            $this->input('observacoes', ''),
            isset($_POST['visivel_cliente']) ? 1 : 0,
            Session::get('user_id'),
        ]);
        $hearingId = (int)$db->lastInsertId();
        SystemLogService::create('cases', 'hearing', $hearingId, "Audiência adicionada ao processo #{$id}");

        $this->json(['success' => true, 'message' => 'Audiência adicionada com sucesso!']);
    }

    private function collectFormData(): array
    {
        return [
            'numero_cnj'        => $this->input('numero_cnj', ''),
            'tribunal'          => $this->input('tribunal', ''),
            'comarca'           => $this->input('comarca', ''),
            'vara'              => $this->input('vara', ''),
            'classe'            => $this->input('classe', ''),
            'assunto'           => $this->input('assunto', ''),
            'valor_causa'       => (float)str_replace(['.', ','], ['', '.'], $this->input('valor_causa', '0')),
            'fase_processual'   => $this->input('fase_processual', ''),
            'status'            => $this->input('status', 'ativo'),
            'risco_processual'  => $this->input('risco_processual', '') ?: null,
            'probabilidade_exito' => $this->input('probabilidade_exito', '') ?: null,
            'proxima_providencia' => $this->input('proxima_providencia', ''),
            'outras_informacoes' => $this->input('outras_informacoes', ''),
            'responsavel_id'    => (int)$this->input('responsavel_id', '0') ?: null,
        ];
    }
}
