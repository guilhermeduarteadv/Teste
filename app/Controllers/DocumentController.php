<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\Document;
use App\Services\SystemLogService;
use App\Helpers\SecurityHelper;

class DocumentController extends Controller
{
    private $model;
    private $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
    private $allowedMimes = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct()
    {
        $this->model = new Document();
    }

    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $filters = ['search' => $this->input('search', ''), 'categoria' => $this->input('categoria', ''), 'entity_type' => $this->input('entity_type', '')];
        $result  = $this->model->findAllPaginated($page, $perPage, $filters);
        $this->render('documents/index', [
            'pageTitle'  => 'Documentos',
            'documents'  => $result['data'],
            'pagination' => $result,
            'filters'    => $filters,
        ]);
    }

    public function upload(): void
    {
        $this->requirePermission('documents.create');
        $this->validateCsrf();

        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload.']);
        }

        $file = $_FILES['document'];
        $originalName = $file['name'];
        $tmpPath = $file['tmp_name'];
        $size = $file['size'];

        // Validate size (20MB max)
        if ($size > 20 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'Arquivo muito grande. Máximo 20MB.']);
        }

        // Validate extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->json(['success' => false, 'message' => 'Extensão não permitida. Permitido: ' . implode(', ', $this->allowedExtensions)]);
        }

        // Validate MIME type. AppServ sometimes runs PHP without fileinfo/finfo enabled.
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpPath);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($tmpPath);
        } else {
            $mimeType = $this->allowedMimes[$extension] ?? 'application/octet-stream';
        }
        if (!in_array($mimeType, array_values($this->allowedMimes), true)) {
            if (isset($this->allowedMimes[$extension])) {
                $mimeType = $this->allowedMimes[$extension];
            } else {
                Logger::security("Invalid MIME type upload attempt: {$mimeType}", ['file' => $originalName]);
                $this->json(['success' => false, 'message' => 'Tipo de arquivo inválido ou corrompido.']);
            }
        }

        // Check for executables
        $dangerousMimes = ['application/x-executable', 'application/x-sharedlib', 'text/x-php', 'application/x-php'];
        if (in_array($mimeType, $dangerousMimes)) {
            Logger::security("Dangerous file upload blocked: {$mimeType}", ['file' => $originalName]);
            $this->json(['success' => false, 'message' => 'Tipo de arquivo não permitido por segurança.']);
        }

        // Store file with hash name
        $storagePath = ROOT_PATH . '/storage/documents';
        if (!is_dir($storagePath)) mkdir($storagePath, 0755, true);

        $storedName = hash('sha256', $originalName . time() . random_bytes(16)) . '.' . $extension;
        $subDir = substr($storedName, 0, 2);
        $fullDir = $storagePath . '/' . $subDir;
        if (!is_dir($fullDir)) mkdir($fullDir, 0755, true);

        $fullPath = $fullDir . '/' . $storedName;
        if (!move_uploaded_file($tmpPath, $fullPath)) {
            Logger::error("Failed to move uploaded file: {$originalName}");
            $this->json(['success' => false, 'message' => 'Erro ao salvar arquivo. Tente novamente.']);
        }

        // Set restrictive permissions
        chmod($fullPath, 0644);

        $entityType = $this->input('entity_type', 'client');
        $entityId   = (int)$this->input('entity_id', '0');

        $docData = [
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'categoria'      => $this->input('categoria', 'outros'),
            'descricao'      => $this->input('descricao', $originalName),
            'original_name'  => SecurityHelper::sanitizeFileName($originalName),
            'stored_name'    => $storedName,
            'path'           => $subDir . '/' . $storedName,
            'mime_type'      => $mimeType,
            'extension'      => $extension,
            'size'           => $size,
            'visivel_cliente' => isset($_POST['visivel_cliente']) ? 1 : 0,
            'uploaded_by'    => Session::get('user_id'),
        ];

        $id = $this->model->insert($docData);
        SystemLogService::upload('documents', $entityType, $entityId, $originalName);
        Logger::audit("Documento enviado: {$originalName} ID #{$id}");

        $this->json(['success' => true, 'message' => 'Documento enviado com sucesso!', 'id' => $id]);
    }

    public function download(string $id): void
    {
        $doc = $this->model->findById((int)$id);
        if (!$doc) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }

        $filePath = ROOT_PATH . '/storage/documents/' . $doc['path'];
        if (!file_exists($filePath)) {
            Logger::error("Document file not found on disk: {$doc['path']}");
            http_response_code(404);
            echo 'Arquivo não encontrado no servidor.';
            return;
        }

        SystemLogService::download('documents', $doc['entity_type'], $doc['entity_id'], $doc['original_name']);
        Logger::audit("Documento baixado: {$doc['original_name']} ID #{$id}");

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: attachment; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . $doc['size']);
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    public function preview(string $id): void
    {
        $doc = $this->model->findById((int)$id);
        if (!$doc) {
            http_response_code(404);
            echo 'Documento não encontrado.';
            return;
        }

        $filePath = ROOT_PATH . '/storage/documents/' . $doc['path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        // Only allow preview for PDF and images
        if (!in_array($doc['extension'], ['pdf', 'jpg', 'jpeg', 'png'])) {
            $this->download($id);
            return;
        }

        header('Content-Type: ' . $doc['mime_type']);
        header('Content-Disposition: inline; filename="' . $doc['original_name'] . '"');
        header('Content-Length: ' . $doc['size']);
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    public function delete(string $id): void
    {
        $this->requirePermission('documents.delete');
        $this->validateCsrf();

        $doc = $this->model->findById((int)$id);
        if (!$doc) {
            $this->json(['success' => false, 'message' => 'Documento não encontrado.']);
        }

        // Soft delete DB record
        $this->model->softDelete((int)$id);

        // Optionally remove physical file (keep for audit, just soft delete)
        SystemLogService::delete('documents', 'document', (int)$id, "Documento excluído: {$doc['original_name']}");
        Logger::audit("Documento excluído: {$doc['original_name']} ID #{$id}");

        $this->json(['success' => true, 'message' => 'Documento excluído com sucesso.']);
    }
}
