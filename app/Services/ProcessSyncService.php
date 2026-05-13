<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\LegalCase;
use Core\Database;
use Core\Logger;

class ProcessSyncService
{
    private $caseModel;

    public function __construct()
    {
        $this->caseModel = new LegalCase();
    }

    public function importByOAB(string $oabNumber, string $oabState, array $tribunais = [], ?int $userId = null): array
    {
        $authResult = null;

        if ($userId !== null && strtolower($oabState) === 'sp') {
            try {
                $authResult = (new AuthenticatedTJSPService())->importByOAB($userId, $oabNumber, $oabState);
                if (!empty($authResult['success'])) {
                    $this->updateLastSync();
                    return $authResult;
                }
            } catch (\Throwable $e) {
                Logger::error('Authenticated OAB import failed: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
                $authResult = ['success' => false, 'message' => 'Falha na importação autenticada: ' . $e->getMessage()];
            }
        }

        $cnj = new CNJService();
        $result = $cnj->searchProcessesByOAB($oabNumber, $oabState, $tribunais);

        $new = (int)($result['new'] ?? 0);
        $updated = (int)($result['updated'] ?? 0);
        $errors = $result['errors'] ?? [];

        $this->updateLastSync();

        $message = "Importação concluída pelo DataJud/CNJ: {$new} novos processos, {$updated} atualizados.";

        if ($new === 0 && $updated === 0) {
            if ($authResult && empty($authResult['success'])) {
                $message = ($authResult['message'] ?? 'A importação autenticada não retornou processos.') . ' Como fallback, o DataJud/CNJ também não retornou processos para essa OAB.';
            } else {
                $message .= ' Nenhum processo foi localizado automaticamente. Para listar processos por OAB, principalmente eproc/e-SAJ e segredo de justiça, cadastre uma sessão autenticada em Tribunais > Conectar Tribunal.';
            }
        }

        return [
            'success' => true,
            'message' => $message,
            'new' => $new,
            'updated' => $updated,
            'errors' => $errors,
            'authenticated' => $authResult,
            'notice' => ($new === 0 && $updated === 0) ? 'Nenhuma fonte retornou processos para a OAB informada.' : null
        ];
    }

    public function syncCase(int $caseId, string $numeroCnj, string $tribunal): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT segredo_justica FROM cases WHERE id = ? LIMIT 1");
            $stmt->execute([$caseId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && !empty($row['segredo_justica'])) {
                return [
                    'success' => false,
                    'skipped' => true,
                    'message' => 'Processo marcado como segredo de justiça. A sincronização automática foi bloqueada para este processo.'
                ];
            }
        } catch (\Throwable $e) {
            Logger::warning('Não foi possível verificar segredo de justiça antes da sincronização: ' . $e->getMessage());
        }

        $tribunal = strtolower(trim($tribunal ?: 'tjsp'));

        $cnj = new CNJService();
        $datajud = $cnj->updateProcess($caseId, $numeroCnj, $tribunal);
        if (!empty($datajud['success'])) {
            return $datajud;
        }

        if ($tribunal === 'tjsp') {
            $eproc = (new EprocTJSPService())->searchByNumber($numeroCnj, '1');

            if (!empty($eproc['success']) && !empty($eproc['data'])) {
                $this->caseModel->update($caseId, $this->filterCasePayload($eproc['data']));
                $this->addEprocMovements($caseId, $eproc['data']['movimentos'] ?? []);
                $this->addSyncMovement($caseId, 'Sincronização realizada pelo eproc/TJSP.');

                return [
                    'success' => true,
                    'message' => 'Processo atualizado pelo eproc/TJSP. O DataJud não retornou o processo, mas o eproc foi localizado.',
                    'data' => $eproc['data'],
                    'datajud' => $datajud,
                    'eproc' => $eproc
                ];
            }

            // Não marca como sincronizado, mas evita mensagem genérica de falha CNJ.
            return [
                'success' => false,
                'message' => 'O processo não foi localizado no DataJud/CNJ e o eproc/TJSP respondeu, mas não retornou dados estruturados para sincronização automática. Abra a consulta pública manual do eproc/TJSP para conferir o processo.',
                'manual_url' => $eproc['manual_url'] ?? 'https://eproc1g.tjsp.jus.br/eproc',
                'datajud' => $datajud,
                'eproc' => $eproc
            ];
        }

        return $datajud;
    }

    public function syncRegistered(): array
    {
        $cases = $this->caseModel->findAllRegisteredForSync();
        $ok = 0;
        $fail = 0;
        $messages = [];

        foreach ($cases as $case) {
            $r = $this->syncCase((int)$case['id'], (string)$case['numero_cnj'], (string)($case['tribunal'] ?: 'tjsp'));
            if (!empty($r['success'])) {
                $ok++;
            } else {
                $fail++;
                $messages[] = $case['numero_cnj'] . ': ' . ($r['message'] ?? 'falha');
            }
        }

        $this->updateLastSync();

        return [
            'success' => $fail === 0,
            'message' => "Sincronização dos cadastrados finalizada: {$ok} atualizados, {$fail} não localizados/erro.",
            'updated' => $ok,
            'failed' => $fail,
            'errors' => array_slice($messages, 0, 20)
        ];
    }

