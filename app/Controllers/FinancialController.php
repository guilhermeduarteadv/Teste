<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\SchemaGuardService;
use Core\Session;
use Core\Logger;
use App\Models\FinancialEntry;
use App\Models\Client;
use App\Models\LegalCase;
use App\Models\AdministrativeProcedure;
use App\Models\LegalConsultancy;
use App\Services\SystemLogService;
use App\Helpers\ValidationHelper;

class FinancialController extends Controller
{
    private $model;

    public function __construct()
    {
        (new SchemaGuardService())->ensureV42Schema();
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
        $monthlyRevenue = $this->model->getMonthlyRevenue((int)date('Y'), 12);

        $this->render('financial/index', [
            'pageTitle'  => 'Financeiro',
            'entries'    => $result['data'],
            'pagination' => $result,
            'filters'    => $filters,
            'summary'    => $summary,
            'overdue'    => $overdue,
            'monthlyRevenue' => $monthlyRevenue,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('financial.create');
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $cases   = (new LegalCase())->findAll(['status' => 'ativo'], 'numero_cnj ASC');
        $administrativeProcedures = (new AdministrativeProcedure())->findAll([], 'titulo ASC');
        $consultancies = (new LegalConsultancy())->findAll([], 'titulo ASC');
        $openReceivables = $this->model->getOpenReceivables();

        $this->render('financial/create', [
            'pageTitle' => 'Novo Lançamento',
            'clients'   => $clients,
            'cases'     => $cases,
            'administrativeProcedures' => $administrativeProcedures,
            'consultancies' => $consultancies,
            'preselected' => [
                'client_id' => $_GET['client_id'] ?? '',
                'case_id' => $_GET['case_id'] ?? '',
                'administrative_procedure_id' => $_GET['administrative_procedure_id'] ?? '',
                'consultancy_id' => $_GET['consultancy_id'] ?? '',
            ],
            'openReceivables' => $openReceivables,
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

        $receiptPath = $this->handleReceiptUpload();
        if ($receiptPath !== null) {
            $data['recibo_path'] = $receiptPath;
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
                $childId = $this->model->insert($parcelaData);
                if (!empty($parcelaData['parent_entry_id'])) {
                    $this->model->refreshParentPaymentStatus((int)$parcelaData['parent_entry_id']);
                }
                $vencimento->modify('+1 month');
            }
            Logger::audit("Lançamento financeiro criado: {$totalParcelas} parcelas");
            Session::flash('success', "Lançamento criado com {$totalParcelas} parcelas!");
        } else {
            $data['created_by'] = Session::get('user_id');
            $id = $this->model->insert($data);
            if (!empty($data['parent_entry_id'])) {
                $this->model->refreshParentPaymentStatus((int)$data['parent_entry_id']);
            }
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
        $openReceivables = $this->model->getOpenReceivables((int)$id);
        $linkedPayments = $this->model->getLinkedPayments((int)$id);
        $paymentProgress = $this->model->getPaymentProgress((int)$id);

        $this->render('financial/edit', [
            'pageTitle' => 'Editar Lançamento',
            'entry'     => $entry,
            'clients'   => $clients,
            'cases'     => $cases,
            'openReceivables' => $openReceivables,
            'linkedPayments' => $linkedPayments,
            'paymentProgress' => $paymentProgress,
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
        $receiptPath = $this->handleReceiptUpload($entry['recibo_path'] ?? null);
        if ($receiptPath !== null) {
            $data['recibo_path'] = $receiptPath;
        }
        $validator = ValidationHelper::make($data)->required('descricao', 'Descrição')->required('vencimento', 'Vencimento');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/financial/{$id}/edit");
        }

        $oldParentId = (int)($entry['parent_entry_id'] ?? 0);
        $this->model->update((int)$id, $data);
        $newParentId = (int)($data['parent_entry_id'] ?? 0);
        if ($oldParentId > 0) {
            $this->model->refreshParentPaymentStatus($oldParentId);
        }
        if ($newParentId > 0 && $newParentId !== $oldParentId) {
            $this->model->refreshParentPaymentStatus($newParentId);
        }
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
        $parentId = (int)($entry['parent_entry_id'] ?? 0);
        $this->model->softDelete((int)$id);
        if ($parentId > 0) {
            $this->model->refreshParentPaymentStatus($parentId);
        }
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
        $receiptPath = $this->handleReceiptUpload($entry['recibo_path'] ?? null);
        $this->model->markAsPaid((int)$id, $dataPagamento, $formaPagamento, $receiptPath);
        if (!empty($entry['parent_entry_id'])) {
            $this->model->refreshParentPaymentStatus((int)$entry['parent_entry_id']);
        }
        SystemLogService::update('financial', 'financial_entry', (int)$id, $entry, ['status' => 'pago']);

        if (!$this->isAjax()) {
            Session::flash('success', 'Pagamento registrado com sucesso!');
            $this->redirect('/financial');
            return;
        }

        $this->json(['success' => true, 'message' => 'Pagamento registrado com sucesso!']);
    }


    public function receipt(string $id): void
    {
        $user = Session::get('user');
        if (($user['role'] ?? '') !== 'admin' && empty($user['permissions']['financial.view'])) {
            http_response_code(403);
            echo 'Sem permissão.';
            return;
        }

        $entry = $this->model->findById((int)$id);
        if (!$entry || empty($entry['recibo_path'])) {
            http_response_code(404);
            echo 'Comprovante não encontrado.';
            return;
        }

        $relativePath = str_replace(['..', '\\'], ['', '/'], (string)$entry['recibo_path']);
        $file = ROOT_PATH . '/' . ltrim($relativePath, '/');

        if (!is_file($file)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp'
        ];

        header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    private function handleReceiptUpload(?string $currentPath = null): ?string
    {
        if (empty($_FILES['comprovante']) || !isset($_FILES['comprovante']['error'])) {
            return $currentPath;
        }

        if ($_FILES['comprovante']['error'] === UPLOAD_ERR_NO_FILE) {
            return $currentPath;
        }

        if ($_FILES['comprovante']['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha ao enviar o comprovante de pagamento. Código: ' . $_FILES['comprovante']['error']);
        }

        $maxSize = 10 * 1024 * 1024; // 10 MB
        if ((int)$_FILES['comprovante']['size'] > $maxSize) {
            throw new \RuntimeException('O comprovante deve ter no máximo 10 MB.');
        }

        $originalName = (string)($_FILES['comprovante']['name'] ?? 'comprovante');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            throw new \RuntimeException('Formato de comprovante inválido. Use PDF, JPG, PNG ou WEBP.');
        }

        $dir = ROOT_PATH . '/storage/financial_receipts';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $safeName = 'comprovante_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $dir . '/' . $safeName;

        if (!move_uploaded_file($_FILES['comprovante']['tmp_name'], $destination)) {
            throw new \RuntimeException('Não foi possível salvar o comprovante no servidor.');
        }

        return 'storage/financial_receipts/' . $safeName;
    }

    private function collectFormData(): array
    {
        $parentEntryId = (int)$this->input('parent_entry_id', '0') ?: null;
        $clientId = (int)$this->input('client_id', '0') ?: null;
        $caseId = (int)$this->input('case_id', '0') ?: null;
        $administrativeProcedureId = (int)$this->input('administrative_procedure_id', '0') ?: null;
        $consultancyId = (int)$this->input('consultancy_id', '0') ?: null;

        if ($parentEntryId) {
            $parent = $this->model->findById($parentEntryId);
            if ($parent) {
                $clientId = $clientId ?: ((int)($parent['client_id'] ?? 0) ?: null);
                $caseId = $caseId ?: ((int)($parent['case_id'] ?? 0) ?: null);
                $administrativeProcedureId = $administrativeProcedureId ?: ((int)($parent['administrative_procedure_id'] ?? 0) ?: null);
                $consultancyId = $consultancyId ?: ((int)($parent['consultancy_id'] ?? 0) ?: null);
            }
        }

        return [
            'client_id'      => $clientId,
            'case_id'        => $caseId,
            'administrative_procedure_id' => $administrativeProcedureId,
            'consultancy_id'  => $consultancyId,
            'entity_type'     => $administrativeProcedureId ? 'administrative_procedure' : ($consultancyId ? 'consultancy' : ($caseId ? 'case' : '')),
            'entity_id'       => $administrativeProcedureId ?: ($consultancyId ?: ($caseId ?: null)),
            'parent_entry_id' => $parentEntryId,
            'is_payment'     => $parentEntryId ? 1 : (isset($_POST['is_payment']) ? 1 : 0),
            'tipo'           => $this->input('tipo', ''),
            'descricao'      => $this->input('descricao', ''),
            'valor'          => (float)str_replace(['.', ','], ['', '.'], $this->input('valor', '0')),
            'vencimento'     => $this->input('vencimento', ''),
            'data_pagamento' => $this->input('data_pagamento', '') ?: ($parentEntryId ? date('Y-m-d') : null),
            'status'         => $parentEntryId ? $this->input('status', 'pago') : $this->input('status', 'pendente'),
            'forma_pagamento' => $this->input('forma_pagamento', '') ?: null,
            'parcela_numero' => (int)$this->input('parcela_numero', '0') ?: null,
            'parcela_total'  => (int)$this->input('parcela_total', '0') ?: null,
            'observacoes'    => $this->input('observacoes', ''),
            'visivel_cliente' => isset($_POST['visivel_cliente']) ? 1 : 0,
        ];
    }
}
