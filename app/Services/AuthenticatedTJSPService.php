<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\LegalCase;
use Core\Logger;

class AuthenticatedTJSPService
{
    private $connService;
    private $cookieFile;
    private $baseUrls = [
        'eproc' => [
            'https://eproc1g.tjsp.jus.br/eproc',
            'https://eproc2g.tjsp.jus.br/eproc',
            'https://eproc1g-consulta.tjsp.jus.br/eproc',
            'https://eproc2g-consulta.tjsp.jus.br/eproc',
        ],
        'esaj' => [
            'https://esaj.tjsp.jus.br',
        ],
    ];

    public function __construct()
    {
        $this->connService = new TribunalConnectionService();
        $this->cookieFile = sys_get_temp_dir() . '/juriscontrol_tjsp_auth_' . md5(__FILE__) . '.cookie';
    }

    public function testConnection(array $connection): array
    {
        $cookies = $this->connService->revealCookies($connection);
        if (trim($cookies) === '') {
            return [
                'success' => false,
                'message' => 'Sessão/cookies não cadastrados. Login por senha foi salvo, mas ainda não é usado automaticamente contra SSO/captcha. Importe os cookies da sessão autenticada do navegador.'
            ];
        }
        $this->writeCookieFileFromHeader($cookies);
        $sistema = strtolower((string)($connection['sistema'] ?? 'eproc'));
        $html = $this->request('GET', $this->baseUrls[$sistema][0] ?? $this->baseUrls['eproc'][0], [], []);
        if ($html === false) {
            return ['success' => false, 'message' => 'Não foi possível acessar o tribunal com a sessão informada.'];
        }
        if ($this->looksLoggedIn($html)) {
            return ['success' => true, 'message' => 'Sessão autenticada aparentemente válida.'];
        }
        return ['success' => false, 'message' => 'A sessão foi aceita tecnicamente, mas a página não indicou usuário autenticado. Atualize os cookies após fazer login no tribunal.'];
    }

    public function importByOAB(int $userId, string $oabNumber, string $oabState): array
    {
        $connection = $this->connService->getBestConnection($userId, 'tjsp');
        if (!$connection) {
            return [
                'success' => false,
                'message' => 'Nenhuma conexão autenticada do TJSP foi cadastrada. Acesse Tribunais > Conectar Tribunal e cadastre a sessão do eproc/e-SAJ.',
                'needs_connection' => true,
                'new' => 0,
                'updated' => 0,
            ];
        }

        $cookies = $this->connService->revealCookies($connection);
        if (trim($cookies) === '') {
            return [
                'success' => false,
                'message' => 'Há conexão cadastrada, mas sem cookies/sessão autenticada. Para segredo de justiça e importação por OAB, faça login no eproc/e-SAJ no navegador e cole os cookies em Tribunais > Conectar Tribunal.',
                'needs_connection' => true,
                'new' => 0,
                'updated' => 0,
            ];
        }

        $this->writeCookieFileFromHeader($cookies);
        $sistema = strtolower((string)($connection['sistema'] ?? 'eproc'));
        $result = $sistema === 'esaj'
            ? $this->importEsajByOAB($oabNumber, $oabState)
            : $this->importEprocByOAB($oabNumber, $oabState);

        $this->connService->markSync((int)$connection['id'], $result['success'] ? null : ($result['message'] ?? null));
        return $result;
    }

    private function importEprocByOAB(string $oabNumber, string $oabState): array
    {
        $attempts = [];
        $htmlParts = [];
        foreach ($this->baseUrls['eproc'] as $base) {
            $urls = [
                $base . '/controlador.php?acao=processo_selecionar&acao_origem=processo_consultar&acao_retorno=processo_consultar',
                $base . '/externo_controlador.php?acao=processo_consulta_publica',
                $base . '/controlador.php?acao=processo_consultar',
                $base . '/',
            ];
            foreach ($urls as $url) {
                $html = $this->request('GET', $url, [], $attempts);
                if ($html !== false) {
                    $htmlParts[] = $html;
                    $posted = $this->trySubmitFormsForOab($html, $url, $oabNumber, $oabState, $attempts);
                    foreach ($posted as $p) $htmlParts[] = $p;
                }
            }
        }
        return $this->upsertCasesFromHtml($htmlParts, 'eproc_tjsp_auth', $attempts);
    }

    private function importEsajByOAB(string $oabNumber, string $oabState): array
    {
        $attempts = [];
        $urls = [
            'https://esaj.tjsp.jus.br/cpopg/open.do',
            'https://esaj.tjsp.jus.br/cposg/open.do',
            'https://esaj.tjsp.jus.br/esaj/portal.do?servico=740000',
        ];
        $htmlParts = [];
        foreach ($urls as $url) {
            $html = $this->request('GET', $url, [], $attempts);
            if ($html !== false) {
                $htmlParts[] = $html;
                $posted = $this->trySubmitFormsForOab($html, $url, $oabNumber, $oabState, $attempts);
                foreach ($posted as $p) $htmlParts[] = $p;
            }
        }
        return $this->upsertCasesFromHtml($htmlParts, 'esaj_tjsp_auth', $attempts);
    }

