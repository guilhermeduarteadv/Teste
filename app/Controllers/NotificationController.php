<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    public function index(): void
    {
        $user = Session::get('user');
        if (!$user) { $this->redirect('/login'); }

        $notifications = $this->service->getAll((int)$user['id'], 100);

        $this->render('notifications/index', [
            'pageTitle'     => 'Notificações',
            'currentUser'   => $user,
            'notifications' => $notifications,
        ]);
    }

    public function unread(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['count' => 0, 'items' => []]);
        }

        $items = $this->service->getUnread((int)$user['id']);
        $this->json(['count' => count($items), 'items' => $items]);
    }

    public function markRead(int $id): void
    {
        $user = Session::get('user');
        if (!$user) { $this->json(['success' => false], 401); }

        $ok = $this->service->markRead($id, (int)$user['id']);
        $this->json(['success' => $ok]);
    }

    public function readAll(): void
    {
        $user = Session::get('user');
        if (!$user) { $this->json(['success' => false], 401); }

        $this->validateCsrf();
        $ok = $this->service->markAllRead((int)$user['id']);
        $this->json(['success' => $ok]);
    }
}
