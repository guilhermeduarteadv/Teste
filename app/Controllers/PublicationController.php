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
        $rows = Database::getInstance()->query(
            "SELECT p.*, c.numero_cnj as case_cnj, c.id as linked_case_id
             FROM publications p
             LEFT JOIN cases c ON p.case_id = c.id
             WHERE p.deleted_at IS NULL
             ORDER BY p.data_publicacao DESC, p.id DESC
             LIMIT 300"
        )->fetchAll();
        $this->render('publications/index', ['pageTitle' => 'Publicações', 'publications' => $rows]);
    }

    public function show(string $id): void
    {
        $db = Database::getInstance();
        $pub = $db->prepare(
            "SELECT p.*, c.numero_cnj as case_cnj, c.titulo as case_titulo
             FROM publications p
             LEFT JOIN cases c ON p.case_id = c.id
             WHERE p.id = ? AND p.deleted_at IS NULL"
        );
        $pub->execute([$id]);
        $publication = $pub->fetch();
        if (!$publication) {
            $this->redirect('/publications');
            return;
        }
        $cases = $db->query(
            "SELECT id, numero_cnj, titulo FROM cases WHERE deleted_at IS NULL ORDER BY titulo"
        )->fetchAll();
        $this->render('publications/show', [
            'pageTitle'   => 'Publicação #' . $id,
            'publication' => $publication,
            'cases'       => $cases,
        ]);
    }

    public function linkCase(string $id): void
    {
        $this->validateCsrf();
        $caseId = (int)$this->input('case_id', 0);
        $db = Database::getInstance();
        $db->prepare("UPDATE publications SET case_id = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL")
           ->execute([$caseId ?: null, $id]);
        $this->json(['success' => true, 'message' => 'Publicação vinculada ao processo.']);
    }

    public function createDeadline(string $id): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $pub = $db->prepare("SELECT * FROM publications WHERE id = ? AND deleted_at IS NULL");
        $pub->execute([$id]);
        $publication = $pub->fetch();
        if (!$publication) {
            $this->json(['success' => false, 'message' => 'Publicação não encontrada.']);
            return;
        }
        $titulo = $this->input('titulo', 'Prazo de publicação');
        $dataBase = $this->input('data_base', $publication['data_publicacao'] ?? date('Y-m-d'));
        $dias = max(1, (int)$this->input('dias', 15));
        $dataPrazo = date('Y-m-d', strtotime($dataBase . ' +' . $dias . ' days'));
        $st = $db->prepare(
            "INSERT INTO case_deadlines (case_id, titulo, descricao, data_prazo, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())"
        );
        $st->execute([
            $publication['case_id'],
            $titulo,
            'Prazo criado a partir de publicação #' . $id,
            $dataPrazo,
        ]);
        $this->json(['success' => true, 'message' => 'Prazo criado com sucesso.', 'data_prazo' => $dataPrazo]);
    }

    public function updateStatus(string $id): void
    {
        $this->validateCsrf();
        $status = $this->input('status', 'pending');
        $allowed = ['pending', 'reviewed', 'linked', 'ignored'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Status inválido.']);
            return;
        }
        Database::getInstance()
            ->prepare("UPDATE publications SET status = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL")
            ->execute([$status, $id]);
        $this->json(['success' => true, 'message' => 'Status atualizado.']);
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
        $st = $db->prepare(
            "INSERT IGNORE INTO publications
             (case_id, numero_cnj, tribunal, diario, data_publicacao, titulo, texto, fonte, hash, status, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,'pending',NOW(),NOW())"
        );
        $st->execute([
            $caseId,
            $numero,
            'tjsp',
            $this->input('diario', 'DJE'),
            $this->input('data_publicacao', date('Y-m-d')),
            $this->input('titulo', 'Publicação'),
            $texto,
            'manual',
            $hash,
        ]);
        if ($caseId) {
            $mv = $db->prepare(
                "INSERT INTO case_movements (case_id, data_movimento, tipo, descricao, fonte, hash, created_at)
                 VALUES (?, ?, 'publicacao', ?, 'import', ?, NOW())"
            );
            $mv->execute([$caseId, $this->input('data_publicacao', date('Y-m-d')) . ' 00:00:00', $texto, $hash]);
        }
        $this->json(['success' => true, 'message' => 'Publicação importada e vinculada quando houve processo correspondente.']);
    }

    public function readDje(): void
    {
        $this->validateCsrf();
        try {
            $oab   = (string)$this->input('oab_number', '');
            $uf    = (string)$this->input('oab_state', 'SP');
            $start = (string)$this->input('date_start', date('Y-m-d', strtotime('-7 days')));
            $end   = (string)$this->input('date_end', date('Y-m-d'));
            $extra = (string)$this->input('query_extra', '');
            $service = new DjeTJSPPublicationService();
            $result  = $service->searchAndImportByOab($oab, $uf, $start, $end, $extra);
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Erro ao ler DJE/TJSP: ' . $e->getMessage()], 500);
        }
    }

    public function readText(): void
    {
        $this->validateCsrf();
        try {
            $texto   = (string)$this->input('diary_text', '');
            $data    = (string)$this->input('data_publicacao_texto', date('Y-m-d'));
            $service = new DjeTJSPPublicationService();
            $result  = $service->importFromText($texto, $data, 'texto_diario');
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Erro ao ler texto do diário: ' . $e->getMessage()], 500);
        }
    }
}
