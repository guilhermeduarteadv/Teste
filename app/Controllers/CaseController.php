<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Logger;
use App\Models\LegalCase;
use App\Models\Client;
use App\Models\User;
use App\Services\CNJService;
use App\Services\ProcessSyncService;
use App\Services\SystemLogService;
use App\Services\TribunalPreferenceService;
use App\Helpers\DateHelper;
use App\Helpers\ValidationHelper;

class CaseController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new LegalCase();
    }

    public function index(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = [
            'search'    => $this->input('search', ''),
            'status'    => $this->input('status', ''),
            'tribunal'  => $this->input('tribunal', ''),
        ];
        $result = $this->model->findAllPaginated($page, $perPage, $filters);
        $config = require ROOT_PATH . '/config/api.php';

        $this->render('cases/index', [
            'pageTitle'  => 'Processos',
            'cases'      => $result['data'],
            'pagination' => $result,
            'filters'    => $filters,
            'tribunais'  => $config['cnj']['tribunais'],
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('cases.create');
        $clients = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $users   = (new User())->findAllWithRoles(['status' => 'active']);
        $tribunais = (new TribunalPreferenceService())->enabled();

        $this->render('cases/create', [
            'pageTitle' => 'Novo Processo',
            'clients'   => $clients,
            'users'     => $users,
            'tribunais' => $tribunais,
            'classesProcessuais' => self::classesProcessuais(),
            'areasProcessuais' => self::areasProcessuais(),
            'fasesProcessuais' => self::fasesProcessuais(),
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('cases.create');
        $this->validateCsrf();

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)
            ->required('assunto', 'Assunto')
            ->numeric('valor_causa', 'Valor da Causa');

        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            Session::flash('old', $data);
            $this->redirect('/cases/create');
        }

        $data['responsavel_id'] = $data['responsavel_id'] ?: Session::get('user_id');
        $id = $this->model->insert($data);

        // Add clients to case
        $clientIds = $_POST['client_ids'] ?? ($_POST['clients'] ?? []);
        foreach ($clientIds as $clientId) {
            $this->model->addClient($id, (int)$clientId, $_POST['client_tipos'][$clientId] ?? 'autor');
        }

        SystemLogService::create('cases', 'case', $id, "Novo processo criado: {$data['numero_cnj']}");
        Logger::audit("Processo criado: {$data['numero_cnj']} ID #{$id}");

        Session::flash('success', 'Processo cadastrado com sucesso!');
        $this->redirect("/cases/{$id}");
    }

    public function show(string $id): void
    {
        $case = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $clients   = $this->model->getClients((int)$id);
        $movements = $this->model->getMovements((int)$id);
        $deadlines = $this->model->getDeadlines((int)$id);
        $hearings  = $this->model->getHearings((int)$id);
        $documents = $this->model->getDocuments((int)$id);
        $financial = $this->model->getFinancial((int)$id);
        $tasks     = $this->model->getTasks((int)$id);
        $db        = \Core\Database::getInstance();
        $stmtC     = $db->prepare("SELECT * FROM case_contacts WHERE case_id = ? AND deleted_at IS NULL ORDER BY nome");
        $stmtC->execute([(int)$id]);
        $caseContacts = $stmtC->fetchAll(\PDO::FETCH_ASSOC);
        $stmtW     = $db->prepare("SELECT * FROM case_witnesses WHERE case_id = ? AND deleted_at IS NULL ORDER BY nome");
        $stmtW->execute([(int)$id]);
        $caseWitnesses = $stmtW->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('cases/show', [
            'pageTitle' => 'Processo: ' . ($case['numero_cnj'] ?: $case['assunto']),
            'case'      => $case,
            'clients'   => $clients,
            'movements' => $movements,
            'deadlines' => $deadlines,
            'hearings'  => $hearings,
            'documents' => $documents,
            'financial' => $financial,
            'tasks'     => $tasks,
            'caseContacts' => $caseContacts,
            'caseWitnesses' => $caseWitnesses,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('cases.edit');
        $case    = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }
        $clients     = (new Client())->findAll(['status' => 'active'], 'name ASC');
        $caseClients = $this->model->getClients((int)$id);
        $users       = (new User())->findAllWithRoles(['status' => 'active']);
        $tribunais   = (new TribunalPreferenceService())->enabled();

        $this->render('cases/edit', [
            'pageTitle'   => 'Editar Processo',
            'case'        => $case,
            'clients'     => $clients,
            'caseClients' => $caseClients,
            'users'       => $users,
            'tribunais'   => $tribunais,
            'classesProcessuais' => self::classesProcessuais(),
            'areasProcessuais' => self::areasProcessuais(),
            'fasesProcessuais' => self::fasesProcessuais(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('cases.edit');
        $this->validateCsrf();

        $case = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $data = $this->collectFormData();
        $validator = ValidationHelper::make($data)->required('assunto', 'Assunto');
        if ($validator->fails()) {
            Session::flash('error', $validator->firstError());
            $this->redirect("/cases/{$id}/edit");
        }

        $this->model->update((int)$id, $data);

        // Sync clients
        $db = \Core\Database::getInstance();
        $db->prepare("DELETE FROM case_clients WHERE case_id = ?")->execute([(int)$id]);
        $clientIds = $_POST['client_ids'] ?? ($_POST['clients'] ?? []);
        foreach ($clientIds as $clientId) {
            $this->model->addClient((int)$id, (int)$clientId, $_POST['client_tipos'][$clientId] ?? 'autor');
        }

        SystemLogService::update('cases', 'case', (int)$id, $case, $data);
        Logger::audit("Processo atualizado ID #{$id}");

        Session::flash('success', 'Processo atualizado com sucesso!');
        $this->redirect("/cases/{$id}");
    }

    public function delete(string $id): void
    {
        $this->requirePermission('cases.delete');
        $this->validateCsrf();

        $case = $this->model->findWithDetails((int)$id);
        if (!$case) {
            Session::flash('error', 'Processo não encontrado.');
            $this->redirect('/cases');
        }

        $this->model->softDelete((int)$id);
        SystemLogService::delete('cases', 'case', (int)$id);
        Logger::audit("Processo excluído ID #{$id}");

        Session::flash('success', 'Processo excluído com sucesso.');
        $this->redirect('/cases');
    }

    public function syncCnj(string $id): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        try {
            $this->validateCsrf();

            $case = $this->model->findWithDetails((int)$id);
            if (!$case || empty($case['numero_cnj'])) {
                $this->json(['success' => false, 'message' => 'Processo sem número CNJ para sincronizar.']);
                return;
            }

            if (!empty($case['segredo_justica'])) {
                $this->json([
                    'success' => false,
                    'skipped' => true,
                    'message' => 'Processo marcado como segredo de justiça. A sincronização automática foi bloqueada para este processo.'
                ]);
                return;
            }

            $sync = new ProcessSyncService();
            $result = $sync->syncCase((int)$id, $case['numero_cnj'], $case['tribunal'] ?? 'TJSP');
            $this->json($result);
        } catch (\Throwable $e) {
            \Core\Logger::error('Sincronização do processo falhou: ' . $e->getMessage(), [
                'case_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $this->json([
                'success' => false,
                'message' => 'Erro ao sincronizar processo: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function addMovement(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findById((int)$id);
        if (!$case) {
            $this->json(['success' => false, 'message' => 'Processo não encontrado.']);
        }

        $data = [
            'data_movimento'  => $this->input('data_movimento', date('Y-m-d H:i:s')),
            'tipo'            => $this->input('tipo', ''),
            'descricao'       => $this->input('descricao', ''),
            'fonte'           => 'manual',
            'visivel_cliente' => isset($_POST['visivel_cliente']) ? 1 : 0,
            'created_by'      => Session::get('user_id'),
        ];

        if (empty($data['descricao'])) {
            $this->json(['success' => false, 'message' => 'Descrição é obrigatória.']);
        }

        $movId = $this->model->addMovement((int)$id, $data);
        SystemLogService::create('cases', 'movement', $movId, "Movimentação adicionada ao processo #{$id}");

        $this->json(['success' => true, 'message' => 'Movimentação adicionada com sucesso!']);
    }

    public function addDeadline(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findById((int)$id);
        if (!$case) {
            $this->json(['success' => false, 'message' => 'Processo não encontrado.']);
        }

        $tipo       = $this->input('tipo', 'processual');
        $descricao  = $this->input('descricao', '');
        $dataInicio = $this->input('data_inicio', date('Y-m-d'));
        $prazoDias  = (int)$this->input('prazo_dias', '0');
        $dataFinal  = $this->input('data_final', '');

        if (empty($descricao)) {
            $this->json(['success' => false, 'message' => 'Descrição é obrigatória.']);
        }

        // Calculate deadline if days provided
        $dataFinalCalculada = null;
        if ($prazoDias > 0) {
            $dataFinalCalculada = DateHelper::calculateDeadline($dataInicio, $prazoDias, $tipo);
            if (empty($dataFinal)) $dataFinal = $dataFinalCalculada;
        }

        if (empty($dataFinal)) {
            $this->json(['success' => false, 'message' => 'Data final é obrigatória.']);
        }

        $data = [
            'tipo'                => $tipo,
            'descricao'           => $descricao,
            'data_inicio'         => $dataInicio,
            'prazo_dias'          => $prazoDias,
            'data_final'          => $dataFinal,
            'data_final_calculada' => $dataFinalCalculada,
            'confirmado'          => isset($_POST['confirmado']) ? 1 : 0,
            'visivel_cliente'     => isset($_POST['visivel_cliente']) ? 1 : 0,
            'observacoes'         => $this->input('observacoes', ''),
            'created_by'          => Session::get('user_id'),
        ];

        $deadlineId = $this->model->addDeadline((int)$id, $data);
        SystemLogService::create('cases', 'deadline', $deadlineId, "Prazo adicionado ao processo #{$id}");

        $this->json(['success' => true, 'message' => 'Prazo adicionado com sucesso!', 'data_final_calculada' => $dataFinalCalculada]);
    }

    public function addHearing(string $id): void
    {
        $this->validateCsrf();
        $case = $this->model->findById((int)$id);
        if (!$case) {
            $this->json(['success' => false, 'message' => 'Processo não encontrado.']);
        }

        $titulo = $this->input('titulo', '');
        $data   = $this->input('data', '');
        $hora   = $this->input('hora', '');
        if (empty($titulo) || empty($data) || empty($hora)) {
            $this->json(['success' => false, 'message' => 'Título, data e hora são obrigatórios.']);
        }

        $db = \Core\Database::getInstance();
        $stmt = $db->prepare("INSERT INTO case_hearings (case_id, tipo, titulo, data, hora, local, observacoes, status, visivel_cliente, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, NOW(), NOW())");
        $stmt->execute([
            (int)$id,
            $this->input('tipo', 'conciliacao'),
            $titulo, $data, $hora,
            $this->input('local', ''),
            $this->input('observacoes', ''),
            isset($_POST['visivel_cliente']) ? 1 : 0,
            Session::get('user_id'),
        ]);
        $hearingId = (int)$db->lastInsertId();
        SystemLogService::create('cases', 'hearing', $hearingId, "Audiência adicionada ao processo #{$id}");

        $this->json(['success' => true, 'message' => 'Audiência adicionada com sucesso!']);
    }



    public function addContact(string $id): void
    {
        $this->validateCsrf();
        $nome = $this->input('nome','');
        if (empty($nome)) { $this->json(['success'=>false,'message'=>'Nome é obrigatório.']); }
        $db = \Core\Database::getInstance();
        $stmt = $db->prepare("INSERT INTO case_contacts (case_id,nome,tipo,telefone,email,documento,endereco,observacoes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
        $stmt->execute([(int)$id,$nome,$this->input('tipo',''),$this->input('telefone',''),$this->input('email',''),$this->input('documento',''),$this->input('endereco',''),$this->input('observacoes','')]);
        $this->json(['success'=>true,'message'=>'Contato incluído.']);
    }

    public function addWitness(string $id): void
    {
        $this->validateCsrf();
        $nome = $this->input('nome','');
        if (empty($nome)) { $this->json(['success'=>false,'message'=>'Nome é obrigatório.']); }
        $db = \Core\Database::getInstance();
        $stmt = $db->prepare("INSERT INTO case_witnesses (case_id,nome,telefone,email,documento,endereco,resumo_depoimento,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())");
        $stmt->execute([(int)$id,$nome,$this->input('telefone',''),$this->input('email',''),$this->input('documento',''),$this->input('endereco',''),$this->input('resumo_depoimento',''),$this->input('status','a_arrolar')]);
        $this->json(['success'=>true,'message'=>'Testemunha incluída.']);
    }


    public function searchClients(): void
    {
        $term = trim($_GET['q'] ?? '');

        if (mb_strlen($term) < 2) {
            $this->json(['success' => true, 'clients' => []]);
            return;
        }

        $db = \Core\Database::getInstance();

        $columnExists = function(string $column) use ($db): bool {
            try {
                $stmt = $db->prepare("SHOW COLUMNS FROM clients LIKE ?");
                $stmt->execute([$column]);
                return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                return false;
            }
        };

        $docColumn = $columnExists('cpf_cnpj') ? 'cpf_cnpj' : ($columnExists('cpf') ? 'cpf' : null);
        $emailColumn = $columnExists('email') ? 'email' : null;
        $phoneColumn = $columnExists('phone') ? 'phone' : ($columnExists('telefone') ? 'telefone' : null);

        $select = ['id', 'name'];
        if ($docColumn) $select[] = "{$docColumn} AS cpf_cnpj"; else $select[] = "'' AS cpf_cnpj";
        if ($emailColumn) $select[] = "{$emailColumn} AS email"; else $select[] = "'' AS email";
        if ($phoneColumn) $select[] = "{$phoneColumn} AS phone"; else $select[] = "'' AS phone";

        $where = ["name LIKE ?"];
        $params = ['%' . $term . '%'];

        if ($emailColumn) {
            $where[] = "{$emailColumn} LIKE ?";
            $params[] = '%' . $term . '%';
        }

        $digits = preg_replace('/\D/', '', $term);
        if ($docColumn && $digits !== '') {
            $where[] = "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$docColumn},''),'.',''),'-',''),'/',''),' ','') LIKE ?";
            $params[] = '%' . $digits . '%';
        }

        if ($phoneColumn && $digits !== '') {
            $where[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$phoneColumn},''),'(',''),')',''),'-',''),' ',''),'.','') LIKE ?";
            $params[] = '%' . $digits . '%';
        }

        $sql = "
            SELECT " . implode(', ', $select) . "
            FROM clients
            WHERE deleted_at IS NULL
              AND (" . implode(' OR ', $where) . ")
            ORDER BY name ASC
            LIMIT 20
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $this->json([
            'success' => true,
            'clients' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ]);
    }


    public function searchCasesByClient(): void
    {
        $clientId = (int)($_GET['client_id'] ?? 0);

        if ($clientId <= 0) {
            $this->json(['success' => true, 'cases' => []]);
            return;
        }

        $db = \Core\Database::getInstance();

        $sql = "
            SELECT c.id, c.numero_cnj, c.assunto, c.classe, c.status
            FROM cases c
            INNER JOIN case_clients cc ON cc.case_id = c.id
            WHERE c.deleted_at IS NULL
              AND cc.client_id = ?
            ORDER BY c.updated_at DESC, c.id DESC
            LIMIT 100
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$clientId]);

        $this->json([
            'success' => true,
            'cases' => $stmt->fetchAll(\PDO::FETCH_ASSOC)
        ]);
    }

    private function collectFormData(): array
    {
        return [
            'numero_cnj'        => $this->input('numero_cnj', ''),
            'tribunal'          => $this->input('tribunal', ''),
            'comarca'           => $this->input('comarca', ''),
            'vara'              => $this->input('vara', ''),
            'classe'            => $this->input('classe', ''),
            'area'              => $this->input('area', ''),
            'assunto'           => $this->input('assunto', ''),
            'valor_causa'       => (float)str_replace(['.', ','], ['', '.'], $this->input('valor_causa', '0')),
            'fase_processual'   => $this->input('fase_processual', ''),
            'status'            => $this->input('status', 'ativo'),
            'risco_processual'  => $this->input('risco_processual', '') ?: null,
            'probabilidade_exito' => $this->input('probabilidade_exito', '') ?: null,
            'proxima_providencia' => $this->input('proxima_providencia', ''),
            'outras_informacoes' => $this->input('outras_informacoes', ''),
            'responsavel_id'    => (int)$this->input('responsavel_id', '0') ?: null,
            'segredo_justica'  => isset($_POST['segredo_justica']) ? 1 : 0,
            'parte_contraria_nome' => $this->input('parte_contraria_nome', ''),
            'parte_contraria_tipo_pessoa' => $this->input('parte_contraria_tipo_pessoa', ''),
            'parte_contraria_cpf_cnpj' => $this->input('parte_contraria_cpf_cnpj', ''),
            'parte_contraria_rg_ie' => $this->input('parte_contraria_rg_ie', ''),
            'parte_contraria_email' => $this->input('parte_contraria_email', ''),
            'parte_contraria_telefone' => $this->input('parte_contraria_telefone', ''),
            'parte_contraria_endereco' => $this->input('parte_contraria_endereco', ''),
            'parte_contraria_numero' => $this->input('parte_contraria_numero', ''),
            'parte_contraria_complemento' => $this->input('parte_contraria_complemento', ''),
            'parte_contraria_bairro' => $this->input('parte_contraria_bairro', ''),
            'parte_contraria_cidade' => $this->input('parte_contraria_cidade', ''),
            'parte_contraria_estado' => $this->input('parte_contraria_estado', ''),
            'parte_contraria_cep' => $this->input('parte_contraria_cep', ''),
            'parte_contraria_advogado' => $this->input('parte_contraria_advogado', ''),
            'parte_contraria_advogado_oab' => $this->input('parte_contraria_advogado_oab', ''),
            'parte_contraria_observacoes' => $this->input('parte_contraria_observacoes', ''),
        ];
    }

    private static function classesProcessuais(): array
    {
        return ['Procedimento Comum Cível','Cumprimento de sentença','Execução de título extrajudicial','Juizado Especial Cível','Alimentos','Divórcio litigioso','Divórcio consensual','Guarda','Regulamentação de visitas','Inventário','Arrolamento','Ação trabalhista - rito ordinário','Ação trabalhista - rito sumaríssimo','Mandado de segurança','Agravo de instrumento','Apelação Cível','Embargos à execução','Monitória','Busca e apreensão','Consignação em pagamento','Obrigação de fazer','Indenização por dano moral','Revisional de contrato','Outros'];
    }

    private static function areasProcessuais(): array
    {
        return ['Cível','Família e Sucessões','Consumidor','Trabalhista','Previdenciário','Empresarial','Tributário','Criminal','Administrativo','Saúde','Imobiliário','Bancário','Juizado Especial','Outros'];
    }

    private static function fasesProcessuais(): array
    {
        return ['Atendimento inicial','Pré-processual','Inicial a distribuir','Distribuído','Citação','Contestação/defesa','Réplica','Saneamento','Instrução','Audiência designada','Aguardando sentença','Sentença publicada','Recurso','Contrarrazões','Trânsito em julgado','Cumprimento de sentença','Execução','Penhora/bloqueio','Acordo','Arquivado','Suspenso','Encerrado'];
    }
}
