<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\FinancialEntry;
use App\Models\Client;
use App\Models\LegalCase;
use App\Services\SystemLogService;
use App\Helpers\ValidationHelper;

class FinancialController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new FinancialEntry();
    }

    public function index(): void
    {
        $user = Session::get('user');
        if (($user['role'] ?? '') !== 'admin' && empty($user['permissions']['financial.view'])) {
            Session::flash('error', 'Sem permissão para visualizar o financeiro.');
            $this->redirect('/dashboard');
        }

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $filters = [
            'status'     => $this->input('status', ''),
            'tipo'       => $this->input('tipo', ''),
            'search'     => $this->input('search', ''),
            'data_inicio' => $this->input('data_inicio', ''),
            'data_fim'   => $this->input('data_fim', ''),
        ];
        $result  = $this->model->findAllPaginated($page, $perPage, $filters);
        $summary = $this->model->getSummary();
        $overdue = $this->model->getOverdue();

        $this->render('financial/index', [
            'pageTitle'  => 'Financeiro',
            'entries'    => $result['data'],
            'pagination' => $result,
            'filters'    => $filters,
            'summary'    => $summary,
            'overdue'    => $overdue,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('financial.create');
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $cases   = (new LegalCase())->findAll(['status' => 'ativo'], 'numero_cnj ASC');

        $this->render('financial/create', [
            'pageTitle' => 'Novo Lançamento',
            'clients'   => $clients,
            'cases'     => $cases,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('financial.create');
        $this->validateCsrf();

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)
            ->required('descricao', 'Descrição')
            ->required('tipo', 'Tipo')
            ->required('vencimento', 'Vencimento')
            ->date('vencimento', 'Vencimento')
            ->numeric('valor', 'Valor');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::flash('old', $data);
            $this->redirect('/financial/create');
        }

        // Handle installments
        $totalParcelas = (int)($data['parcela_total'] ?? 1);
        if ($totalParcelas > 1) {
            $vencimento = new \DateTime($data['vencimento']);
            for ($i = 1; $i <= $totalParcelas; $i++) {
                $parcelaData = $data;
                $parcelaData['parcela_numero'] = $i;
                $parcelaData['vencimento'] = $vencimento->format('Y-m-d');
                $parcelaData['created_by'] = Session::get('user_id');
                $this->model->insert($parcelaData);
                $vencimento->modify('+1 month');
            }
            Logger::audit("Lançamento financeiro criado: {$totalParcelas} parcelas");
            Session::flash('success', "Lançamento criado com {$totalParcelas} parcelas!");
        } else {
            $data['created_by'] = Session::get('user_id');
            $id = $this->model->insert($data);
            SystemLogService::create('financial', 'financial_entry', $id);
            Logger::audit("Lançamento financeiro criado ID #{$id}");
            Session::flash('success', 'Lançamento financeiro criado com sucesso!');
        }

        $this->redirect('/financial');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('financial.edit');
        $entry = $this->model->findById((int)$id);
        if (!$entry) {
            Session::flash('error', 'Lançamento não encontrado.');
            $this->redirect('/financial');
        }
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $cases   = (new LegalCase())->findAll([], 'numero_cnj ASC');

        $this->render('financial/edit', [
            'pageTitle' => 'Editar Lançamento',
            'entry'     => $entry,
            'clients'   => $clients,
            'cases'     => $cases,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('financial.edit');
        $this->validateCsrf();

        $entry = $this->model->findById((int)$id);
        if (!$entry) {
            Session::flash('error', 'Lançamento não encontrado.');
            $this->redirect('/financial');
        }

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)->required('descricao', 'Descrição')->required('vencimento', 'Vencimento');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/financial/{$id}/edit");
        }

        $this->model->update((int)$id, $data);
        SystemLogService::update('financial', 'financial_entry', (int)$id, $entry, $data);

        Session::flash('success', 'Lançamento atualizado com sucesso!');
        $this->redirect('/financial');
    }

    public function delete(string $id): void
    {
        $this->requirePermission('financial.delete');
        $this->validateCsrf();
        $entry = $this->model->findById((int)$id);
        if (!$entry) {
            Session::flash('error', 'Lançamento não encontrado.');
            $this->redirect('/financial');
        }
        $this->model->softDelete((int)$id);
        SystemLogService::delete('financial', 'financial_entry', (int)$id);
        Session::flash('success', 'Lançamento excluído com sucesso.');
        $this->redirect('/financial');
    }

    public function markAsPaid(string $id): void
    {
        $this->validateCsrf();
        $entry = $this->model->findById((int)$id);
        if (!$entry) {
            $this->json(['success' => false, 'message' => 'Lançamento não encontrado.']);
        }
        $dataPagamento = $this->input('data_pagamento', date('Y-m-d'));
        $formaPagamento = $this->input('forma_pagamento', 'outros');
        $this->model->markAsPaid((int)$id, $dataPagamento, $formaPagamento);
        SystemLogService::update('financial', 'financial_entry', (int)$id, $entry, ['status' => 'pago']);
        $this->json(['success' => true, 'message' => 'Pagamento registrado com sucesso!']);
    }

    private function collectFormData(): array
    {
        return [
            'client_id'      => (int)$this->input('client_id', '0') ?: null,
            'case_id'        => (int)$this->input('case_id', '0') ?: null,
            'tipo'           => $this->input('tipo', ''),
            'descricao'      => $this->input('descricao', ''),
            'valor'          => (float)str_replace(['.', ','], ['', '.'], $this->input('valor', '0')),
            'vencimento'     => $this->input('vencimento', ''),
            'data_pagamento' => $this->input('data_pagamento', '') ?: null,
            'status'         => $this->input('status', 'pendente'),
            'forma_pagamento' => $this->input('forma_pagamento', '') ?: null,
            'parcela_numero' => (int)$this->input('parcela_numero', '0') ?: null,
            'parcela_total'  => (int)$this->input('parcela_total', '0') ?: null,
            'observacoes'    => $this->input('observacoes', ''),
            'visivel_cliente' => isset($_POST['visivel_cliente']) ? 1 : 0,
        ];
    }
}
