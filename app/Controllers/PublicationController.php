<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use App\Services\DjeTJSPPublicationService;

class PublicationController extends Controller
{
    public function index(): void
    {
        $rows = Database::getInstance()->query("SELECT p.*, c.id as linked_case_id FROM publications p LEFT JOIN cases c ON p.case_id=c.id WHERE p.deleted_at IS NULL ORDER BY p.data_publicacao DESC, p.id DESC LIMIT 300")->fetchAll();
        $this->render('publications/index', ['pageTitle' => 'Publicações', 'publications' => $rows]);
    }

    public function importManual(): void
    {
        $this->validateCsrf();
        $texto = $this->input('texto', '');
        if ($texto === '') {
            $this->json(['success' => false, 'message' => 'Texto da publicação é obrigatório.']);
        }
        preg_match('/\d{7}-\d{2}\.\d{4}\.\d\.\d{2}\.\d{4}/', $texto, $m);
        $numero = $m[0] ?? $this->input('numero_cnj', '');
        $db = Database::getInstance();
        $caseId = null;
        if ($numero) {
            $st = $db->prepare("SELECT id FROM cases WHERE numero_cnj=? AND deleted_at IS NULL LIMIT 1");
            $st->execute([$numero]);
            $caseId = $st->fetchColumn() ?: null;
        }
        $hash = md5($numero . '|' . $texto . '|' . $this->input('data_publicacao', date('Y-m-d')));
        $st = $db->prepare("INSERT IGNORE INTO publications (case_id, numero_cnj, tribunal, diario, data_publicacao, titulo, texto, fonte, hash, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())");
        $st->execute([$caseId, $numero, 'tjsp', $this->input('diario', 'DJE'), $this->input('data_publicacao', date('Y-m-d')), $this->input('titulo', 'Publicação'), $texto, 'manual', $hash]);
        if ($caseId) {
            $mv = $db->prepare("INSERT INTO case_movements (case_id, data_movimento, tipo, descricao, fonte, hash, created_at) VALUES (?, ?, 'publicacao', ?, 'import', ?, NOW())");
            $mv->execute([$caseId, $this->input('data_publicacao', date('Y-m-d')) . ' 00:00:00', $texto, $hash]);
        }
        $this->json(['success' => true, 'message' => 'Publicação importada e vinculada quando houve processo correspondente.']);
    }

    public function readDje(): void
    {
        $this->validateCsrf();
        try {
            $oab = (string)$this->input('oab_number', '');
            $uf = (string)$this->input('oab_state', 'SP');
            $start = (string)$this->input('date_start', date('Y-m-d', strtotime('-7 days')));
            $end = (string)$this->input('date_end', date('Y-m-d'));
            $extra = (string)$this->input('query_extra', '');

            $service = new DjeTJSPPublicationService();
            $result = $service->searchAndImportByOab($oab, $uf, $start, $end, $extra);
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Erro ao ler DJE/TJSP: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    }

    public function readText(): void
    {
        $this->validateCsrf();
        try {
            $texto = (string)$this->input('diary_text', '');
            $data = (string)$this->input('data_publicacao_texto', date('Y-m-d'));
            $service = new DjeTJSPPublicationService();
            $result = $service->importFromText($texto, $data, 'texto_diario');
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Erro ao ler texto do diário: ' . $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], 500);
        }
    }
}
