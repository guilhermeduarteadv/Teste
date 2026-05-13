<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\TimelineService;
use Core\Controller;
use Core\Database;
use PDO;

class TimelineController extends Controller
{
    public function caseTimeline($caseId): void
    {
        $caseId = (int)$caseId;
        $service = new TimelineService();
        $service->rebuildCaseTimeline($caseId);
        $timeline = $service->getCaseTimeline($caseId);
        $progress = $service->calculateCaseProgress($caseId);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM cases WHERE id = ? LIMIT 1");
        $stmt->execute([$caseId]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->render('cases/timeline', [
            'pageTitle' => 'Linha do tempo processual',
            'case' => $case,
            'timeline' => $timeline,
            'progress' => $progress,
        ]);
    }

    public function rebuild($caseId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $service = new TimelineService();
            echo json_encode($service->rebuildCaseTimeline((int)$caseId), JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function markImportant($timelineId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $important = isset($_POST['important']) ? (bool)$_POST['important'] : true;
            $visible = isset($_POST['visible_client']) ? (bool)$_POST['visible_client'] : true;
            $service = new TimelineService();
            $ok = $service->markImportant((int)$timelineId, $important, $visible);
            echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function apiCaseTimeline($caseId): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $service = new TimelineService();
            $service->rebuildCaseTimeline((int)$caseId);
            echo json_encode([
                'success' => true,
                'progress' => $service->calculateCaseProgress((int)$caseId),
                'timeline' => $service->getCaseTimeline((int)$caseId)
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
