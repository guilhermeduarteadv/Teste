<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Database;
use Core\Session;
use App\Models\LegalCase;
use App\Models\Client;

class TimesheetController extends Controller
{
    public function index(): void
    {
        $db=Database::getInstance();
        $rows=$db->query("SELECT ts.*, c.numero_cnj, cl.name as client_name, u.name as user_name FROM timesheets ts LEFT JOIN cases c ON ts.case_id=c.id LEFT JOIN clients cl ON ts.client_id=cl.id LEFT JOIN users u ON ts.user_id=u.id WHERE ts.deleted_at IS NULL ORDER BY ts.data DESC, ts.id DESC LIMIT 200")->fetchAll();
        $summary=$db->query("SELECT COALESCE(SUM(minutos),0) as minutos, COALESCE(SUM((minutos/60)*valor_hora),0) as valor FROM timesheets WHERE deleted_at IS NULL")->fetch();
        $this->render('timesheets/index',['pageTitle'=>'Timesheet','entries'=>$rows,'summary'=>$summary,'cases'=>(new LegalCase())->findAll([], 'numero_cnj ASC'),'clients'=>(new Client())->findAll(['status'=>'active'],'name ASC')]);
    }
    public function store(): void
    {
        $this->validateCsrf();
        $caseId=(int)$this->input('case_id','0') ?: null; $clientId=(int)$this->input('client_id','0') ?: null;
        $data=$this->input('data',date('Y-m-d')); $inicio=$this->input('inicio','') ?: null; $fim=$this->input('fim','') ?: null;
        $minutos=(int)$this->input('minutos','0');
        if($minutos<=0 && $inicio && $fim){ $minutos=max(0,(strtotime($fim)-strtotime($inicio))/60); }
        $descricao=$this->input('descricao',''); if($descricao===''){ $this->json(['success'=>false,'message'=>'Descrição é obrigatória.']); }
        $stmt=Database::getInstance()->prepare("INSERT INTO timesheets (case_id, client_id, user_id, data, inicio, fim, minutos, descricao, atividade, valor_hora, faturavel, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
        $stmt->execute([$caseId,$clientId,Session::get('user_id'),$data,$inicio,$fim,(int)$minutos,$descricao,$this->input('atividade',''),(float)str_replace(['.',','],['','.'],$this->input('valor_hora','0')),isset($_POST['faturavel'])?1:0]);
        $this->json(['success'=>true,'message'=>'Hora lançada com sucesso.']);
    }
    public function delete(string $id): void
    {
        $this->validateCsrf();
        Database::getInstance()->prepare("UPDATE timesheets SET deleted_at=NOW(), updated_at=NOW() WHERE id=?")->execute([(int)$id]);
        $this->json(['success'=>true,'message'=>'Lançamento excluído.']);
    }
}
