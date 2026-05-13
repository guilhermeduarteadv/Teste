<?php
declare(strict_types=1);
namespace App\Controllers;
use Core\Controller;
use Core\Database;
use Core\Session;

class TemplateController extends Controller
{
    public function index(): void { $db=Database::getInstance(); $rows=$db->query("SELECT * FROM legal_templates WHERE deleted_at IS NULL ORDER BY area,tipo,titulo")->fetchAll(\PDO::FETCH_ASSOC); $this->render('templates/index',['pageTitle'=>'Modelos de Peças','templates'=>$rows]); }
    public function store(): void { $this->validateCsrf(); $db=Database::getInstance(); $st=$db->prepare("INSERT INTO legal_templates (titulo,area,tipo,conteudo,created_by,created_at,updated_at) VALUES (?,?,?,?,?,NOW(),NOW())"); $st->execute([$this->input('titulo',''),$this->input('area',''),$this->input('tipo',''),$this->input('conteudo',''),Session::get('user_id')]); Session::flash('success','Modelo salvo.'); $this->redirect('/templates'); }
    public function delete(string $id): void { $this->validateCsrf(); $db=Database::getInstance(); $st=$db->prepare("UPDATE legal_templates SET deleted_at=NOW() WHERE id=?"); $st->execute([(int)$id]); Session::flash('success','Modelo excluído.'); $this->redirect('/templates'); }
}