    private function filterCasePayload(array $data): array
    {
        $allowed = ['numero_cnj', 'tribunal', 'sistema', 'comarca', 'vara', 'classe', 'area', 'assunto', 'valor_causa', 'fase_processual', 'status', 'cnj_raw_data', 'fonte_importacao', 'last_sync_at', 'last_movement_hash'];
        $payload = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }
        $payload['last_sync_at'] = date('Y-m-d H:i:s');
        if (empty($payload['sistema'])) {
            $payload['sistema'] = 'eproc';
        }
        if (empty($payload['fonte_importacao'])) {
            $payload['fonte_importacao'] = 'eproc_tjsp';
        }
        return $payload;
    }

    private function addEprocMovements(int $caseId, array $movimentos): void
    {
        if (empty($movimentos)) {
            return;
        }
        try {
            $db = Database::getInstance();
            foreach ($movimentos as $mov) {
                if (!is_array($mov)) {
                    continue;
                }

                $data = $mov['data_movimento'] ?? date('Y-m-d H:i:s');
                $descricao = trim((string)($mov['descricao'] ?? 'Movimentação eproc/TJSP'));
                if ($descricao === '') {
                    continue;
                }

                $tipo = (string)($mov['tipo'] ?? 'movimentacao');
                $eventoNumero = isset($mov['evento_numero']) ? (string)$mov['evento_numero'] : null;
                $documentoUrl = $mov['documento_url'] ?? null;
                $documentoTipo = $mov['documento_tipo'] ?? null;
                $usuarioOrigem = $mov['usuario_origem'] ?? null;
                $conteudo = $mov['conteudo'] ?? null;
                $externalId = $mov['external_id'] ?? ($eventoNumero ? 'eproc:' . $eventoNumero : null);

                // Hash estável: evita duplicidade e permite acumular TODOS os eventos que a fonte retornar.
                $hash = md5($caseId . '|eproc_tjsp|' . $data . '|' . $eventoNumero . '|' . $tipo . '|' . $descricao . '|' . (string)$documentoUrl);

                $stmt = $db->prepare("SELECT id FROM case_movements WHERE case_id = ? AND hash = ? LIMIT 1");
                $stmt->execute([$caseId, $hash]);
                if ($stmt->fetch()) {
                    continue;
                }

                $stmt = $db->prepare("INSERT INTO case_movements
                    (case_id, data_movimento, tipo, descricao, fonte, cnj_id, external_id, evento_numero, documento_url, documento_tipo, usuario_origem, conteudo, hash, created_at)
                    VALUES (?, ?, ?, ?, 'eproc_tjsp', ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $caseId,
                    $data,
                    $tipo,
                    $descricao,
                    $mov['cnj_id'] ?? null,
                    $externalId,
                    $eventoNumero,
                    $documentoUrl,
                    $documentoTipo,
                    $usuarioOrigem,
                    $conteudo,
                    $hash
                ]);
            }
        } catch (\Throwable $e) {
            Logger::error('Failed to add complete eproc movements: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    private function addSyncMovement(int $caseId, string $descricao): void
    {
        try {
            $db = Database::getInstance();
            $hash = md5($caseId . $descricao . date('Y-m-d'));
            $stmt = $db->prepare("SELECT id FROM case_movements WHERE case_id = ? AND hash = ? LIMIT 1");
            $stmt->execute([$caseId, $hash]);
            if ($stmt->fetch()) {
                return;
            }
            $stmt = $db->prepare("INSERT INTO case_movements (case_id, data_movimento, tipo, descricao, fonte, hash, created_at) VALUES (?, NOW(), 'sincronizacao', ?, 'eproc_tjsp', ?, NOW())");
            $stmt->execute([$caseId, $descricao, $hash]);
        } catch (\Throwable $e) {
            Logger::error('Failed to add sync movement: ' . $e->getMessage());
        }
    }

    public function syncPartiesFromDatajud(int $caseId, string $numeroCnj): int
    {
        $db = Database::getInstance();
        $cnj = new CNJService();
        $data = $cnj->getProcessData($numeroCnj);
        if (empty($data['partes'])) {
            return 0;
        }
        $count = 0;
        foreach ($data['partes'] as $parte) {
            $nome = trim((string)($parte['nome'] ?? ''));
            if ($nome === '') {
                continue;
            }
            $tipo     = strtolower((string)($parte['tipo'] ?? 'reu'));
            $polo     = strtolower((string)($parte['polo'] ?? 'passivo'));
            $advogado = (string)($parte['advogado'] ?? '');
            $oab      = (string)($parte['advogado_oab'] ?? '');
            $existing = $db->prepare(
                "SELECT id FROM case_parties WHERE case_id = ? AND nome = ? AND deleted_at IS NULL LIMIT 1"
            );
            $existing->execute([$caseId, $nome]);
            if ($existing->fetchColumn()) {
                continue;
            }
            $db->prepare(
                "INSERT INTO case_parties (case_id, tipo, nome, polo, advogado, advogado_oab, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
            )->execute([$caseId, $tipo, $nome, $polo, $advogado, $oab]);
            $count++;
        }
        return $count;
    }

    private function updateLastSync(): void
    {
        try {
            Database::getInstance()->prepare("UPDATE settings SET valor=? WHERE chave='cnj_last_sync'")->execute([date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            Logger::error($e->getMessage());
        }
    }
}
