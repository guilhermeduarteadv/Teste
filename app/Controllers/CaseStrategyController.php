<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use Core\Database;
use App\Models\LegalCase;
use App\Services\SystemLogService;

class CaseStrategyController extends Controller
{
    public function show(string $caseId): void
    {
        $case = (new LegalCase())->findById((int)$caseId);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM process_strategy_notes WHERE case_id = ? ORDER BY updated_at DESC LIMIT 1"
        );
        $stmt->execute([(int)$caseId]);
        $strategy = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        $this->render('cases/strategy', [
            'pageTitle' => 'Estratégia — ' . ($case['numero_cnj'] ?: $case['assunto']),
            'case'      => $case,
            'strategy'  => $strategy,
        ]);
    }

    public function save(string $caseId): void
    {
        $this->validateCsrf();

        $case = (new LegalCase())->findById((int)$caseId);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $db = Database::getInstance();

        // Check existing record
        $stmt = $db->prepare("SELECT id FROM process_strategy_notes WHERE case_id = ? LIMIT 1");
        $stmt->execute([(int)$caseId]);
        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

        $valorProvavel = !empty($_POST['valor_provavel'])
            ? (float)str_replace(['.', ','], ['', '.'], $_POST['valor_provavel'])
            : null;
        $valorMinimo = !empty($_POST['valor_minimo_acordo'])
            ? (float)str_replace(['.', ','], ['', '.'], $_POST['valor_minimo_acordo'])
            : null;
        $chanceAcordo = isset($_POST['chance_acordo']) && $_POST['chance_acordo'] !== ''
            ? (int)$_POST['chance_acordo']
            : null;

        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $stmt = $db->prepare(
                "UPDATE process_strategy_notes SET
                    tese_principal = ?, tese_subsidiaria = ?, riscos = ?,
                    provas_favoraveis = ?, provas_desfavoraveis = ?,
                    proximos_passos = ?, valor_provavel = ?,
                    chance_acordo = ?, valor_minimo_acordo = ?,
                    observacoes = ?, internal_only = ?, updated_at = ?
                 WHERE case_id = ?"
            );
            $stmt->execute([
                $_POST['tese_principal'] ?? '',
                $_POST['tese_subsidiaria'] ?? '',
                $_POST['riscos'] ?? '',
                $_POST['provas_favoraveis'] ?? '',
                $_POST['provas_desfavoraveis'] ?? '',
                $_POST['proximos_passos'] ?? '',
                $valorProvavel,
                $chanceAcordo,
                $valorMinimo,
                $_POST['observacoes'] ?? '',
                1,
                $now,
                (int)$caseId,
            ]);
            SystemLogService::log('update', 'strategy', "Estratégia atualizada para o processo #{$caseId}", 'case_strategy', (int)$existing['id']);
        } else {
            $stmt = $db->prepare(
                "INSERT INTO process_strategy_notes
                    (case_id, tese_principal, tese_subsidiaria, riscos,
                     provas_favoraveis, provas_desfavoraveis, proximos_passos,
                     valor_provavel, chance_acordo, valor_minimo_acordo,
                     observacoes, internal_only, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)"
            );
            $stmt->execute([
                (int)$caseId,
                $_POST['tese_principal'] ?? '',
                $_POST['tese_subsidiaria'] ?? '',
                $_POST['riscos'] ?? '',
                $_POST['provas_favoraveis'] ?? '',
                $_POST['provas_desfavoraveis'] ?? '',
                $_POST['proximos_passos'] ?? '',
                $valorProvavel,
                $chanceAcordo,
                $valorMinimo,
                $_POST['observacoes'] ?? '',
                Session::get('user_id'),
                $now,
                $now,
            ]);
            $newId = (int)$db->lastInsertId();
            SystemLogService::create('strategy', 'case_strategy', $newId, "Estratégia criada para o processo #{$caseId}");
        }

        Logger::audit("Estratégia processual salva para o processo #{$caseId}");
        Session::flash('success', 'Estratégia processual salva com sucesso!');
        $this->redirect("/cases/{$caseId}/strategy");
    }
}
