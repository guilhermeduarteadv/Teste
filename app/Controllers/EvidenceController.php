<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\Evidence;
use App\Models\LegalCase;
use App\Services\SystemLogService;

class EvidenceController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new Evidence();
    }

    public function index(string $caseId): void
    {
        $case = (new LegalCase())->findById((int)$caseId);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $evidences = $this->model->findByCase((int)$caseId);

        // Load documents for select
        $db = \Core\Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, original_name, titulo FROM documents
             WHERE case_id = ? AND deleted_at IS NULL ORDER BY original_name ASC"
        );
        $stmt->execute([(int)$caseId]);
        $documents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('cases/evidence', [
            'pageTitle' => 'Provas — ' . ($case['numero_cnj'] ?: $case['assunto']),
            'case'      => $case,
            'evidences' => $evidences,
            'documents' => $documents,
        ]);
    }

    public function store(string $caseId): void
    {
        $this->validateCsrf();

        $case = (new LegalCase())->findById((int)$caseId);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Título é obrigatório.');
            $this->redirect("/cases/{$caseId}/evidence");
        }

        $data = [
            'case_id'           => (int)$caseId,
            'document_id'       => !empty($_POST['document_id']) ? (int)$_POST['document_id'] : null,
            'evidence_type'     => $this->input('evidence_type', 'documento'),
            'title'             => $title,
            'description'       => $this->input('description', ''),
            'probative_strength'=> $this->input('probative_strength', 'media'),
            'legal_note'        => $this->input('legal_note', ''),
            'visible_client'    => isset($_POST['visible_client']) ? 1 : 0,
            'created_by'        => Session::get('user_id'),
        ];

        $id = $this->model->insert($data);
        SystemLogService::create('evidence', 'evidence', $id, "Prova adicionada ao processo #{$caseId}: {$title}");
        Logger::audit("Prova cadastrada ID #{$id} no processo #{$caseId}");

        Session::flash('success', 'Prova cadastrada com sucesso!');
        $this->redirect("/cases/{$caseId}/evidence");
    }

    public function edit(string $id): void
    {
        $evidence = $this->model->findById((int)$id);
        if (!$evidence) {
            Session::flash('error', 'Prova não encontrada.');
            $this->redirect('/cases');
        }

        $case = (new LegalCase())->findById((int)$evidence['case_id']);

        $db = \Core\Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, original_name, titulo FROM documents
             WHERE case_id = ? AND deleted_at IS NULL ORDER BY original_name ASC"
        );
        $stmt->execute([(int)$evidence['case_id']]);
        $documents = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('evidence/edit', [
            'pageTitle' => 'Editar Prova',
            'evidence'  => $evidence,
            'case'      => $case,
            'documents' => $documents,
        ]);
    }

    public function update(string $id): void
    {
        $this->validateCsrf();

        $evidence = $this->model->findById((int)$id);
        if (!$evidence) {
            Session::flash('error', 'Prova não encontrada.');
            $this->redirect('/cases');
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Título é obrigatório.');
            $this->redirect("/evidence/{$id}/edit");
        }

        $data = [
            'document_id'       => !empty($_POST['document_id']) ? (int)$_POST['document_id'] : null,
            'evidence_type'     => $this->input('evidence_type', 'documento'),
            'title'             => $title,
            'description'       => $this->input('description', ''),
            'probative_strength'=> $this->input('probative_strength', 'media'),
            'legal_note'        => $this->input('legal_note', ''),
            'visible_client'    => isset($_POST['visible_client']) ? 1 : 0,
        ];

        $this->model->update((int)$id, $data);
        SystemLogService::update('evidence', 'evidence', (int)$id, $evidence, $data);
        Logger::audit("Prova atualizada ID #{$id}");

        Session::flash('success', 'Prova atualizada com sucesso!');
        $this->redirect("/cases/{$evidence['case_id']}/evidence");
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();

        $evidence = $this->model->findById((int)$id);
        if (!$evidence) {
            Session::flash('error', 'Prova não encontrada.');
            $this->redirect('/cases');
        }

        $caseId = $evidence['case_id'];
        $this->model->softDelete((int)$id);
        SystemLogService::delete('evidence', 'evidence', (int)$id);
        Logger::audit("Prova excluída ID #{$id}");

        Session::flash('success', 'Prova removida com sucesso.');
        $this->redirect("/cases/{$caseId}/evidence");
    }
}
