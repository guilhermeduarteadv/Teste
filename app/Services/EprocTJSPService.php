<?php
declare(strict_types=1);

namespace App\Services;

use Core\Logger;

class EprocTJSPService
{
    private $publicUrls = [
        '1' => 'https://eproc1g-consulta.tjsp.jus.br/eproc/externo_controlador.php?acao=tjsp@consulta_unificada_publica/consultar',
        '2' => 'https://eproc2g-consulta.tjsp.jus.br/eproc/externo_controlador.php?acao=tjsp@consulta_unificada_publica/consultar',
    ];

    private $legacyBaseUrls = [
        'https://eproc1g.tjsp.jus.br/eproc',
        'https://eproc2g.tjsp.jus.br/eproc',
    ];

    private $cookieFile;

    public function __construct()
    {
        $this->cookieFile = sys_get_temp_dir() . '/juriscontrol_eproc_tjsp_' . md5(__FILE__) . '.cookie';
    }

    public function searchByOAB(string $oabNumber, string $oabState): array
    {
        $oab = preg_replace('/\D/', '', $oabNumber);
        $uf = strtoupper(trim($oabState));
        $attempts = [];

        if ($oab === '' || $uf === '') {
            return ['success' => false, 'message' => 'Número OAB e UF são obrigatórios.', 'data' => [], 'attempts' => []];
        }

        foreach ($this->publicUrls as $instancia => $url) {
            $result = $this->submitPublicSearch($url, [
                'type' => 'oab',
                'oab' => $oab,
                'uf' => $uf,
                'instancia' => $instancia,
            ], $attempts);

            if (!empty($result['success']) && !empty($result['data'])) {
                return [
                    'success' => true,
                    'message' => count($result['data']) . ' processos encontrados no eproc/TJSP por OAB.',
                    'data' => $result['data'],
                    'attempts' => array_slice($attempts, -12),
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Não foi possível importar automaticamente processos eproc/TJSP por OAB. A consulta pública respondeu, mas não retornou lista automatizável para a OAB informada.',
            'data' => [],
            'manual_url' => $this->publicUrls['1'],
            'attempts' => array_slice($attempts, -12),
        ];
    }

    public function searchByNumber(string $numeroCnj, string $instancia = '1'): array
    {
        $numeroFormatado = $this->formatCnj($numeroCnj);
        $numeroLimpo = preg_replace('/\D/', '', $numeroCnj);
        $attempts = [];

        if (strlen($numeroLimpo) !== 20) {
            return [
                'success' => false,
                'message' => 'Número CNJ inválido para consulta no eproc/TJSP.',
                'data' => null,
                'attempts' => []
            ];
        }

        $order = [];
        $instancia = (string)$instancia;
        if (isset($this->publicUrls[$instancia])) {
            $order[$instancia] = $this->publicUrls[$instancia];
        }
        foreach ($this->publicUrls as $k => $v) {
            if (!isset($order[$k])) {
                $order[$k] = $v;
            }
        }

        foreach ($order as $inst => $url) {
            $result = $this->submitPublicSearch($url, [
                'type' => 'number',
                'numero_formatado' => $numeroFormatado,
                'numero_limpo' => $numeroLimpo,
                'instancia' => $inst,
            ], $attempts);

            if (!empty($result['success']) && !empty($result['data'])) {
                return [
                    'success' => true,
                    'message' => 'Processo localizado no eproc/TJSP.',
                    'data' => $result['data'],
                    'attempts' => array_slice($attempts, -12),
                ];
            }
        }

        // Último fallback: antigas URLs do eproc, apenas para alcançar eventual redirect para consulta pública.
        foreach ($this->legacyBaseUrls as $base) {
            $urls = [
                $base . '/externo_controlador.php?acao=processo_consulta_publica',
                $base . '/controlador.php?acao=processo_consulta_publica',
            ];
            foreach ($urls as $url) {
                $html = $this->request('GET', $url, [], [], $attempts);
                if ($html === false) {
                    continue;
                }
                $parsed = $this->parseProcessPage($html, $numeroFormatado, $numeroLimpo);
                if ($parsed !== null) {
                    return ['success' => true, 'message' => 'Processo localizado no eproc/TJSP.', 'data' => $parsed, 'attempts' => array_slice($attempts, -12)];
                }
            }
        }

        return [
            'success' => false,
            'message' => 'O eproc/TJSP respondeu, mas o processo não foi localizado ou a tela pública não retornou dados estruturados automatizáveis.',
            'data' => null,
            'manual_url' => $this->publicUrls['1'],
            'attempts' => array_slice($attempts, -12)
        ];
    }

    private function submitPublicSearch(string $url, array $criteria, array &$attempts): array
    {
        $this->resetCookieJar();

        $html = $this->request('GET', $url, [], [], $attempts);
        if ($html === false) {
            return ['success' => false, 'data' => null, 'message' => 'Falha ao abrir Consulta Unificada pública.'];
        }

        if ($criteria['type'] === 'number') {
            $parsed = $this->parseProcessPage($html, $criteria['numero_formatado'], $criteria['numero_limpo']);
            if ($parsed !== null) {
                return ['success' => true, 'data' => $parsed];
            }
        } else {
            $rows = $this->parseProcessList($html);
            if (!empty($rows)) {
                return ['success' => true, 'data' => $rows];
            }
        }

        $forms = $this->extractForms($html, $url);
        if (empty($forms)) {
            $forms[] = ['method' => 'POST', 'action' => $url, 'fields' => []];
        }

        foreach ($forms as $form) {
            $payloads = $criteria['type'] === 'number'
                ? $this->buildNumberPayloads($form, $criteria['numero_formatado'], $criteria['numero_limpo'], (string)$criteria['instancia'])
                : $this->buildOabPayloads($form, $criteria['oab'], $criteria['uf'], (string)$criteria['instancia']);

            foreach ($payloads as $payload) {
                $headers = [
                    'Referer: ' . $url,
                    'Origin: ' . $this->originFromUrl($url),
                    'Content-Type: application/x-www-form-urlencoded',
                ];
                $resp = $this->request($form['method'], $form['action'], $payload, $headers, $attempts);
                if ($resp === false) {
                    continue;
                }

                if ($criteria['type'] === 'number') {
                    $parsed = $this->parseProcessPage($resp, $criteria['numero_formatado'], $criteria['numero_limpo']);
                    if ($parsed !== null) {
                        return ['success' => true, 'data' => $parsed];
                    }

                    $rows = $this->parseProcessList($resp);
                    foreach ($rows as $row) {
                        if (preg_replace('/\D/', '', $row['numero_cnj']) === $criteria['numero_limpo']) {
                            $row['cnj_raw_data'] = json_encode(['fonte' => 'eproc_tjsp', 'html_resumo' => substr($this->plainText($resp), 0, 5000)]);
                            return ['success' => true, 'data' => $row];
                        }
                    }
                } else {
                    $rows = $this->parseProcessList($resp);
                    if (!empty($rows)) {
                        return ['success' => true, 'data' => $rows];
                    }
                }

                if ($this->hasCaptchaOrBlocked($resp)) {
                    return ['success' => false, 'data' => null, 'message' => 'Consulta pública retornou captcha, token expirado ou bloqueio.'];
                }
            }
        }

        return ['success' => false, 'data' => null, 'message' => 'Consulta pública não retornou resultado automatizável.'];
    }

    private function buildNumberPayloads(array $form, string $numeroFormatado, string $numeroLimpo, string $instancia): array
    {
        $baseFields = $form['fields'];
        $candidates = [];

        $smart = $baseFields;
        foreach (array_keys($smart) as $name) {
            $low = strtolower($name);
            if (strpos($low, 'process') !== false || strpos($low, 'num') !== false || strpos($low, 'valor') !== false || strpos($low, 'pesquisa') !== false || strpos($low, 'chave') !== false) {
                if (strpos($low, 'captcha') === false && strpos($low, 'token') === false && strpos($low, 'hash') === false) {
                    $smart[$name] = $numeroFormatado;
                }
            }
            if (strpos($low, 'instancia') !== false || strpos($low, 'grau') !== false) {
                $smart[$name] = $instancia;
            }
        }
        $candidates[] = $smart;

        $specificSets = [
            ['num_processo' => $numeroFormatado, 'numProcesso' => $numeroFormatado, 'numeroProcesso' => $numeroFormatado],
            ['txtNumProcesso' => $numeroFormatado, 'txtNumeroProcesso' => $numeroFormatado],
            ['txtValor' => $numeroFormatado, 'tipoPesquisa' => 'PROCESSO'],
            ['txtValor' => $numeroLimpo, 'tipoPesquisa' => 'PROCESSO'],
            ['valor' => $numeroFormatado, 'tipoConsulta' => 'processo'],
            ['termo' => $numeroFormatado, 'tipo' => 'processo'],
            ['chave' => $numeroFormatado, 'tipoPesquisa' => 'processo'],
            ['acao' => 'tjsp@consulta_unificada_publica/consultar', 'num_processo' => $numeroFormatado],
        ];

        foreach ($specificSets as $fields) {
            $payload = array_merge($baseFields, $fields);
            $payload = $this->forceSelectValues($payload, $form['selects'] ?? [], $instancia);
            $candidates[] = $payload;
        }

        return $this->uniquePayloads($candidates);
    }

    private function buildOabPayloads(array $form, string $oab, string $uf, string $instancia): array
    {
        $baseFields = $form['fields'];
        $candidates = [];

        $smart = $baseFields;
        foreach (array_keys($smart) as $name) {
            $low = strtolower($name);
            if (strpos($low, 'oab') !== false) {
                $smart[$name] = $oab;
            }
            if (strpos($low, 'uf') !== false || strpos($low, 'estado') !== false) {
                $smart[$name] = $uf;
            }
            if (strpos($low, 'valor') !== false || strpos($low, 'pesquisa') !== false || strpos($low, 'termo') !== false) {
                $smart[$name] = $oab . $uf;
            }
            if (strpos($low, 'instancia') !== false || strpos($low, 'grau') !== false) {
                $smart[$name] = $instancia;
            }
        }
        $candidates[] = $this->forceSelectValues($smart, $form['selects'] ?? [], $instancia);

        $specificSets = [
            ['txtOab' => $oab, 'txtUfOab' => $uf],
            ['numOab' => $oab, 'ufOab' => $uf],
            ['numeroOab' => $oab, 'estadoOab' => $uf],
            ['txtValor' => $oab . $uf, 'tipoPesquisa' => 'OAB'],
            ['valor' => $oab . $uf, 'tipoConsulta' => 'oab'],
            ['termo' => $oab . $uf, 'tipo' => 'oab'],
        ];

        foreach ($specificSets as $fields) {
            $payload = array_merge($baseFields, $fields);
            $payload = $this->forceSelectValues($payload, $form['selects'] ?? [], $instancia);
            $candidates[] = $payload;
        }

        return $this->uniquePayloads($candidates);
    }

    private function forceSelectValues(array $payload, $selects, string $instancia): array
    {
        if (!is_array($selects)) {
            $selects = [];
        }
        foreach ($selects as $name => $options) {
            $low = strtolower($name);
            if (strpos($low, 'instancia') !== false || strpos($low, 'grau') !== false) {
                $payload[$name] = $this->pickOption($options, $instancia);
            }
            if ((strpos($low, 'tipo') !== false || strpos($low, 'pesquisa') !== false) && empty($payload[$name])) {
                $payload[$name] = $this->pickOption($options, 'processo');
            }
        }
        return $payload;
    }

    private function pickOption(array $options, string $wanted): string
    {
        $wanted = strtolower($wanted);
        foreach ($options as $value => $label) {
            $hay = strtolower($value . ' ' . $label);
            if ($wanted === '1' && (strpos($hay, '1') !== false || strpos($hay, 'primeiro') !== false || strpos($hay, '1º') !== false)) {
                return $value;
            }
            if ($wanted === '2' && (strpos($hay, '2') !== false || strpos($hay, 'segundo') !== false || strpos($hay, '2º') !== false)) {
                return $value;
            }
            if ($wanted !== '1' && $wanted !== '2' && strpos($hay, $wanted) !== false) {
                return $value;
            }
        }
        foreach ($options as $value => $label) {
            if ((string)$value !== '') {
                return (string)$value;
            }
        }
        return '';
    }

    private function extractForms(string $html, string $baseUrl): array
    {
        $forms = [];
        if (!preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $html, $matches, PREG_SET_ORDER)) {
            return $forms;
        }

        foreach ($matches as $m) {
            $formTag = $m[0];
            $inside = $m[1];
            $method = 'POST';
            if (preg_match('/method=["\']?([^"\'\s>]+)/i', $formTag, $mm)) {
                $method = strtoupper($mm[1]);
            }
            $action = $baseUrl;
            if (preg_match('/action=["\']([^"\']*)/i', $formTag, $am) && trim($am[1]) !== '') {
                $action = $this->absoluteUrl($baseUrl, html_entity_decode($am[1], ENT_QUOTES, 'UTF-8'));
            }

            $fields = [];
            $selects = [];

            if (preg_match_all('/<input\b[^>]*>/is', $inside, $inputs, PREG_SET_ORDER)) {
                foreach ($inputs as $input) {
                    $tag = $input[0];
                    if (!preg_match('/name=["\']([^"\']+)/i', $tag, $nm)) {
                        continue;
                    }
                    $name = html_entity_decode($nm[1], ENT_QUOTES, 'UTF-8');
                    $type = '';
                    if (preg_match('/type=["\']?([^"\'\s>]+)/i', $tag, $tm)) {
                        $type = strtolower($tm[1]);
                    }
                    if (in_array($type, ['button', 'image', 'file'], true)) {
                        continue;
                    }
                    $value = '';
                    if (preg_match('/value=["\']([^"\']*)/i', $tag, $vm)) {
                        $value = html_entity_decode($vm[1], ENT_QUOTES, 'UTF-8');
                    }
                    $fields[$name] = $value;
                }
            }

            if (preg_match_all('/<textarea\b[^>]*name=["\']([^"\']+)["\'][^>]*>(.*?)<\/textarea>/is', $inside, $texts, PREG_SET_ORDER)) {
                foreach ($texts as $t) {
                    $fields[html_entity_decode($t[1], ENT_QUOTES, 'UTF-8')] = html_entity_decode(strip_tags($t[2]), ENT_QUOTES, 'UTF-8');
                }
            }

            if (preg_match_all('/<select\b[^>]*name=["\']([^"\']+)["\'][^>]*>(.*?)<\/select>/is', $inside, $sels, PREG_SET_ORDER)) {
                foreach ($sels as $s) {
                    $name = html_entity_decode($s[1], ENT_QUOTES, 'UTF-8');
                    $options = [];
                    if (preg_match_all('/<option\b([^>]*)>(.*?)<\/option>/is', $s[2], $opts, PREG_SET_ORDER)) {
                        foreach ($opts as $opt) {
                            $value = '';
                            if (preg_match('/value=["\']([^"\']*)/i', $opt[1], $vm)) {
                                $value = html_entity_decode($vm[1], ENT_QUOTES, 'UTF-8');
                            } else {
                                $value = trim(html_entity_decode(strip_tags($opt[2]), ENT_QUOTES, 'UTF-8'));
                            }
                            $label = trim(html_entity_decode(strip_tags($opt[2]), ENT_QUOTES, 'UTF-8'));
                            $options[$value] = $label;
                            if (strpos(strtolower($opt[1]), 'selected') !== false) {
                                $fields[$name] = $value;
                            }
                        }
                    }
                    if (!isset($fields[$name])) {
                        $fields[$name] = count($options) ? (string)array_key_first($options) : '';
                    }
                    $selects[$name] = $options;
                }
            }

            $forms[] = ['method' => $method, 'action' => $action, 'fields' => $fields, 'selects' => $selects];
        }
        return $forms;
    }

    private function parseProcessList(string $html): array
    {
        preg_match_all('/\d{7}-\d{2}\.\d{4}\.8\.26\.\d{4}/', $html, $m);
        $numbers = array_values(array_unique($m[0] ?? []));
        $data = [];
        foreach ($numbers as $n) {
            $data[] = [
                'numero_cnj' => $n,
                'tribunal' => 'tjsp',
                'sistema' => 'eproc',
                'fonte_importacao' => 'eproc_tjsp',
                'status' => 'ativo',
                'last_sync_at' => date('Y-m-d H:i:s'),
                'cnj_raw_data' => json_encode(['fonte' => 'eproc_tjsp', 'numero' => $n])
            ];
        }
        return $data;
    }

    private function parseProcessPage(string $html, string $numeroFormatado, string $numeroLimpo): ?array
    {
        $plain = $this->plainText($html);
        $digits = preg_replace('/\D/', '', $plain);
        if (strpos($digits, $numeroLimpo) === false && strpos($html, $numeroFormatado) === false) {
            return null;
        }

        $plainCompact = preg_replace('/\s+/', ' ', $plain);
        $data = [
            'numero_cnj' => $numeroFormatado,
            'tribunal' => 'tjsp',
            'sistema' => 'eproc',
            'fonte_importacao' => 'eproc_tjsp',
            'status' => 'ativo',
            'last_sync_at' => date('Y-m-d H:i:s'),
            'cnj_raw_data' => json_encode(['fonte' => 'eproc_tjsp', 'html_resumo' => substr($plainCompact, 0, 8000)])
        ];

        $patterns = [
            'classe' => '/(?:Classe|Classe Judicial)\s*[:\-]?\s*([^\n\r|]{3,140})/iu',
            'area' => '/(?:Área|Competência|Competencia)\s*[:\-]?\s*([^\n\r|]{3,120})/iu',
            'assunto' => '/Assunto\s*[:\-]?\s*([^\n\r|]{3,200})/iu',
            'comarca' => '/(?:Comarca|Foro)\s*[:\-]?\s*([^\n\r|]{3,140})/iu',
            'vara' => '/(?:Vara|Órgão julgador|Orgao julgador|Juízo|Juizo)\s*[:\-]?\s*([^\n\r|]{3,180})/iu',
            'valor_causa' => '/Valor\s+(?:da\s+)?causa\s*[:\-]?\s*R?\$?\s*([0-9\.]+,\d{2})/iu',
            'fase_processual' => '/(?:Situação|Situacao|Status|Fase)\s*[:\-]?\s*([^\n\r|]{3,120})/iu',
        ];

        foreach ($patterns as $key => $regex) {
            if (preg_match($regex, $plainCompact, $mm)) {
                $value = trim($mm[1]);
                $value = preg_replace('/\s{2,}/', ' ', $value);
                $value = trim($value, " \t\n\r\0\x0B:-|");
                if ($key === 'valor_causa') {
                    $value = str_replace('.', '', $value);
                    $value = str_replace(',', '.', $value);
                }
                $data[$key] = $value;
            }
        }

        $movimentos = $this->parseMovements($html, $plainCompact);
        if (!empty($movimentos)) {
            $data['movimentos'] = $movimentos;
        }

        return $data;
    }

    private function parseMovements(string $html, string $plainCompact): array
    {
        $movs = [];
        $seen = [];

        // 1) Preferir linhas/tabelas do HTML, pois nelas é possível capturar links de documentos/eventos.
        if (preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $html, $rows, PREG_SET_ORDER)) {
            foreach ($rows as $row) {
                $rowHtml = $row[1];
                $text = $this->cleanText($rowHtml);
                if (!preg_match('/(\d{2}\/\d{2}\/\d{4})(?:\s+(\d{2}:\d{2}(?::\d{2})?))?/u', $text, $dm)) {
                    continue;
                }

                $desc = trim(preg_replace('/\s+/', ' ', $text));
                $desc = preg_replace('/^.*?' . preg_quote($dm[1], '/') . '(?:\s+' . preg_quote($dm[2] ?? '', '/') . ')?\s*/u', '', $desc);
                $desc = trim($desc);
                if ($desc === '' || mb_strlen($desc, 'UTF-8') < 4) {
                    continue;
                }

                $eventoNumero = null;
                if (preg_match('/\b(?:Evento|Ev\.?|Mov\.?|Seq\.?|#)\s*[:\-]?\s*(\d{1,8})\b/iu', $text, $em)) {
                    $eventoNumero = $em[1];
                } elseif (preg_match('/^\s*(\d{1,8})\s+\d{2}\/\d{2}\/\d{4}/u', $text, $em)) {
                    $eventoNumero = $em[1];
                }

                $docUrl = null;
                $docTipo = null;
                if (preg_match('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $rowHtml, $am)) {
                    $docUrl = $this->absoluteUrl($this->publicUrls['1'], html_entity_decode($am[1], ENT_QUOTES, 'UTF-8'));
                    $docTipo = trim($this->cleanText($am[2]));
                    if ($docTipo === '') {
                        $docTipo = 'Documento';
                    }
                }

                $dt = $this->normalizeBrazilDate($dm[1], $dm[2] ?? null);
                $tipo = $this->detectMovementType($desc);
                $key = md5($dt . '|' . $eventoNumero . '|' . $desc . '|' . $docUrl);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $movs[] = [
                    'data_movimento' => $dt,
                    'evento_numero' => $eventoNumero,
                    'descricao' => $desc,
                    'fonte' => 'eproc_tjsp',
                    'tipo' => $tipo,
                    'documento_url' => $docUrl,
                    'documento_tipo' => $docTipo,
                    'usuario_origem' => $this->guessOriginUser($desc),
                    'conteudo' => null,
                ];
            }
        }

        // 2) Fallback textual: captura TODAS as ocorrências por data, sem limitar a 20.
        $text = preg_replace('/\s+/', ' ', $plainCompact);
        $regex = '/(\d{2}\/\d{2}\/\d{4})(?:\s+(\d{2}:\d{2}(?::\d{2})?))?\s+(.+?)(?=\s+\d{2}\/\d{2}\/\d{4}(?:\s+\d{2}:\d{2})?|$)/u';
        if (preg_match_all($regex, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $desc = trim(preg_replace('/\s+/', ' ', $m[3]));
                $desc = trim($desc, " \t\n\r\0\x0B:-|");
                if ($desc === '' || mb_strlen($desc, 'UTF-8') < 4) {
                    continue;
                }
                // Evita salvar cabeçalhos, menus e textos genéricos como movimentação.
                if (preg_match('/^(processo|consulta|menu|voltar|imprimir|dados do processo|partes|advogados?)\b/iu', $desc) && mb_strlen($desc, 'UTF-8') < 80) {
                    continue;
                }

                $dt = $this->normalizeBrazilDate($m[1], $m[2] ?? null);
                $tipo = $this->detectMovementType($desc);
                $key = md5($dt . '||' . $desc);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $movs[] = [
                    'data_movimento' => $dt,
                    'evento_numero' => null,
                    'descricao' => $desc,
                    'fonte' => 'eproc_tjsp',
                    'tipo' => $tipo,
                    'documento_url' => null,
                    'documento_tipo' => null,
                    'usuario_origem' => $this->guessOriginUser($desc),
                    'conteudo' => null,
                ];
            }
        }

        usort($movs, function ($a, $b) {
            return strcmp((string)$b['data_movimento'], (string)$a['data_movimento']);
        });

        return $movs;
    }

    private function normalizeBrazilDate(string $date, ?string $time = null): string
    {
        $time = $time ?: '00:00:00';
        if (strlen($time) === 5) {
            $time .= ':00';
        }
        $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $date . ' ' . $time);
        if (!$dt) {
            $dt = \DateTime::createFromFormat('d/m/Y', $date);
        }
        return $dt ? $dt->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    }

