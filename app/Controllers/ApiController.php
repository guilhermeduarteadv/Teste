<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Database;
use App\Models\UserModel;
use App\Models\CaseModel;
use App\Services\CNJService;
use App\Services\ProcessSyncService;
use App\Helpers\DateHelper;
use App\Services\SystemLogService;

class ApiController extends Controller
{
    private $jsonEndpointActive = false;
    private $jsonEndpointResponded = false;

    /**
     * Versão local do json() para endpoints AJAX: garante resposta JSON mesmo
     * quando houver buffers abertos, warning/notice ou erro fatal.
     */
    protected function json($data, int $code = 200): void
    {
        $this->jsonEndpointResponded = true;
        $this->jsonEndpointActive = false;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            $json = json_encode([
                'success' => false,
                'message' => 'Falha ao gerar JSON: ' . json_last_error_msg()
            ]);
        }

        echo $json;
        exit;
    }

    public function syncCnj(): void
    {
        $this->beginJsonEndpoint();

        try {
            if (!$this->validCsrfForJson()) {
                $this->json(['success' => false, 'message' => 'Requisição inválida: token CSRF inválido. Recarregue a página e tente novamente.'], 403);
                return;
            }

            $user = Session::get('user');
            if (!$user) {
                $this->json(['success' => false, 'message' => 'Não autorizado.'], 401);
                return;
            }

            $oabNumber = trim($_POST['oab_number'] ?? $_POST['oab'] ?? '');
            $oabState  = trim($_POST['oab_state'] ?? $_POST['uf'] ?? '');
            $tribunais = $_POST['tribunais'] ?? [];

            if ($oabNumber === '' || $oabState === '') {
                $resolved = $this->resolveOabFromUserAndSettings($user);
                if ($oabNumber === '') {
                    $oabNumber = $resolved['oab_number'];
                }
                if ($oabState === '') {
                    $oabState = $resolved['oab_state'];
                }
            }

            $oabNumber = preg_replace('/\D/', '', (string)$oabNumber);
            $oabState  = strtoupper(trim((string)$oabState));

            if (empty($oabNumber) || empty($oabState)) {
                $this->json([
                    'success' => false,
                    'message' => 'Número OAB e estado são obrigatórios. Informe no botão Importar por OAB ou cadastre em Administração > Configurações / Usuários.',
                    'needs_oab' => true
                ], 422);
                return;
            }

            $syncService = new ProcessSyncService();
            $result = $syncService->importByOAB($oabNumber, $oabState, is_array($tribunais) ? $tribunais : [], (int)($user['id'] ?? 0));
            SystemLogService::log('cnj_sync', 'api', "Sincronização CNJ: OAB {$oabNumber}/{$oabState}");

            $this->json($result);
        } catch (\Throwable $e) {
            \Core\Logger::error('Importação por OAB falhou: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Erro ao importar por OAB: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    private function resolveOabFromUserAndSettings(array $sessionUser): array
    {
        $oabNumber = trim((string)($sessionUser['oab_number'] ?? ''));
        $oabState = trim((string)($sessionUser['oab_state'] ?? ''));

        try {
            $db = Database::getInstance();

            if (($oabNumber === '' || $oabState === '') && !empty($sessionUser['id'])) {
                $stmt = $db->prepare("SELECT oab_number, oab_state FROM users WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$sessionUser['id']]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    if ($oabNumber === '') {
                        $oabNumber = trim((string)($row['oab_number'] ?? ''));
                    }
                    if ($oabState === '') {
                        $oabState = trim((string)($row['oab_state'] ?? ''));
                    }
                }
            }

            if ($oabNumber === '' || $oabState === '') {
                $stmt = $db->query("SELECT chave, valor FROM settings WHERE chave IN ('office_oab', 'office_oab_state')");
                $settings = [];
                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $settings[$row['chave']] = $row['valor'];
                }
                if ($oabNumber === '') {
                    $oabNumber = trim((string)($settings['office_oab'] ?? ''));
                }
                if ($oabState === '') {
                    $oabState = trim((string)($settings['office_oab_state'] ?? ''));
                }
            }
        } catch (\Exception $e) {
            // Mantém os dados já encontrados e deixa a validação principal tratar campos vazios.
        }

        return [
            'oab_number' => $oabNumber,
            'oab_state' => $oabState,
        ];
    }


    private function beginJsonEndpoint(): void
    {
        $this->jsonEndpointActive = true;
        $this->jsonEndpointResponded = false;

        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ob_start();

        // Não transforma Notice/Warning em exception aqui, porque alguns servidores
        // antigos/AppServ emitem avisos em bibliotecas externas. O erro é logado e
        // o endpoint continua podendo devolver JSON válido.
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            \Core\Logger::warning('Aviso PHP em endpoint JSON: ' . $message, [
                'file' => $file,
                'line' => $line,
                'severity' => $severity
            ]);
            return true;
        });

        register_shutdown_function(function () {
            if (!$this->jsonEndpointActive || $this->jsonEndpointResponded) {
                return;
            }

            $error = error_get_last();
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

            if ($error && in_array($error['type'], $fatalTypes, true)) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');

                echo json_encode([
                    'success' => false,
                    'message' => 'Erro fatal no servidor: ' . ($error['message'] ?? 'erro desconhecido'),
                    'file' => $error['file'] ?? null,
                    'line' => $error['line'] ?? null
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
                return;
            }

            $buffer = '';
            if (ob_get_level() > 0) {
                $buffer = trim((string)ob_get_contents());
                ob_end_clean();
            }

            if ($buffer !== '') {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Servidor retornou saída inesperada antes do JSON.',
                    'raw' => mb_substr($buffer, 0, 2000)
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
            }
        });
    }

    private function validCsrfForJson(): bool
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return \Core\Session::verifyCsrf($token);
    }

    public function status(): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Não autorizado.'], 401);
            return;
        }

        $apiConfig = require ROOT_PATH . '/config/api.php';
        $hasApiKey = !empty($apiConfig['cnj']['api_key']);

        $lastSync = null;
        try {
            $db = \Core\Database::getInstance();
            $stmt = $db->query("SELECT valor FROM settings WHERE chave = 'cnj_last_sync' LIMIT 1");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $lastSync = $row ? $row['valor'] : null;
        } catch (\Exception $e) {}

        $this->json([
            'success'   => true,
            'configured'=> $hasApiKey,
            'last_sync' => $lastSync,
            'tribunais' => $apiConfig['cnj']['tribunais'],
        ]);
    }

    public function calculateDeadline(): void
    {
        $this->validateCsrf();

        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $days      = (int)($_POST['days'] ?? 0);
        $tipo      = $_POST['tipo'] ?? 'processual';
        $state     = $_POST['state'] ?? '';

        if ($days <= 0) {
            $this->json(['success' => false, 'message' => 'Número de dias inválido.'], 422);
            return;
        }

        try {
            $result = DateHelper::calculateDeadline($startDate, $days, $tipo, $state);
            $daysUntil = DateHelper::daysUntil($result);
            $human     = DateHelper::humanDiff($result);

            $this->json([
                'success'          => true,
                'deadline'         => $result,
                'deadline_br'      => DateHelper::formatBr($result),
                'days_until'       => $daysUntil,
                'human_diff'       => $human,
                'is_overdue'       => $daysUntil < 0,
                'is_today'         => DateHelper::isToday($result),
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro ao calcular prazo: ' . $e->getMessage()], 500);
        }
    }

    public function syncRegisteredProcesses(): void
    {
        $this->beginJsonEndpoint();

        try {
            if (!$this->validCsrfForJson()) {
                $this->json(['success' => false, 'message' => 'Requisição inválida: token CSRF inválido. Recarregue a página e tente novamente.'], 403);
                return;
            }
            $result = (new ProcessSyncService())->syncRegistered();
            $this->json($result);
        } catch (\Throwable $e) {
            \Core\Logger::error('Sincronização de cadastrados falhou: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Erro ao sincronizar processos cadastrados: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
