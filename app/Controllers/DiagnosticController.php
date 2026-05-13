<?php
declare(strict_types=1);
namespace App\Controllers;
use Core\Controller;
use Core\Database;

class DiagnosticController extends Controller
{
    public function index(): void
    {
        $db = Database::getInstance();
        $checks = [];
        $tables = ['users','clients','cases','tasks','financial_entries','case_movements','documents','timesheets','publications','tribunal_connections'];
        foreach ($tables as $t) {
            try { $checks['Tabela '.$t] = ['ok'=>true,'info'=>(string)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn().' registros']; }
            catch (\Throwable $e) { $checks['Tabela '.$t] = ['ok'=>false,'info'=>$e->getMessage()]; }
        }
        $checks['PHP'] = ['ok'=>version_compare(PHP_VERSION,'7.4','>='),'info'=>PHP_VERSION];
        $checks['cURL'] = ['ok'=>function_exists('curl_init'),'info'=>function_exists('curl_init')?'habilitado':'indisponível'];
        $checks['finfo'] = ['ok'=>class_exists('finfo'),'info'=>class_exists('finfo')?'habilitado':'indisponível'];
        $checks['storage gravável'] = ['ok'=>is_writable(ROOT_PATH.'/storage'),'info'=>ROOT_PATH.'/storage'];
        $checks['logs gravável'] = ['ok'=>is_writable(ROOT_PATH.'/logs'),'info'=>ROOT_PATH.'/logs'];
        $this->render('diagnostics/index', ['pageTitle'=>'Diagnóstico do Sistema','checks'=>$checks]);
    }
}
