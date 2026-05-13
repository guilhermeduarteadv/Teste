<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\SchemaGuardService;
use Core\Session;
use Core\Logger;
use App\Models\ClientModel;
use App\Models\CaseModel;
use App\Models\FinancialModel;
use App\Models\DocumentModel;
use App\Services\TimelineService;
use Core\Database;

class PortalController extends Controller
{
    private $clientModel;
    private $caseModel;
    private $financialModel;
    private $documentModel;

    public function __construct()
    {
        (new SchemaGuardService())->ensureV42Schema();
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
        $this->render('portal/login', [
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
        $client = $this->clientModel->queryOne("SELECT * FROM clients WHERE email = ? AND portal_access = 1 AND deleted_at IS NULL LIMIT 1",[$email]);

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
        $timelineService = new TimelineService();

        foreach ($cases as $case) {
            $movements = $this->caseModel->queryOne(
                "SELECT cm.* FROM case_movements cm WHERE cm.case_id = ? AND cm.visivel_cliente = 1 ORDER BY cm.data_movimento DESC LIMIT 3",
                [$case['id']]
            );

            $case['last_movement'] = $movements;

            try {
                $timelineService->rebuildCaseTimeline((int)$case['id']);
                $case['timeline_preview'] = array_slice(
                    $timelineService->getCaseTimeline((int)$case['id'], true),
                    -5
                );
                $case['timeline_progress'] = $timelineService->calculateCaseProgress((int)$case['id']);
            } catch (\Throwable $e) {
                $case['timeline_preview'] = [];
                $case['timeline_progress'] = 0;
            }

            $casesFiltered[] = $case;
        }

        $this->render('portal/cases', [
            'title'  => 'Meus Processos - Portal do Cliente',
            'client' => $client,
            'cases'  => $casesFiltered,
        ], 'portal');
    }


    public function caseTimeline(string $caseId): void
    {
        $client = $this->requirePortalAuth();
        $caseId = (int)$caseId;

        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT c.*
            FROM cases c
            INNER JOIN case_clients cc ON cc.case_id = c.id
            WHERE c.id = ?
              AND cc.client_id = ?
              AND c.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$caseId, (int)$client['id']]);
        $case = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$case) {
            \Core\Session::flash('error', 'Processo não encontrado no portal.');
            $this->redirect('/portal/cases');
            return;
        }

        $service = new TimelineService();
        $service->rebuildCaseTimeline($caseId);
        $timeline = $service->getCaseTimeline($caseId, true);
        $progress = $service->calculateCaseProgress($caseId);

