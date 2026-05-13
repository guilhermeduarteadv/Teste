<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Services\TribunalConnectionService;
use App\Services\AuthenticatedTJSPService;

class TribunalConnectionController extends Controller
{
    public function index(): void
    {
        $user = Session::get('user');
        $service = new TribunalConnectionService();
        $this->render('tribunals/index', [
            'pageTitle' => 'Conectar Tribunais',
            'connections' => $service->all((int)$user['id']),
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();
        $user = Session::get('user');
        $service = new TribunalConnectionService();
        $service->save((int)$user['id'], [
            'tribunal' => $_POST['tribunal'] ?? 'tjsp',
            'sistema' => $_POST['sistema'] ?? 'eproc',
            'username' => $_POST['username'] ?? '',
            'password' => $_POST['password'] ?? '',
            'cookies' => $_POST['cookies'] ?? '',
            'oab_number' => $_POST['oab_number'] ?? '',
            'oab_state' => $_POST['oab_state'] ?? '',
        ]);
        Session::flash('success', 'Conexão do tribunal salva. Para segredo de justiça, mantenha cookies/sessão atualizados.');
        $this->redirect('/tribunals');
    }

    public function test(string $id): void
    {
        $this->validateCsrf();
        $user = Session::get('user');
        $service = new TribunalConnectionService();
        $connections = $service->all((int)$user['id']);
        $connection = null;
        foreach ($connections as $c) { if ((int)$c['id'] === (int)$id) { $connection = $service->find((int)$user['id'], $c['tribunal'], $c['sistema']); break; } }
        if (!$connection) $this->json(['success'=>false,'message'=>'Conexão não encontrada.'], 404);
        $result = (new AuthenticatedTJSPService())->testConnection($connection);
        $service->markTestResult((int)$id, !empty($result['success']), $result['message'] ?? null);
        $this->json($result);
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $user = Session::get('user');
        (new TribunalConnectionService())->delete((int)$user['id'], (int)$id);
        Session::flash('success', 'Conexão removida.');
        $this->redirect('/tribunals');
    }
}
