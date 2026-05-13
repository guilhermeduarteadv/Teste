<?php
declare(strict_types=1);
namespace App\Controllers;
use Core\Controller;
use Core\Database;
use Core\Session;

class BackupController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $rows = [];
        try { $rows = $db->query("SELECT * FROM backups ORDER BY id DESC LIMIT 50")->fetchAll(\PDO::FETCH_ASSOC); } catch (\Throwable $e) {}
        $this->render('backups/index', ['pageTitle'=>'Backup e Restauração','backups'=>$rows]);
    }
    public function create(): void
    {
        $this->validateCsrf();
        $db = Database::getInstance();
        $dir = ROOT_PATH . '/storage/backups'; if (!is_dir($dir)) mkdir($dir,0775,true);
        $file = 'backup_' . date('Ymd_His') . '.sql'; $path = $dir . '/' . $file;
        $out = "-- JurisControl backup gerado em ".date('Y-m-d H:i:s')."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n";
        $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
            $out .= "\nDROP TABLE IF EXISTS `$table`;\n" . ($create['Create Table'] ?? array_values($create)[1]) . ";\n";
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(function($c) { return '`' . str_replace('`', '``', $c) . '`'; }, array_keys($row));
                $vals = array_map(function($v) use ($db) { return $v === null ? 'NULL' : $db->quote((string)$v); }, array_values($row));
                $out .= "INSERT INTO `$table` (".implode(',',$cols).") VALUES (".implode(',',$vals).");\n";
            }
        }
        $out .= "SET FOREIGN_KEY_CHECKS=1;\n"; file_put_contents($path,$out);
        $stmt=$db->prepare("INSERT INTO backups (filename,path,size_bytes,created_by,created_at) VALUES (?,?,?,?,NOW())");
        $stmt->execute([$file,'storage/backups/'.$file,filesize($path),Session::get('user_id')]);
        Session::flash('success','Backup criado com sucesso.'); $this->redirect('/backups');
    }
    public function download(string $id): void
    {
        $db=Database::getInstance(); $st=$db->prepare("SELECT * FROM backups WHERE id=?"); $st->execute([(int)$id]); $b=$st->fetch(\PDO::FETCH_ASSOC);
        $path = $b ? ROOT_PATH . '/' . $b['path'] : '';
        if (!$b || !is_file($path)) { http_response_code(404); echo 'Backup não encontrado'; return; }
        header('Content-Type: application/sql'); header('Content-Disposition: attachment; filename="'.$b['filename'].'"'); readfile($path); exit;
    }
}