    private function cleanText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html);
        $html = preg_replace('/<br\s*\/?>/i', ' ', $html);
        $html = preg_replace('/<\/t[dh]>/i', ' ', $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function detectMovementType(string $descricao): string
    {
        $d = mb_strtolower($descricao, 'UTF-8');
        if (strpos($d, 'despacho') !== false) return 'despacho';
        if (strpos($d, 'decisão') !== false || strpos($d, 'decisao') !== false) return 'decisao';
        if (strpos($d, 'sentença') !== false || strpos($d, 'sentenca') !== false) return 'sentenca';
        if (strpos($d, 'acórdão') !== false || strpos($d, 'acordao') !== false) return 'acordao';
        if (strpos($d, 'petição') !== false || strpos($d, 'peticao') !== false || strpos($d, 'juntada') !== false) return 'peticionamento';
        if (strpos($d, 'certidão') !== false || strpos($d, 'certidao') !== false) return 'certidao';
        if (strpos($d, 'audiência') !== false || strpos($d, 'audiencia') !== false) return 'audiencia';
        if (strpos($d, 'publica') !== false || strpos($d, 'disponibiliza') !== false) return 'publicacao';
        if (strpos($d, 'intima') !== false) return 'intimacao';
        return 'movimentacao';
    }

    private function guessOriginUser(string $descricao): ?string
    {
        if (preg_match('/(?:por|usu[aá]rio|origem|respons[aá]vel)\s*[:\-]?\s*([A-ZÁ-Ú][A-Za-zÁ-ú0-9 ._\-]{3,80})/u', $descricao, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function hasCaptchaOrBlocked(string $html): bool
    {
        $text = strtolower($html);
        return strpos($text, 'captcha') !== false
            || strpos($text, 'recaptcha') !== false
            || strpos($text, 'código de segurança') !== false
            || strpos($text, 'codigo de seguranca') !== false
            || strpos($text, 'sessão expirou') !== false
            || strpos($text, 'sessao expirou') !== false
            || strpos($text, 'csrf') !== false;
    }

    private function formatCnj(string $numero): string
    {
        $n = preg_replace('/\D/', '', $numero);
        if (strlen($n) !== 20) {
            return $numero;
        }
        return substr($n, 0, 7) . '-' . substr($n, 7, 2) . '.' . substr($n, 9, 4) . '.' . substr($n, 13, 1) . '.' . substr($n, 14, 2) . '.' . substr($n, 16, 4);
    }

    private function request(string $method, string $url, array $data, array $headers, array &$attempts)
    {
        $method = strtoupper($method) === 'GET' ? 'GET' : 'POST';
        $fullUrl = $url;
        if ($method === 'GET' && !empty($data)) {
            $fullUrl .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }

        $ch = curl_init($fullUrl);
        $defaultHeaders = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36',
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $attempts[] = [
            'method' => $method,
            'url' => $fullUrl,
            'effective_url' => $effective,
            'http_code' => $code,
            'error' => $err,
            'posted_fields' => $method === 'POST' ? array_keys($data) : [],
        ];

        if ($err || $body === false || $code >= 400 || $code === 0) {
            Logger::warning('EprocTJSP request failed', ['url' => $fullUrl, 'code' => $code, 'error' => $err]);
            return false;
        }
        return (string)$body;
    }

    private function absoluteUrl(string $base, string $url): string
    {
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }
        $parts = parse_url($base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $url;
        }
        $path = $parts['path'] ?? '/';
        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $scheme . '://' . $host . $dir . '/' . ltrim($url, '/');
    }

    private function originFromUrl(string $url): string
    {
        $p = parse_url($url);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '');
    }

    private function plainText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html);
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    }

    private function resetCookieJar(): void
    {
        if (is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
        @file_put_contents($this->cookieFile, '');
    }

    private function uniquePayloads(array $payloads): array
    {
        $seen = [];
        $out = [];
        foreach ($payloads as $p) {
            ksort($p);
            $key = md5(json_encode($p));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $p;
            }
        }
        return $out;
    }
}
