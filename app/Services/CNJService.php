<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Logger;
use PDO;

class CNJService
{
    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $logs = [];

    public function __construct()
    {
        $config = require ROOT_PATH . '/config/api.php';
        $this->baseUrl = $config['cnj']['base_url'];
        $this->apiKey  = $config['cnj']['api_key'];
        $this->timeout = $config['cnj']['timeout'];
    }

    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $url = $this->baseUrl . '/api_publica_tjsp/_search';
            $response = $this->makeRequest('GET', $url, []);
            $duration = (int)((microtime(true) - $start) * 1000);
            if ($response !== false) {
                $this->saveLog('cnj', $url, 'GET', [], 200, 'Conexão realizada com sucesso', 'success', $duration);
                return ['success' => true, 'message' => 'Conexão com API CNJ realizada com sucesso.', 'duration_ms' => $duration];
            }
            $this->saveLog('cnj', $url, 'GET', [], 0, 'Falha na conexão', 'error', $duration);
            return ['success' => false, 'message' => 'Falha na conexão com a API CNJ.'];
        } catch (\Exception $e) {
            Logger::error('CNJ testConnection failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao conectar com API CNJ: ' . $e->getMessage()];
        }
    }

    public function searchProcessesByOAB(string $oabNumber, string $oabState, array $tribunais = []): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'Chave da API CNJ não configurada.', 'data' => []];
        }

        $results = [];
        $config = require ROOT_PATH . '/config/api.php';
        $tribunaisToSearch = !empty($tribunais) ? $tribunais : $config['cnj']['tribunais'];
        $totalNew = 0;
        $totalUpdated = 0;
        $errors = [];

        foreach ($tribunaisToSearch as $tribunal) {
            try {
                $url = str_replace('{tribunal}', strtolower($tribunal), $this->baseUrl . '/api_publica_{tribunal}/_search');
                $payload = [
                    'query' => [
                        'bool' => [
                            'must' => [
                                ['match' => ['numeroOAB' => $oabNumber]],
                                ['match' => ['estadoOAB' => $oabState]],
                            ],
                        ],
                    ],
                    'size' => 100,
                    'sort' => [['dataHoraUltimaAtualizacao' => ['order' => 'desc']]],
                ];

                $start = microtime(true);
                $response = $this->makeRequest('POST', $url, $payload);
                $duration = (int)((microtime(true) - $start) * 1000);

                if ($response === false) {
                    $errors[] = "Falha ao consultar {$tribunal}";
                    $this->saveLog('cnj', $url, 'POST', $payload, 0, "Falha ao consultar {$tribunal}", 'error', $duration);
                    continue;
                }

                $data = json_decode($response, true);
                if (!isset($data['hits']['hits'])) continue;

                $hits = $data['hits']['hits'];
                $this->saveLog('cnj', $url, 'POST', $payload, 200, "Encontrados " . count($hits) . " processos em {$tribunal}", 'success', $duration);

                foreach ($hits as $hit) {
                    $process = $hit['_source'] ?? [];
                    $result = $this->processCase($process, $tribunal, $hit['_id'] ?? '');
                    if ($result === 'new') $totalNew++;
                    elseif ($result === 'updated') $totalUpdated++;
                }

            } catch (\Exception $e) {
                $errors[] = "Erro em {$tribunal}: " . $e->getMessage();
                Logger::error("CNJ search error for {$tribunal}: " . $e->getMessage());
            }
        }

        $this->updateLastSync();
        $message = "{$totalNew} novos processos encontrados, {$totalUpdated} atualizados.";
        if ($totalNew === 0 && $totalUpdated === 0) {
            $message = 'Nenhuma movimentação nova encontrada.';
        }

        return [
            'success'  => true,
            'message'  => $message,
            'new'      => $totalNew,
            'updated'  => $totalUpdated,
            'errors'   => $errors,
        ];
    }

    public function updateProcess(int $caseId, string $numeroCnj, string $tribunal): array
    {
        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'Chave da API CNJ não configurada.'];
        }

        try {
            $url = str_replace('{tribunal}', strtolower($tribunal), $this->baseUrl . '/api_publica_{tribunal}/_search');
            $payload = [
                'query' => ['match' => ['numeroProcessoUnicoTribunal' => $numeroCnj]],
                'size'  => 1,
            ];

            $start    = microtime(true);
            $response = $this->makeRequest('POST', $url, $payload);
            $duration = (int)((microtime(true) - $start) * 1000);

            if ($response === false) {
                $this->saveLog('cnj', $url, 'POST', $payload, 0, 'Falha na requisição', 'error', $duration);
                return ['success' => false, 'message' => 'Falha na conexão com a API CNJ.'];
            }

            $data = json_decode($response, true);
            if (!isset($data['hits']['hits'][0])) {
                return ['success' => false, 'message' => 'Processo não encontrado na API CNJ.'];
            }

            $process = $data['hits']['hits'][0]['_source'] ?? [];
            $this->updateCaseFromCNJ($caseId, $process);
            $this->saveLog('cnj', $url, 'POST', $payload, 200, "Processo {$numeroCnj} atualizado com sucesso", 'success', $duration);

            return ['success' => true, 'message' => "Processo {$numeroCnj} atualizado com sucesso.", 'data' => $process];

        } catch (\Exception $e) {
            Logger::error('CNJ updateProcess failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao atualizar processo: ' . $e->getMessage()];
        }
    }

    private function processCase(array $process, string $tribunal, string $cnj_id): string
    {
        try {
            $db = Database::getInstance();
            $numeroCnj = $process['numeroProcessoUnicoTribunal'] ?? $process['numero'] ?? '';
            if (empty($numeroCnj)) return 'skip';

            $stmt = $db->prepare("SELECT id, last_movement_hash FROM cases WHERE numero_cnj = ? LIMIT 1");
            $stmt->execute([$numeroCnj]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            $hash = md5(json_encode($process));

            if (!$existing) {
                $stmt = $db->prepare("INSERT INTO cases (numero_cnj, tribunal, classe, assunto, status, cnj_raw_data, last_movement_hash, last_sync_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'ativo', ?, ?, NOW(), NOW(), NOW())");
                $stmt->execute([
                    $numeroCnj,
                    $tribunal,
                    $process['classe']['nome'] ?? '',
                    $process['assuntos'][0]['nome'] ?? '',
                    json_encode($process),
                    $hash,
                ]);
                $caseId = (int)$db->lastInsertId();
                $this->syncMovements($caseId, $process['movimentos'] ?? []);
                return 'new';
            }

            if ($existing['last_movement_hash'] !== $hash) {
                $stmt = $db->prepare("UPDATE cases SET cnj_raw_data = ?, last_movement_hash = ?, last_sync_at = NOW(), updated_at = NOW() WHERE id = ?");
                $stmt->execute([json_encode($process), $hash, $existing['id']]);
                $this->syncMovements((int)$existing['id'], $process['movimentos'] ?? []);
                return 'updated';
            }

            return 'unchanged';
        } catch (\Exception $e) {
            Logger::error('Error processing CNJ case: ' . $e->getMessage());
            return 'error';
        }
    }

    private function updateCaseFromCNJ(int $caseId, array $process): void
    {
        $db = Database::getInstance();
        $hash = md5(json_encode($process));
        $stmt = $db->prepare("UPDATE cases SET cnj_raw_data = ?, last_movement_hash = ?, last_sync_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([json_encode($process), $hash, $caseId]);
        $this->syncMovements($caseId, $process['movimentos'] ?? []);
    }

    private function syncMovements(int $caseId, array $movimentos): void
    {
        if (empty($movimentos)) return;
        $db = Database::getInstance();

        foreach ($movimentos as $mov) {
            $descricao = $mov['complemento'] ?? $mov['nome'] ?? 'Movimentação';
            $data = $mov['dataHora'] ?? date('Y-m-d H:i:s');
            $cnjId = $mov['codigo'] ?? '';
            $hash = md5($caseId . $data . $descricao);

            $stmt = $db->prepare("SELECT id FROM case_movements WHERE case_id = ? AND hash = ? LIMIT 1");
            $stmt->execute([$caseId, $hash]);
            if ($stmt->fetch()) continue;

            $stmt = $db->prepare("INSERT INTO case_movements (case_id, data_movimento, descricao, fonte, cnj_id, hash, created_at)
                VALUES (?, ?, ?, 'cnj_api', ?, ?, NOW())");
            $stmt->execute([$caseId, $data, $descricao, $cnjId, $hash]);
        }
    }

    private function makeRequest(string $method, string $url, array $payload): string|false
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: ApiKey ' . $this->apiKey,
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $response === false) {
            Logger::error("CNJ API cURL error: {$error}", ['url' => $url]);
            return false;
        }

        if ($httpCode >= 400) {
            Logger::warning("CNJ API returned {$httpCode}", ['url' => $url, 'response' => substr($response, 0, 500)]);
            return false;
        }

        return $response;
    }

    public function saveLogs(string $service, string $endpoint, string $method, array $request, int $code, string $message, string $status, int $duration): void
    {
        $this->saveLog($service, $endpoint, $method, $request, $code, $message, $status, $duration);
    }

    private function saveLog(string $service, string $endpoint, string $method, array $request, int $code, string $message, string $status, int $duration): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO api_logs (service, endpoint, method, request_data, response_code, response_body, status, error_message, duration_ms, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $service,
                $endpoint,
                $method,
                json_encode($request),
                $code,
                $message,
                $status,
                $status === 'error' ? $message : null,
                $duration,
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to save API log: ' . $e->getMessage());
        }
    }

    private function updateLastSync(): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE settings SET valor = ? WHERE chave = 'cnj_last_sync'");
            $stmt->execute([date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            Logger::error('Failed to update last sync: ' . $e->getMessage());
        }
    }
}
