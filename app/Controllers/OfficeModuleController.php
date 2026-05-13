<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\GenericModel;
use App\Models\Client;
use App\Models\LegalCase;
use App\Services\SchemaGuardService;

class OfficeModuleController extends Controller
{
    public function __construct()
    {
        (new SchemaGuardService())->ensureV52Schema();
    }

    public function contracts(): void
    {
        (new SchemaGuardService())->ensureV52Schema();
        $model = new GenericModel('legal_fee_contracts');
        $items = $model->findAll([], 'created_at DESC', 200);
        $this->render('office_modules/contracts', ['pageTitle' => 'Contratos de Honorários', 'items' => $items]);
    }

    public function contractCreate(): void
    {
        $this->render('office_modules/contract_form', [
            'pageTitle' => 'Novo Contrato de Honorários',
            'clients' => (new Client())->findAll(['status' => 'active'], 'name ASC'),
            'cases' => (new LegalCase())->findAll(['status' => 'ativo'], 'numero_cnj ASC'),
            'item' => []
        ]);
    }

    public function contractStore(): void
    {
        $this->validateCsrf();
        $model = new GenericModel('legal_fee_contracts');
        $model->insert([
            'client_id' => (int)($_POST['client_id'] ?? 0) ?: null,
            'case_id' => (int)($_POST['case_id'] ?? 0) ?: null,
            'title' => trim($_POST['title'] ?? ''),
            'fee_type' => trim($_POST['fee_type'] ?? 'fixo'),
            'fixed_amount' => (float)str_replace(',', '.', $_POST['fixed_amount'] ?? '0'),
            'monthly_amount' => (float)str_replace(',', '.', $_POST['monthly_amount'] ?? '0'),
            'success_percentage' => (float)str_replace(',', '.', $_POST['success_percentage'] ?? '0'),
            'success_minimum' => (float)str_replace(',', '.', $_POST['success_minimum'] ?? '0'),
            'installments' => (int)($_POST['installments'] ?? 0) ?: null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'status' => $_POST['status'] ?? 'ativo',
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        Session::flash('success', 'Contrato cadastrado.');
        $this->redirect('/contracts');
    }

    public function payables(): void
    {
        $model = new GenericModel('payable_entries');
        $items = $model->findAll([], 'due_date DESC, created_at DESC', 200);
        $this->render('office_modules/payables', ['pageTitle' => 'Contas a Pagar', 'items' => $items]);
    }

    public function payableStore(): void
    {
        $this->validateCsrf();
        $model = new GenericModel('payable_entries');
        $model->insert([
            'description' => trim($_POST['description'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'supplier' => trim($_POST['supplier'] ?? ''),
            'amount' => (float)str_replace(',', '.', $_POST['amount'] ?? '0'),
            'due_date' => $_POST['due_date'] ?? null,
            'payment_date' => $_POST['payment_date'] ?? null,
            'status' => $_POST['status'] ?? 'pendente',
            'payment_method' => trim($_POST['payment_method'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        Session::flash('success', 'Conta cadastrada.');
        $this->redirect('/payables');
    }

    public function dre(): void
    {
        $db = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));
        try {
            $receitas = $db->query("SELECT DATE_FORMAT(COALESCE(data_pagamento,vencimento,created_at),'%Y-%m') mes, SUM(valor) total FROM financial_entries WHERE deleted_at IS NULL AND status='pago' AND YEAR(COALESCE(data_pagamento,vencimento,created_at))={$year} GROUP BY mes")->fetchAll();
        } catch (\Throwable $e) { $receitas = []; }
        try {
            $despesas = $db->query("SELECT DATE_FORMAT(COALESCE(payment_date,due_date,created_at),'%Y-%m') mes, SUM(amount) total FROM payable_entries WHERE deleted_at IS NULL AND status='pago' AND YEAR(COALESCE(payment_date,due_date,created_at))={$year} GROUP BY mes")->fetchAll();
        } catch (\Throwable $e) { $despesas = []; }
        $this->render('office_modules/dre', ['pageTitle' => 'DRE Simples', 'receitas' => $receitas, 'despesas' => $despesas, 'year' => $year]);
    }

    public function leads(): void
    {
        $model = new GenericModel('leads');
        $items = $model->findAll([], 'created_at DESC', 200);
        $this->render('office_modules/leads', ['pageTitle' => 'Leads / Comercial', 'items' => $items]);
    }

    public function leadStore(): void
    {
        $this->validateCsrf();
        $model = new GenericModel('leads');
        $model->insert([
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'source' => trim($_POST['source'] ?? ''),
            'area' => trim($_POST['area'] ?? ''),
            'legal_issue' => trim($_POST['legal_issue'] ?? ''),
            'estimated_value' => (float)str_replace(',', '.', $_POST['estimated_value'] ?? '0'),
            'status' => $_POST['status'] ?? 'novo',
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        Session::flash('success', 'Lead cadastrado.');
        $this->redirect('/leads');
    }

    public function checklists(): void
    {
        $model = new GenericModel('checklist_templates');
        $items = $model->findAll([], 'name ASC', 200);
        $this->render('office_modules/checklists', ['pageTitle' => 'Checklists', 'items' => $items]);
    }

    public function checklistStore(): void
    {
        $this->validateCsrf();
        $items = array_filter(array_map('trim', explode("\n", $_POST['items'] ?? '')));
        $model = new GenericModel('checklist_templates');
        $model->insert([
            'name' => trim($_POST['name'] ?? ''),
            'module' => trim($_POST['module'] ?? ''),
            'procedure_type' => trim($_POST['procedure_type'] ?? ''),
            'items_json' => json_encode(array_values($items), JSON_UNESCAPED_UNICODE),
            'active' => 1,
        ]);
        Session::flash('success', 'Checklist criado.');
        $this->redirect('/checklists');
    }

    public function clientRequests(): void
    {
        $db = Database::getInstance();
        try {
            $items = $db->query("SELECT cr.*, cl.name AS client_name FROM client_requests cr LEFT JOIN clients cl ON cl.id=cr.client_id ORDER BY cr.created_at DESC LIMIT 200")->fetchAll();
        } catch (\Throwable $e) { $items = []; }
        $this->render('office_modules/client_requests', ['pageTitle' => 'Pendências do Cliente', 'items' => $items, 'clients' => (new Client())->findAll(['status'=>'active'], 'name ASC')]);
    }

    public function clientRequestStore(): void
    {
        $this->validateCsrf();
        $model = new GenericModel('client_requests');
        $model->insert([
            'client_id' => (int)($_POST['client_id'] ?? 0),
            'case_id' => (int)($_POST['case_id'] ?? 0) ?: null,
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'request_type' => trim($_POST['request_type'] ?? ''),
            'status' => 'pendente',
            'due_date' => $_POST['due_date'] ?? null,
            'visible_client' => 1,
        ]);
        Session::flash('success', 'Pendência criada.');
        $this->redirect('/client-requests');
    }
}
