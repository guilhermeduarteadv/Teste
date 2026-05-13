<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;
use App\Services\AuditService;
use PDO;

class AuditController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        $userId    = $this->input('user_id', '');
        $module    = $this->input('module', '');
        $dateFrom  = $this->input('date_from', date('Y-m-01'));
        $dateTo    = $this->input('date_to', date('Y-m-d'));

        $where  = "at.created_at BETWEEN ? AND ?";
        $params = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];

        if ($userId !== '') {
            $where .= " AND at.user_id = ?";
            $params[] = (int)$userId;
        }
        if ($module !== '') {
            $where .= " AND at.module = ?";
            $params[] = $module;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT at.*, u.name AS user_name
                 FROM audit_trail at
                 LEFT JOIN users u ON u.id = at.user_id
                 WHERE {$where}
                 ORDER BY at.created_at DESC
                 LIMIT 500"
            );
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $logs = [];
        }

        try {
            $users = $this->db->query(
                "SELECT id, name FROM users WHERE deleted_at IS NULL ORDER BY name ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $users = [];
        }

        try {
            $modules = $this->db->query(
                "SELECT DISTINCT module FROM audit_trail WHERE module IS NOT NULL ORDER BY module ASC"
            )->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $modules = [];
        }

        $this->render('admin/audit', [
            'pageTitle'   => 'Auditoria e LGPD',
            'logs'        => $logs,
            'users'       => $users,
            'modules'     => $modules,
            'filter_user' => $userId,
            'filter_module' => $module,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'csrf_token'  => Session::csrfToken(),
        ]);
    }

    public function export(): void
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo   = $_GET['date_to'] ?? date('Y-m-d');

        try {
            $stmt = $this->db->prepare(
                "SELECT at.*, u.name AS user_name
                 FROM audit_trail at
                 LEFT JOIN users u ON u.id = at.user_id
                 WHERE at.created_at BETWEEN ? AND ?
                 ORDER BY at.created_at DESC"
            );
            $stmt->execute([$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $logs = [];
        }

        $filename = 'audit_trail_' . date('Ymd_His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode([
            'exported_at' => date('Y-m-d H:i:s'),
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total' => count($logs),
            'logs' => $logs,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function exportClientData(string $id): void
    {
        $this->validateCsrf();
        $clientId = (int)$id;
        $userId   = (int)Session::get('user_id');

        $result = AuditService::exportClientData($clientId, $userId);

        $this->json($result);
    }
}