    private function trySubmitFormsForOab(string $html, string $baseUrl, string $oabNumber, string $oabState, array &$attempts): array
    {
        $forms = $this->extractForms($html, $baseUrl);
        $responses = [];
        foreach ($forms as $form) {
            $payloads = $this->buildOabPayloads($form, $oabNumber, $oabState);
            foreach ($payloads as $payload) {
                $resp = $this->request($form['method'], $form['action'], $payload, $attempts, ['Referer: '.$baseUrl, 'Content-Type: application/x-www-form-urlencoded']);
                if ($resp !== false) $responses[] = $resp;
            }
        }
        return $responses;
    }

    private function upsertCasesFromHtml(array $htmlParts, string $source, array $attempts): array
    {
        $numbers = [];
        foreach ($htmlParts as $html) {
            if (!is_string($html)) continue;
            preg_match_all('/\d{7}-\d{2}\.\d{4}\.8\.26\.\d{4}/', $html, $m1);
            preg_match_all('/\b\d{20}\b/', $html, $m2);
            foreach (($m1[0] ?? []) as $n) $numbers[$this->formatCnj($n)] = true;
            foreach (($m2[0] ?? []) as $n) {
                if (substr($n, 13, 3) === '826') $numbers[$this->formatCnj($n)] = true;
            }
        }

        $numbers = array_keys($numbers);
        if (empty($numbers)) {
            return [
                'success' => false,
                'message' => 'A sessão autenticada foi usada, mas não foi possível extrair uma lista de processos por OAB. O tribunal pode ter alterado a tela ou exigir navegação/2FA. Tente abrir a tela de processos no navegador, atualize os cookies e tente novamente.',
                'new' => 0,
                'updated' => 0,
                'attempts' => array_slice($attempts, -15),
            ];
        }

        $model = new LegalCase();
        $new = 0; $updated = 0;
        foreach ($numbers as $numero) {
            $status = $model->upsertFromProcessData([
                'numero_cnj' => $numero,
                'tribunal' => 'tjsp',
                'sistema' => strpos($source, 'esaj') !== false ? 'esaj' : 'eproc',
                'status' => 'ativo',
                'fonte_importacao' => $source,
                'last_sync_at' => date('Y-m-d H:i:s'),
                'cnj_raw_data' => json_encode(['fonte' => $source, 'numero_cnj' => $numero], JSON_UNESCAPED_UNICODE),
            ]);
            if ($status === 'new') $new++;
            if ($status === 'updated') $updated++;
        }

        return [
            'success' => true,
            'message' => "Importação autenticada TJSP concluída: {$new} novos processos, {$updated} atualizados.",
            'new' => $new,
            'updated' => $updated,
            'source' => $source,
        ];
    }

    private function buildOabPayloads(array $form, string $oabNumber, string $oabState): array
    {
        $base = $form['fields'] ?? [];
        $payloads = [];
        $smart = $base;
        foreach (array_keys($smart) as $name) {
            $low = strtolower($name);
            if (strpos($low, 'oab') !== false && strpos($low, 'uf') === false && strpos($low, 'estado') === false) $smart[$name] = $oabNumber;
            if (strpos($low, 'uf') !== false || strpos($low, 'estado') !== false) $smart[$name] = $oabState;
            if (strpos($low, 'valor') !== false || strpos($low, 'termo') !== false || strpos($low, 'pesquisa') !== false) $smart[$name] = $oabNumber;
        }
        $payloads[] = $smart;
        $sets = [
            ['numeroOAB' => $oabNumber, 'estadoOAB' => $oabState],
            ['numOAB' => $oabNumber, 'ufOAB' => $oabState],
            ['txtOAB' => $oabNumber, 'txtUfOAB' => $oabState],
            ['dadosConsulta.valorConsulta' => $oabNumber, 'dadosConsulta.localPesquisa.cdLocal' => '-1', 'tipoNuProcesso' => 'UNIFICADO'],
            ['conversationId' => '', 'cbPesquisa' => 'DOCPARTE', 'dadosConsulta.valorConsulta' => $oabNumber . $oabState],
            ['txtValor' => $oabNumber . $oabState, 'tipoPesquisa' => 'OAB'],
        ];
        foreach ($sets as $s) $payloads[] = array_merge($base, $s);
        return $this->uniquePayloads($payloads);
    }

