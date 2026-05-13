<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;

class CasePartiesController extends Controller
{
    public function index(string $caseId): void
    {
        $db = Database::getInstance();
        $case = $db->prepare("SELECT id, titulo, numero_cnj FROM cases WHERE id = ? AND deleted_at IS NULL");
        $case->execute([$caseId]);
        $case = $case->fetch();
        if (!$case) {
            $this->redirect('/cases');
            return;
        }
        $parties = $db->prepare(
            "SELECT * FROM case_parties WHERE case_id = ? AND deleted_at IS NULL ORDER BY tipo, nome"
        );
        $parties->execute([$caseId]);
        $this->render('cases/parties', [
            'pageTitle' => 'Partes do Processo — ' . $case['titulo'],
            'case'      => $case,
            'parties'   => $parties->fetchAll(),
        ]);
    }

    public function store(string $caseId): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $st = $db->prepare(
            "INSERT INTO case_parties (case_id, tipo, nome, cpf_cnpj, advogado, advogado_oab, polo, observacoes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $st->execute([
            $caseId,
            $this->input('tipo', 'reu'),
            $this->input('nome', ''),
            $this->input('cpf_cnpj', ''),
            $this->input('advogado', ''),
            $this->input('advogado_oab', ''),
            $this->input('polo', 'passivo'),
            $this->input('observacoes', ''),
        ]);
        $this->redirect('/cases/' . $caseId . '/parties');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $row = $db->prepare("SELECT case_id FROM case_parties WHERE id = ? AND deleted_at IS NULL");
        $row->execute([$id]);
        $party = $row->fetch();
        $db->prepare("UPDATE case_parties SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
        $this->json(['success' => true]);
    }

    public function syncFromDatajud(string $caseId): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $case = $db->prepare("SELECT numero_cnj FROM cases WHERE id = ? AND deleted_at IS NULL");
        $case->execute([$caseId]);
        $case = $case->fetch();
        if (!$case || !$case['numero_cnj']) {
            $this->json(['success' => false, 'message' => 'Processo sem número CNJ para busca no DataJud.']);
            return;
        }
        try {
            $service = new \App\Services\ProcessSyncService();
            $result  = $service->syncPartiesFromDatajud((int)$caseId, $case['numero_cnj']);
            $this->json(['success' => true, 'message' => 'Partes sincronizadas: ' . $result . ' registros.', 'count' => $result]);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => 'Erro ao sincronizar: ' . $e->getMessage()]);
        }
    }
}
