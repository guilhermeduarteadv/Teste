<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\ClientModel;
use App\Models\CaseModel;
use App\Models\FinancialModel;
use App\Models\DocumentModel;

class PortalController extends Controller
{
    private $clientModel;
    private $caseModel;
    private $financialModel;
    private $documentModel;

    public function __construct()
    {
        $this->clientModel    = new ClientModel();
        $this->caseModel      = new CaseModel();
        $this->financialModel = new FinancialModel();
        $this->documentModel  = new DocumentModel();
    }

    private function requirePortalAuth(): array
    {
        $client = Session::get('portal_client');
        if (!$client) {
            $this->redirect('/portal/login');
        }
        return $client;
    }

    public function index(): void
    {
        $client = Session::get('portal_client');
        if ($client) {
            $this->redirect('/portal/cases');
        }
        $this->redirect('/portal/login');
    }

    public function showLogin(): void
    {
        if (Session::get('portal_client')) {
            $this->redirect('/portal/cases');
        }
        $this->render('portal.login', [
            'title'      => 'Portal do Cliente - JurisControl',
            'csrf_token' => Session::csrfToken(),
            'error'      => Session::getFlash('error'),
        ], 'portal');
    }

    public function login(): void
    {
        $this->validateCsrf();

        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            Session::flash('error', 'E-mail e senha são obrigatórios.');
            $this->redirect('/portal/login');
            return;
        }

        // Find client by email
        $client = $this->clientModel->queryOne(
            "SELECT * FROM clients WHERE email = ? AND portal_access = 1 AND deleted_at IS NULL LIMIT 1",
            [$email]
        );

        if (!$client || !password_verify($password, $client['portal_password'] ?? '')) {
            Logger::security('Portal login failed', ['email' => $email]);
            Session::flash('error', 'E-mail ou senha incorretos.');
            $this->redirect('/portal/login');
            return;
        }

        Session::regenerate();
        Session::set('portal_client', [
            'id'    => $client['id'],
            'name'  => $client['name'],
            'email' => $client['email'],
        ]);

        Logger::info('Portal login successful', ['client_id' => $client['id']]);
        $this->redirect('/portal/cases');
    }

    public function logout(): void
    {
        Session::remove('portal_client');
        Session::flash('success', 'Você saiu do portal.');
        $this->redirect('/portal/login');
    }

    public function cases(): void
    {
        $client = $this->requirePortalAuth();
        $cases  = $this->clientModel->getClientCases($client['id']);

        // Filter to only show visible info
        $casesFiltered = [];
        foreach ($cases as $case) {
            $movements = $this->caseModel->queryOne(
                "SELECT cm.* FROM case_movements cm WHERE cm.case_id = ? AND cm.visivel_cliente = 1 ORDER BY cm.data_movimento DESC LIMIT 3",
                [$case['id']]
            );
            $case['last_movement'] = $movements;
            $casesFiltered[] = $case;
        }

        $this->render('portal.cases', [
            'title'  => 'Meus Processos - Portal do Cliente',
            'client' => $client,
            'cases'  => $casesFiltered,
        ], 'portal');
    }

    public function financial(): void
    {
        $client  = $this->requirePortalAuth();
        $entries = $this->financialModel->query(
            "SELECT fe.*, c.numero_cnj FROM financial_entries fe
             LEFT JOIN cases c ON fe.case_id = c.id
             WHERE fe.client_id = ? AND fe.visivel_cliente = 1 AND fe.deleted_at IS NULL
             ORDER BY fe.vencimento DESC",
            [$client['id']]
        );

        $summary = $this->financialModel->getClientFinancialSummary($client['id']);

        $this->render('portal.financial', [
            'title'   => 'Financeiro - Portal do Cliente',
            'client'  => $client,
            'entries' => $entries,
            'summary' => $summary,
        ], 'portal');
    }

    public function documents(): void
    {
        $client    = $this->requirePortalAuth();
        $documents = $this->documentModel->query(
            "SELECT d.*, u.name AS uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.entity_type = 'client' AND d.entity_id = ? AND d.visivel_cliente = 1 AND d.deleted_at IS NULL
             ORDER BY d.created_at DESC",
            [$client['id']]
        );

        $this->render('portal.documents', [
            'title'     => 'Documentos - Portal do Cliente',
            'client'    => $client,
            'documents' => $documents,
        ], 'portal');
    }

    public function downloadDocument(string $id): void
    {
        $client = $this->requirePortalAuth();

        $doc = $this->documentModel->queryOne(
            "SELECT d.* FROM documents d
             WHERE d.id = ? AND d.entity_type = 'client' AND d.entity_id = ?
             AND d.visivel_cliente = 1 AND d.deleted_at IS NULL LIMIT 1",
            [$id, $client['id']]
        );

        if (!$doc || !file_exists($doc['path'])) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }

        Logger::info('Portal document download', ['doc_id' => $id, 'client_id' => $client['id']]);

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . filesize($doc['path']));
        readfile($doc['path']);
        exit;
    }
}
