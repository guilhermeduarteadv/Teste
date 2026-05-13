<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use Core\Database;
use App\Services\SystemLogService;
use PDO;

class KnowledgeController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // -------------------------------------------------------------------------
    // JURISPRUDÊNCIA
    // -------------------------------------------------------------------------

    public function jurisprudence(): void
    {
        $area   = trim($_GET['area'] ?? '');
        $tag    = trim($_GET['tag'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $where  = ['j.deleted_at IS NULL'];
        $params = [];

        if ($area !== '') {
            $where[]  = 'j.area = ?';
            $params[] = $area;
        }

        if ($tag !== '') {
            $where[]  = 'j.tags LIKE ?';
            $params[] = '%' . $tag . '%';
        }

        if ($search !== '') {
            $where[]  = '(j.title LIKE ? OR j.court LIKE ? OR j.theme LIKE ? OR j.summary LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT j.*, u.name AS created_by_name
             FROM jurisprudence_library j
             LEFT JOIN users u ON u.id = j.created_by
             WHERE {$whereStr}
             ORDER BY j.decision_date DESC, j.created_at DESC
             LIMIT 300"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $areas = $this->getJurisprudenceAreas();

        $this->render('knowledge/jurisprudence', [
            'pageTitle' => 'Jurisprudência',
            'items'     => $items,
            'areas'     => $areas,
            'area'      => $area,
            'tag'       => $tag,
            'search'    => $search,
        ]);
    }

    public function storeJurisprudence(): void
    {
        $this->validateCsrf();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Título é obrigatório.');
            $this->redirect('/knowledge/jurisprudence');
        }

        $decisionDate = !empty($_POST['decision_date']) ? $_POST['decision_date'] : null;
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO jurisprudence_library
                (title, court, area, theme, summary, ementa, link,
                 decision_date, tags, used_in_case_id,
                 created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $title,
            trim($_POST['court'] ?? ''),
            trim($_POST['area'] ?? ''),
            trim($_POST['theme'] ?? ''),
            trim($_POST['summary'] ?? ''),
            trim($_POST['ementa'] ?? ''),
            trim($_POST['link'] ?? ''),
            $decisionDate,
            trim($_POST['tags'] ?? ''),
            !empty($_POST['used_in_case_id']) ? (int)$_POST['used_in_case_id'] : null,
            Session::get('user_id'),
            $now,
            $now,
        ]);

        $newId = (int)$this->db->lastInsertId();
        SystemLogService::create('knowledge', 'jurisprudence', $newId, "Jurisprudência cadastrada: {$title}");
        Logger::audit("Jurisprudência cadastrada ID #{$newId}");

        Session::flash('success', 'Jurisprudência cadastrada com sucesso!');
        $this->redirect('/knowledge/jurisprudence');
    }

    public function editJurisprudence(string $id): void
    {
        $item = $this->findJurisprudence((int)$id);
        if (!$item) {
            Session::flash('error', 'Registro não encontrado.');
            $this->redirect('/knowledge/jurisprudence');
        }

        $areas = $this->getJurisprudenceAreas();

        $this->render('knowledge/jurisprudence_edit', [
            'pageTitle' => 'Editar Jurisprudência',
            'item'      => $item,
            'areas'     => $areas,
        ]);
    }

    public function updateJurisprudence(string $id): void
    {
        $this->validateCsrf();

        $item = $this->findJurisprudence((int)$id);
        if (!$item) {
            Session::flash('error', 'Registro não encontrado.');
            $this->redirect('/knowledge/jurisprudence');
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Título é obrigatório.');
            $this->redirect("/knowledge/jurisprudence/{$id}/edit");
        }

        $decisionDate = !empty($_POST['decision_date']) ? $_POST['decision_date'] : null;
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "UPDATE jurisprudence_library SET
                title = ?, court = ?, area = ?, theme = ?,
                summary = ?, ementa = ?, link = ?,
                decision_date = ?, tags = ?, used_in_case_id = ?,
                updated_at = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $title,
            trim($_POST['court'] ?? ''),
            trim($_POST['area'] ?? ''),
            trim($_POST['theme'] ?? ''),
            trim($_POST['summary'] ?? ''),
            trim($_POST['ementa'] ?? ''),
            trim($_POST['link'] ?? ''),
            $decisionDate,
            trim($_POST['tags'] ?? ''),
            !empty($_POST['used_in_case_id']) ? (int)$_POST['used_in_case_id'] : null,
            $now,
            (int)$id,
        ]);

        SystemLogService::update('knowledge', 'jurisprudence', (int)$id, $item, $_POST);
        Logger::audit("Jurisprudência atualizada ID #{$id}");

        Session::flash('success', 'Jurisprudência atualizada com sucesso!');
        $this->redirect('/knowledge/jurisprudence');
    }

    public function deleteJurisprudence(string $id): void
    {
        $this->validateCsrf();

        $item = $this->findJurisprudence((int)$id);
        if (!$item) {
            Session::flash('error', 'Registro não encontrado.');
            $this->redirect('/knowledge/jurisprudence');
        }

        $this->db->prepare("UPDATE jurisprudence_library SET deleted_at = NOW() WHERE id = ?")
                 ->execute([(int)$id]);

        SystemLogService::delete('knowledge', 'jurisprudence', (int)$id);
        Logger::audit("Jurisprudência excluída ID #{$id}");

        Session::flash('success', 'Jurisprudência removida com sucesso.');
        $this->redirect('/knowledge/jurisprudence');
    }

    // -------------------------------------------------------------------------
    // TESES JURÍDICAS
    // -------------------------------------------------------------------------

    public function theses(): void
    {
        $area   = trim($_GET['area'] ?? '');
        $tag    = trim($_GET['tag'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $where  = ['t.deleted_at IS NULL'];
        $params = [];

        if ($area !== '') {
            $where[]  = 't.area = ?';
            $params[] = $area;
        }

        if ($tag !== '') {
            $where[]  = 't.tags LIKE ?';
            $params[] = '%' . $tag . '%';
        }

        if ($search !== '') {
            $where[]  = '(t.title LIKE ? OR t.thesis_text LIKE ? OR t.legal_basis LIKE ?)';
            $like     = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare(
            "SELECT t.*, u.name AS created_by_name
             FROM legal_theses t
             LEFT JOIN users u ON u.id = t.created_by
             WHERE {$whereStr}
             ORDER BY t.created_at DESC
             LIMIT 300"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $areas = $this->getThesisAreas();

        $this->render('knowledge/theses', [
            'pageTitle' => 'Banco de Teses',
            'items'     => $items,
            'areas'     => $areas,
            'area'      => $area,
            'tag'       => $tag,
            'search'    => $search,
        ]);
    }

    public function storeThesis(): void
    {
        $this->validateCsrf();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Título é obrigatório.');
            $this->redirect('/knowledge/theses');
        }

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "INSERT INTO legal_theses
                (title, area, thesis_text, legal_basis, tags,
                 created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $title,
            trim($_POST['area'] ?? ''),
            trim($_POST['thesis_text'] ?? ''),
            trim($_POST['legal_basis'] ?? ''),
            trim($_POST['tags'] ?? ''),
            Session::get('user_id'),
            $now,
            $now,
        ]);

        $newId = (int)$this->db->lastInsertId();
        SystemLogService::create('knowledge', 'legal_thesis', $newId, "Tese cadastrada: {$title}");
        Logger::audit("Tese cadastrada ID #{$newId}");

        Session::flash('success', 'Tese cadastrada com sucesso!');
        $this->redirect('/knowledge/theses');
    }

    public function editThesis(string $id): void
    {
        $item = $this->findThesis((int)$id);
        if (!$item) {
            Session::flash('error', 'Tese não encontrada.');
            $this->redirect('/knowledge/theses');
        }

        $areas = $this->getThesisAreas();

        $this->render('knowledge/thesis_edit', [
            'pageTitle' => 'Editar Tese',
            'item'      => $item,
            'areas'     => $areas,
        ]);
    }

    public function updateThesis(string $id): void
    {
        $this->validateCsrf();

        $item = $this->findThesis((int)$id);
        if (!$item) {
            Session::flash('error', 'Tese não encontrada.');
            $this->redirect('/knowledge/theses');
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            Session::flash('error', 'Título é obrigatório.');
            $this->redirect("/knowledge/theses/{$id}/edit");
        }

        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            "UPDATE legal_theses SET
                title = ?, area = ?, thesis_text = ?,
                legal_basis = ?, tags = ?, updated_at = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $title,
            trim($_POST['area'] ?? ''),
            trim($_POST['thesis_text'] ?? ''),
            trim($_POST['legal_basis'] ?? ''),
            trim($_POST['tags'] ?? ''),
            $now,
            (int)$id,
        ]);

        SystemLogService::update('knowledge', 'legal_thesis', (int)$id, $item, $_POST);
        Logger::audit("Tese atualizada ID #{$id}");

        Session::flash('success', 'Tese atualizada com sucesso!');
        $this->redirect('/knowledge/theses');
    }

    public function deleteThesis(string $id): void
    {
        $this->validateCsrf();

        $item = $this->findThesis((int)$id);
        if (!$item) {
            Session::flash('error', 'Tese não encontrada.');
            $this->redirect('/knowledge/theses');
        }

        $this->db->prepare("UPDATE legal_theses SET deleted_at = NOW() WHERE id = ?")
                 ->execute([(int)$id]);

        SystemLogService::delete('knowledge', 'legal_thesis', (int)$id);
        Logger::audit("Tese excluída ID #{$id}");

        Session::flash('success', 'Tese removida com sucesso.');
        $this->redirect('/knowledge/theses');
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function findJurisprudence(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM jurisprudence_library WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function findThesis(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM legal_theses WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function getJurisprudenceAreas(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT DISTINCT area FROM jurisprudence_library WHERE area IS NOT NULL AND area != '' AND deleted_at IS NULL ORDER BY area ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getThesisAreas(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT DISTINCT area FROM legal_theses WHERE area IS NOT NULL AND area != '' AND deleted_at IS NULL ORDER BY area ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