        $this->render('portal/case_timeline', [
            'title' => 'Linha do tempo - Portal do Cliente',
            'client' => $client,
            'case' => $case,
            'timeline' => $timeline,
            'progress' => $progress,
        ], 'portal');
    }


    public function uploadDocument(): void
    {
        $client = $this->requirePortalAuth();

        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Nenhum arquivo enviado ou erro no upload.');
            $this->redirect('/portal/documents');
            return;
        }

        $file = $_FILES['document'];
        $maxTotal = 10 * 1024 * 1024;

        $db = Database::getInstance();
        $sizeColumn = $this->tableColumnExists('documents', 'tamanho_bytes') ? 'tamanho_bytes' : 'size';
        $clientColumn = $this->tableColumnExists('documents', 'uploaded_by_client_id') ? 'uploaded_by_client_id' : 'entity_id';

        $stmt = $db->prepare("SELECT COALESCE(SUM({$sizeColumn}),0) FROM documents WHERE {$clientColumn} = ? AND deleted_at IS NULL");
        $stmt->execute([(int)$client['id']]);
        $used = (int)$stmt->fetchColumn();

        if ($used + (int)$file['size'] > $maxTotal) {
            Session::flash('error', 'Limite total de 10MB excedido. Arquivos maiores devem ser enviados por e-mail.');
            $this->redirect('/portal/documents');
            return;
        }

        $allowed = ['pdf','jpg','jpeg','png','doc','docx'];
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            Session::flash('error', 'Extensão não permitida. Permitido: PDF, JPG, PNG, DOC e DOCX.');
            $this->redirect('/portal/documents');
            return;
        }

        $category = trim($_POST['categoria'] ?? 'documentos_cliente');
        $storageDir = ROOT_PATH . '/storage/client_uploads/' . (int)$client['id'];
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $safeName = date('YmdHis') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $dest = $storageDir . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Falha ao salvar o arquivo.');
            $this->redirect('/portal/documents');
            return;
        }

        $relative = 'storage/client_uploads/' . (int)$client['id'] . '/' . $safeName;

        $hasTamanho = $this->tableColumnExists('documents', 'tamanho_bytes');
        $hasUploadedClient = $this->tableColumnExists('documents', 'uploaded_by_client_id');
        $hasOrigemUpload = $this->tableColumnExists('documents', 'origem_upload');

        $columns = ['title', 'filename', 'original_name', 'path', 'mime_type', 'size', 'categoria', 'entity_type', 'entity_id', 'visivel_cliente', 'created_at', 'updated_at'];
        $values = [
            trim($_POST['title'] ?? $originalName),
            $safeName,
            $originalName,
            $relative,
            $file['type'] ?? '',
            (int)$file['size'],
            $category,
            'client',
            (int)$client['id'],
            1,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ];

        if ($hasTamanho) {
            $columns[] = 'tamanho_bytes';
            $values[] = (int)$file['size'];
        }

        if ($hasUploadedClient) {
            $columns[] = 'uploaded_by_client_id';
            $values[] = (int)$client['id'];
        }

        if ($hasOrigemUpload) {
            $columns[] = 'origem_upload';
            $values[] = 'portal_cliente';
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO documents (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);

        Session::flash('success', 'Documento enviado com sucesso.');
        $this->redirect('/portal/documents');
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

        $this->render('portal/financial', [
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
            "SELECT DISTINCT d.*, u.name AS uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             LEFT JOIN case_clients cc ON d.entity_type = 'case' AND d.entity_id = cc.case_id
             WHERE d.deleted_at IS NULL
               AND d.visivel_cliente = 1
               AND (
                    (d.entity_type = 'client' AND d.entity_id = ?)
                    OR (cc.client_id = ?)
                    OR (d.uploaded_by_client_id = ?)
               )
             ORDER BY d.created_at DESC",
            [(int)$client['id'], (int)$client['id'], (int)$client['id']]
        );

        $db = Database::getInstance();
        $sizeColumn = $this->tableColumnExists('documents', 'tamanho_bytes') ? 'tamanho_bytes' : 'size';
        $clientColumn = $this->tableColumnExists('documents', 'uploaded_by_client_id') ? 'uploaded_by_client_id' : 'entity_id';

        $stmt = $db->prepare("SELECT COALESCE(SUM({$sizeColumn}),0) FROM documents WHERE {$clientColumn} = ? AND deleted_at IS NULL");
        $stmt->execute([(int)$client['id']]);
        $usedBytes = (int)$stmt->fetchColumn();
        $limitBytes = 10 * 1024 * 1024;

        $this->render('portal/documents', [
            'title'     => 'Documentos - Portal do Cliente',
            'client'    => $client,
            'documents' => $documents,
            'usedBytes' => $usedBytes,
            'limitBytes' => $limitBytes,
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

        if (!$doc) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }
        $filePath = ROOT_PATH . '/storage/documents/' . $doc['path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }

        Logger::info('Portal document download', ['doc_id' => $id, 'client_id' => $client['id']]);

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    private function tableColumnExists(string $table, string $column): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }


    public function clientRequestsPortal(): void
    {
        $client = $this->requirePortalAuth();
        $db = \Core\Database::getInstance();
        try {
            $items = $db->prepare("SELECT * FROM client_requests WHERE client_id = ? AND visible_client = 1 ORDER BY created_at DESC");
            $items->execute([(int)$client['id']]);
            $requests = $items->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $requests = [];
        }

        // Load replies (portal_messages) keyed by request id
        $replies = [];
        try {
            if (!empty($requests)) {
                $ids = array_map(function ($r) { return (int)$r['id']; }, $requests);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmtR = $db->prepare(
                    "SELECT pm.* FROM portal_messages pm
                     WHERE pm.client_id = ? AND pm.deleted_at IS NULL
                     ORDER BY pm.created_at ASC"
                );
                $stmtR->execute([(int)$client['id']]);
                $allMsgs = $stmtR->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($allMsgs as $msg) {
                    $replies[] = $msg;
                }
            }
        } catch (\Throwable $e) {
            $replies = [];
        }

        $this->render('portal/client_requests', [
            'title'    => 'Pendências - Portal do Cliente',
            'client'   => $client,
            'requests' => $requests,
            'replies'  => $replies,
            'csrf_token' => Session::csrfToken(),
        ], 'portal');
    }

    public function replyRequest(string $id): void
    {
        $client = $this->requirePortalAuth();
        $this->validateCsrf();

        $requestId = (int)$id;
        $message   = trim($_POST['message'] ?? '');
        $subject   = trim($_POST['subject'] ?? 'Resposta à pendência #' . $requestId);

        if ($message === '') {
            Session::flash('error', 'A mensagem não pode estar vazia.');
            $this->redirect('/portal/requests');
            return;
        }

        $db = Database::getInstance();

        // Verify request belongs to this client
        try {
            $stmt = $db->prepare(
                "SELECT id FROM client_requests WHERE id = ? AND client_id = ? AND visible_client = 1 LIMIT 1"
            );
            $stmt->execute([$requestId, (int)$client['id']]);
            if (!$stmt->fetch()) {
                Session::flash('error', 'Pendência não encontrada.');
                $this->redirect('/portal/requests');
                return;
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'Erro ao verificar pendência.');
            $this->redirect('/portal/requests');
            return;
        }

        try {
            $stmt = $db->prepare(
                "INSERT INTO portal_messages (client_id, subject, message, sender_type, sender_id, created_at)
                 VALUES (?, ?, ?, 'client', ?, NOW())"
            );
            $stmt->execute([(int)$client['id'], $subject, $message, (int)$client['id']]);
        } catch (\Throwable $e) {
            Session::flash('error', 'Erro ao enviar mensagem.');
            $this->redirect('/portal/requests');
            return;
        }

        Session::flash('success', 'Mensagem enviada com sucesso.');
        $this->redirect('/portal/requests');
    }

}
