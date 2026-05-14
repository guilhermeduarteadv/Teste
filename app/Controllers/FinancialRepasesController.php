<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use App\Helpers\Session;

class FinancialRepasesController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        try {
            $repases = $db->query(
                "SELECT r.*, c.nome as client_nome, cs.titulo as case_titulo, cs.numero_cnj
                 FROM client_repasses r
                 LEFT JOIN clients c ON r.client_id = c.id
                 LEFT JOIN cases cs ON r.case_id = cs.id
                 WHERE r.deleted_at IS NULL
                 ORDER BY r.created_at DESC
                 LIMIT 300"
            )->fetchAll();
        } catch (\Throwable $e) {
            $repases = [];
        }
        $clients = $db->query("SELECT id, nome FROM clients WHERE deleted_at IS NULL ORDER BY nome")->fetchAll();
        $cases   = $db->query("SELECT id, assunto, numero_cnj FROM cases WHERE deleted_at IS NULL ORDER BY assunto")->fetchAll();
        $this->render('financial/repases', [
            'pageTitle' => 'Repasses ao Cliente',
            'repases'   => $repases,
            'clients'   => $clients,
            'cases'     => $cases,
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $db->prepare(
            "INSERT INTO client_repasses
             (client_id, case_id, valor_recebido, valor_repassado, percentual_honorarios, data_recebimento, data_repasse, descricao, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW(), NOW())"
        )->execute([
            (int)$this->input('client_id', 0) ?: null,
            (int)$this->input('case_id', 0) ?: null,
            (float)str_replace(',', '.', $this->input('valor_recebido', '0')),
            (float)str_replace(',', '.', $this->input('valor_repassado', '0')),
            (float)str_replace(',', '.', $this->input('percentual_honorarios', '0')),
            $this->input('data_recebimento', date('Y-m-d')),
            $this->input('data_repasse', '') ?: null,
            $this->input('descricao', ''),
        ]);
        Session::flash('success', 'Repasse cadastrado.');
        $this->redirect('/financial/repases');
    }

    public function confirm(string $id): void
    {
        $this->validateCsrf();
        Database::getInstance()->prepare(
            "UPDATE client_repasses SET status = 'repassado', data_repasse = ?, updated_at = NOW() WHERE id = ?"
        )->execute([
            $this->input('data_repasse', date('Y-m-d')),
            $id,
        ]);
        $this->json(['success' => true, 'message' => 'Repasse confirmado.']);
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        Database::getInstance()->prepare(
            "UPDATE client_repasses SET deleted_at = NOW() WHERE id = ?"
        )->execute([$id]);
        $this->json(['success' => true]);
    }
}