    private function extractForms(string $html, string $baseUrl): array
    {
        $forms = [];
        if (!preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $html, $matches, PREG_SET_ORDER)) return $forms;
        foreach ($matches as $m) {
            $tag = $m[0]; $inside = $m[1]; $method = 'POST'; $action = $baseUrl;
            if (preg_match('/method=["\']?([^"\'\s>]+)/i', $tag, $mm)) $method = strtoupper($mm[1]);
            if (preg_match('/action=["\']([^"\']*)/i', $tag, $am) && trim($am[1]) !== '') $action = $this->absoluteUrl($baseUrl, html_entity_decode($am[1], ENT_QUOTES, 'UTF-8'));
            $fields = [];
            if (preg_match_all('/<(input|textarea|select)\b[^>]*>/is', $inside, $inputs, PREG_SET_ORDER)) {
                foreach ($inputs as $input) {
                    $el = $input[0];
                    if (!preg_match('/name=["\']([^"\']+)/i', $el, $nm)) continue;
                    $name = html_entity_decode($nm[1], ENT_QUOTES, 'UTF-8');
                    $value = '';
                    if (preg_match('/value=["\']([^"\']*)/i', $el, $vm)) $value = html_entity_decode($vm[1], ENT_QUOTES, 'UTF-8');
                    $fields[$name] = $value;
                }
            }
            $forms[] = ['method' => $method, 'action' => $action, 'fields' => $fields];
        }
        return $forms;
    }

    private function request(string $method, string $url, array $data, array &$attempts, array $headers = [])
    {
        $method = strtoupper($method) === 'GET' ? 'GET' : 'POST';
        $fullUrl = $url;
        if ($method === 'GET' && !empty($data)) $fullUrl .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        $ch = curl_init($fullUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
            CURLOPT_HTTPHEADER => array_merge(['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: pt-BR,pt;q=0.9'], $headers),
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
        ]);
        if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
        $body = curl_exec($ch); $err = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); curl_close($ch);
        $attempts[] = ['method'=>$method,'url'=>$fullUrl,'effective_url'=>$effective,'http_code'=>$code,'error'=>$err,'posted_fields'=>$method==='POST'?array_keys($data):[]];
        if ($err || $body === false || $code >= 500 || $code === 0) { Logger::warning('Authenticated TJSP request failed', ['url'=>$fullUrl,'code'=>$code,'error'=>$err]); return false; }
        return (string)$body;
    }

    private function writeCookieFileFromHeader(string $cookies): void
    {
        @file_put_contents($this->cookieFile, '');
        $lines = preg_split('/\r\n|\r|\n|;\s*/', trim($cookies));
        $domains = ['.tjsp.jus.br','eproc1g.tjsp.jus.br','eproc2g.tjsp.jus.br','esaj.tjsp.jus.br'];
        $out = "# Netscape HTTP Cookie File\n";
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '=') === false || stripos($line, 'path=') === 0 || stripos($line, 'domain=') === 0 || stripos($line, 'expires=') === 0 || stripos($line, 'max-age=') === 0) continue;
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name); $value = trim($value);
            if ($name === '') continue;
            foreach ($domains as $domain) {
                $out .= $domain . "\tTRUE\t/\tTRUE\t0\t" . $name . "\t" . $value . "\n";
            }
        }
        @file_put_contents($this->cookieFile, $out);
    }

    private function looksLoggedIn(string $html): bool
    {
        $t = strtolower(strip_tags($html));
        return strpos($t, 'sair') !== false || strpos($t, 'logout') !== false || strpos($t, 'meus processos') !== false || strpos($t, 'intima') !== false;
    }

    private function formatCnj(string $numero): string
    {
        $n = preg_replace('/\D/', '', $numero);
        if (strlen($n) !== 20) return $numero;
        return substr($n,0,7).'-'.substr($n,7,2).'.'.substr($n,9,4).'.'.substr($n,13,1).'.'.substr($n,14,2).'.'.substr($n,16,4);
    }

    private function absoluteUrl(string $base, string $url): string
    {
        if (preg_match('/^https?:\/\//i', $url)) return $url;
        $p = parse_url($base); $scheme = $p['scheme'] ?? 'https'; $host = $p['host'] ?? '';
        if (strpos($url, '/') === 0) return $scheme.'://'.$host.$url;
        $path = $p['path'] ?? '/'; $dir = rtrim(str_replace('\\','/', dirname($path)), '/');
        return $scheme.'://'.$host.$dir.'/'.ltrim($url, '/');
    }

    private function uniquePayloads(array $payloads): array
    {
        $seen = []; $out = [];
        foreach ($payloads as $p) { ksort($p); $key = md5(json_encode($p)); if (!isset($seen[$key])) { $seen[$key] = true; $out[] = $p; } }
        return $out;
    }
}
