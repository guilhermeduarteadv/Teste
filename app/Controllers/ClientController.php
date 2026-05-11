<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\Client;
use App\Services\SystemLogService;
use App\Helpers\SecurityHelper;
use App\Helpers\ValidationHelper;

class ClientController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new Client();
    }

    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = [
            'search'     => $this->input('search', ''),
            'status'     => $this->input('status', ''),
            'tipo_pessoa' => $this->input('tipo_pessoa', ''),
        ];
        $result = $this->model->findAllPaginated($page, $perPage, $filters);
        $this->render('clients/index', [
            'pageTitle' => 'Clientes',
            'clients'   => $result['data'],
            'pagination' => $result,
            'filters'   => $filters,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('clients.create');
        $this->render('clients/create', ['pageTitle' => 'Novo Cliente']);
    }

    public function store(): void
    {
        $this->requirePermission('clients.create');
        $this->validateCsrf();

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)
            ->required('name', 'Nome')
            ->max('name', 200, 'Nome')
            ->email('email', 'E-mail')
            ->date('data_nascimento', 'Data de Nascimento');

        if (!empty($data['cpf'])) {
            if (!SecurityHelper::validateCpf($data['cpf'])) {
                Session::flash('error', 'CPF inválido.');
                Session::flash('old', $data);
                $this->redirect('/clients/create');
            }
            if ($this->model->documentExists('cpf', preg_replace('/\D/', '', $data['cpf']))) {
                Session::flash('error', 'CPF já cadastrado no sistema.');
                Session::flash('old', $data);
                $this->redirect('/clients/create');
            }
            $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        }

        if (!empty($data['cnpj'])) {
            if (!SecurityHelper::validateCnpj($data['cnpj'])) {
                Session::flash('error', 'CNPJ inválido.');
                Session::flash('old', $data);
                $this->redirect('/clients/create');
            }
            $data['cnpj'] = preg_replace('/\D/', '', $data['cnpj']);
        }

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::flash('old', $data);
            $this->redirect('/clients/create');
        }

        $data['created_by'] = Session::get('user_id');
        $data['cep'] = preg_replace('/\D/', '', $data['cep'] ?? '');
        $data['phone'] = preg_replace('/\D/', '', $data['phone'] ?? '');
        $data['whatsapp'] = preg_replace('/\D/', '', $data['whatsapp'] ?? '');

        $id = $this->model->insert($data);
        SystemLogService::create('clients', 'client', $id, "Novo cliente criado: {$data['name']}");
        Logger::audit("Cliente criado: {$data['name']} ID #{$id}");

        Session::flash('success', "Cliente {$data['name']} cadastrado com sucesso!");
        $this->redirect("/clients/{$id}");
    }

    public function show(string $id): void
    {
        $client = $this->model->findById((int)$id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('/clients');
        }
        $cases    = $this->model->getCases((int)$id);
        $financial = $this->model->getFinancial((int)$id);
        $documents = $this->model->getDocuments((int)$id);
        $tasks    = $this->model->getTasks((int)$id);

        $this->render('clients/show', [
            'pageTitle' => 'Cliente: ' . $client['name'],
            'client'    => $client,
            'cases'     => $cases,
            'financial' => $financial,
            'documents' => $documents,
            'tasks'     => $tasks,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('clients.edit');
        $client = $this->model->findById((int)$id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('/clients');
        }
        $this->render('clients/edit', ['pageTitle' => 'Editar Cliente', 'client' => $client]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('clients.edit');
        $this->validateCsrf();

        $client = $this->model->findById((int)$id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('/clients');
        }

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)
            ->required('name', 'Nome')
            ->email('email', 'E-mail');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/clients/{$id}/edit");
        }

        if (!empty($data['cpf'])) {
            $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        }
        if (!empty($data['cnpj'])) {
            $data['cnpj'] = preg_replace('/\D/', '', $data['cnpj']);
        }
        $data['cep']      = preg_replace('/\D/', '', $data['cep'] ?? '');
        $data['phone']    = preg_replace('/\D/', '', $data['phone'] ?? '');
        $data['whatsapp'] = preg_replace('/\D/', '', $data['whatsapp'] ?? '');

        $this->model->update((int)$id, $data);
        SystemLogService::update('clients', 'client', (int)$id, $client, $data);
        Logger::audit("Cliente atualizado: {$client['name']} ID #{$id}");

        Session::flash('success', 'Cliente atualizado com sucesso!');
        $this->redirect("/clients/{$id}");
    }

    public function delete(string $id): void
    {
        $this->requirePermission('clients.delete');
        $this->validateCsrf();

        $client = $this->model->findById((int)$id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('/clients');
        }

        $this->model->softDelete((int)$id);
        SystemLogService::delete('clients', 'client', (int)$id, "Cliente excluído: {$client['name']}");
        Logger::audit("Cliente excluído: {$client['name']} ID #{$id}");

        Session::flash('success', 'Cliente excluído com sucesso.');
        $this->redirect('/clients');
    }

    public function togglePortalAccess(string $id): void
    {
        $this->requirePermission('clients.edit');
        $client = $this->model->findById((int)$id);
        if (!$client) {
            Session::flash('error', 'Cliente não encontrado.');
            $this->redirect('/clients');
        }

        if ($client['portal_access']) {
            $this->model->disablePortalAccess((int)$id);
            Session::flash('success', 'Acesso ao portal desativado.');
        } else {
            $tempPassword = bin2hex(random_bytes(6));
            $this->model->enablePortalAccess((int)$id, $tempPassword);
            Session::flash('success', "Acesso ao portal ativado. Senha temporária: {$tempPassword}");
        }
        $this->redirect("/clients/{$id}");
    }

    public function searchCep(string $cep): void
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            $this->json(['success' => false, 'message' => 'CEP inválido.']);
        }
        $ch = curl_init("https://viacep.com.br/ws/{$cep}/json/");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            $this->json(['success' => false, 'message' => 'Erro ao consultar CEP.']);
        }
        $data = json_decode($response, true);
        if (isset($data['erro'])) {
            $this->json(['success' => false, 'message' => 'CEP não encontrado.']);
        }
        $this->json(['success' => true, 'data' => [
            'endereco'   => $data['logradouro'] ?? '',
            'bairro'     => $data['bairro'] ?? '',
            'cidade'     => $data['localidade'] ?? '',
            'estado'     => $data['uf'] ?? '',
            'complemento' => $data['complemento'] ?? '',
        ]]);
    }

    private function collectFormData(): array
    {
        return [
            'tipo_pessoa'      => $this->input('tipo_pessoa', 'fisica'),
            'name'             => $this->input('name', ''),
            'cpf'              => $this->input('cpf', ''),
            'cnpj'             => $this->input('cnpj', ''),
            'rg'               => $this->input('rg', ''),
            'data_nascimento'  => $this->input('data_nascimento', '') ?: null,
            'estado_civil'     => $this->input('estado_civil', '') ?: null,
            'profissao'        => $this->input('profissao', ''),
            'phone'            => $this->input('phone', ''),
            'whatsapp'         => $this->input('whatsapp', ''),
            'email'            => $this->input('email', ''),
            'cep'              => $this->input('cep', ''),
            'endereco'         => $this->input('endereco', ''),
            'numero'           => $this->input('numero', ''),
            'complemento'      => $this->input('complemento', ''),
            'bairro'           => $this->input('bairro', ''),
            'cidade'           => $this->input('cidade', ''),
            'estado'           => $this->input('estado', ''),
            'outras_informacoes' => $this->input('outras_informacoes', ''),
            'status'           => $this->input('status', 'active'),
        ];
    }
}
