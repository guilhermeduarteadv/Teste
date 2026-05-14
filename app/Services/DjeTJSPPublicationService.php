<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use Core\Logger;
use PDO;

class DjeTJSPPublicationService
{
    private $baseUrl = 'https://dje.tjsp.jus.br/cdje';
    private $timeout = 25;

    public function searchAndImportByOab(string $oabNumber, string $oabUf, string $dateStart, string $dateEnd, string $queryExtra = ''): array
    {
        $oabNumber = preg_replace('/\D/', '', $oabNumber);
        $oabUf = strtoupper(trim($oabUf));
        if ($oabNumber === '' || $oabUf === '') {
            return ['success' => false, 'message' => 'Número da OAB e UF são obrigatórios.', 'imported' => 0, 'updated' => 0, 'found' => 0, 'errors' => []];
        }
        if (!$this->validDate($dateStart) || !$this->validDate($dateEnd)) {
            return ['success' => false, 'message' => 'Período inválido.', 'imported' => 0, 'updated' => 0, 'found' => 0, 'errors' => []];
        }

        $queries = $this->buildOabQueries($oabNumber, $oabUf, $queryExtra);
        $all = [];
        $errors = [];
        $attempts = [];

        // Terms to check that the publication actually mentions this OAB/attorney
        $oabTerms = [
            $oabNumber,
            strtoupper($oabUf) . $oabNumber,
            strtoupper($oabUf) . ' ' . $oabNumber,
            'OAB/' . strtoupper($oabUf) . ' ' . $oabNumber,
            'OAB ' . strtoupper($oabUf) . ' ' . $oabNumber,
        ];
        if ($queryExtra !== '') {
            $oabTerms[] = $queryExtra;
        }

        foreach ($queries as $q) {
            $result = $this->searchRemote($q, $dateStart, $dateEnd);
            $attempts[] = $result['attempt'] ?? [];
            if (!$result['success']) {
                $errors[] = $result['message'];
                continue;
            }
            foreach ($result['publications'] as $pub) {
                $texto = strtolower((string)($pub['texto'] ?? ''));
                // Skip publications that don't mention the OAB in their text
                $relevant = false;
                foreach ($oabTerms as $term) {
                    if (stripos($texto, $term) !== false) {
                        $relevant = true;
                        break;
                    }
                }
                if (!$relevant) {
                    continue;
                }
                $key = md5(($pub['numero_cnj'] ?? '') . '|' . ($pub['data_publicacao'] ?? '') . '|' . ($pub['texto'] ?? ''));
                $all[$key] = $pub;
            }
        }

        $stats = $this->storePublications(array_values($all), 'tjsp', 'DJE-TJSP', 'dje_tjsp');
        $stats['found'] = count($all);
        $stats['errors'] = array_values(array_unique(array_filter($errors)));
        $stats['attempts'] = $attempts;
        $stats['success'] = true;
        if ($stats['found'] === 0) {
            $stats['message'] = 'Nenhuma publicação localizada no DJE/TJSP no período informado. Tente ampliar o período ou pesquisar por nome/OAB completa.';
        } else {
            $stats['message'] = 'Leitura do DJE/TJSP concluída: ' . $stats['imported'] . ' novas, ' . $stats['updated'] . ' já existentes/atualizadas.';
        }
        return $stats;
    }

    public function importFromText(string $text, string $dataPublicacao, string $sourceName = 'texto_colado'): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['success' => false, 'message' => 'Texto do diário/publicação é obrigatório.', 'imported' => 0, 'updated' => 0, 'found' => 0];
        }
        if (!$this->validDate($dataPublicacao)) {
            $dataPublicacao = date('Y-m-d');
        }
        $publications = $this->extractPublicationsFromText($text, $dataPublicacao, 'Publicação importada por leitura de diário', null);
        $stats = $this->storePublications($publications, 'tjsp', 'DJE/TEXTO', $sourceName);
        $stats['found'] = count($publications);
        $stats['success'] = true;
        $stats['message'] = 'Leitura do texto concluída: ' . $stats['imported'] . ' novas, ' . $stats['updated'] . ' já existentes/atualizadas.';
        return $stats;
    }

    private function buildOabQueries(string $oabNumber, string $uf, string $extra): array
    {
        $queries = [
            $oabNumber,
            'OAB ' . $uf . ' ' . $oabNumber,
            'OAB/' . $uf . ' ' . $oabNumber,
            $oabNumber . '/' . $uf,
            $uf . $oabNumber,
        ];
        $extra = trim($extra);
        if ($extra !== '') {
            array_unshift($queries, $extra);
        }
        return array_values(array_unique($queries));
    }

    private function searchRemote(string $query, string $dateStart, string $dateEnd): array
    {
        $formats = [
            'dadosConsulta.pesquisaLivre' => $query,
            'dadosConsulta.dtInicio' => $this->brDate($dateStart),
            'dadosConsulta.dtFim' => $this->brDate($dateEnd),
            'dadosConsulta.cdCaderno' => '-1',
        ];
        $urls = [
            $this->baseUrl . '/consultaAvancada.do?' . http_build_query($formats),
            $this->baseUrl . '/consultaSimples.do?' . http_build_query(['dadosConsulta.pesquisaLivre' => $query, 'data' => $this->brDate($dateEnd)]),
        ];

        $last = null;
        foreach ($urls as $url) {
            $resp = $this->httpGet($url);
            $last = $resp;
            if (!$resp['ok']) {
                continue;
            }
            $html = (string)$resp['body'];
            $clean = $this->htmlToText($html);
            if (stripos($clean, 'pesquisa') === false && stripos($clean, 'advogado') === false && stripos($clean, $query) === false) {
                // Ainda pode ser uma tela válida sem resultado, mas tentaremos o próximo formato.
            }
            $publications = $this->extractPublicationsFromText($clean, $dateEnd, 'Publicação DJE/TJSP', $resp['effective_url'] ?: $url);
            if (!empty($publications) || $resp['http_code'] === 200) {
                return ['success' => true, 'publications' => $publications, 'attempt' => ['query' => $query, 'url' => $url, 'http_code' => $resp['http_code'], 'effective_url' => $resp['effective_url']]];
            }
        }

        return [
            'success' => false,
            'message' => 'Não foi possível consultar o DJE/TJSP para o termo: ' . $query,
            'publications' => [],
            'attempt' => ['query' => $query, 'last' => $last],
        ];
    }

    private function extractPublicationsFromText(string $text, string $defaultDate, string $defaultTitle, ?string $sourceUrl): array
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = trim($text);
        if ($text === '') return [];

        preg_match_all('/\d{7}-\d{2}\.\d{4}\.\d\.\d{2}\.\d{4}/', $text, $matches, PREG_OFFSET_CAPTURE);
        if (empty($matches[0])) {
            // Sem CNJ: salvar como publicação geral apenas se texto tiver algum conteúdo jurídico relevante.
            if (mb_strlen($text) < 30) return [];
            return [[
                'numero_cnj' => null,
                'data_publicacao' => $defaultDate,
                'titulo' => $defaultTitle,
                'texto' => mb_substr($text, 0, 5000),
                'source_url' => $sourceUrl,
            ]];
        }

        $publications = [];
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $numero = $matches[0][$i][0];
            $pos = $matches[0][$i][1];
            $next = ($i + 1 < $count) ? $matches[0][$i + 1][1] : min(strlen($text), $pos + 4000);
            $start = max(0, $pos - 300);
            $len = min(5000, $next - $start);
            $snippet = trim(substr($text, $start, $len));
            $date = $this->extractDate($snippet) ?: $defaultDate;
            $title = $this->extractTitle($snippet) ?: ('Publicação - ' . $numero);
            $publications[] = [
                'numero_cnj' => $numero,
                'data_publicacao' => $date,
                'titulo' => $title,
                'texto' => $snippet,
                'source_url' => $sourceUrl,
            ];
        }
        return $publications;
    }

    private function storePublications(array $publications, string $tribunal, string $diario, string $fonte): array
    {
        $db = Database::getInstance();
        $imported = 0; $updated = 0; $linked = 0; $movements = 0;
        foreach ($publications as $pub) {
            $numero = $pub['numero_cnj'] ?? null;
            $data = $pub['data_publicacao'] ?? date('Y-m-d');
            $titulo = $pub['titulo'] ?? 'Publicação';
            $texto = $pub['texto'] ?? '';
            if (trim($texto) === '') continue;
            $caseId = $this->findCaseId($numero);
            if ($caseId) $linked++;
            $hash = md5(($numero ?? '') . '|' . $data . '|' . $titulo . '|' . $texto);

            $exists = null;
            $st = $db->prepare('SELECT id FROM publications WHERE hash = ? LIMIT 1');
            $st->execute([$hash]);
            $exists = $st->fetchColumn();

            if ($exists) {
                $up = $db->prepare('UPDATE publications SET case_id = COALESCE(case_id, ?), updated_at = NOW() WHERE id = ?');
                $up->execute([$caseId, $exists]);
                $updated++;
            } else {
                $ins = $db->prepare('INSERT INTO publications (case_id, numero_cnj, tribunal, diario, data_publicacao, titulo, texto, fonte, hash, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
                $ins->execute([$caseId, $numero, $tribunal, $diario, $data, $titulo, $texto, $fonte, $hash]);
                $imported++;
            }

            if ($caseId) {
                if ($this->insertMovement($caseId, $data, $titulo, $texto, $hash)) {
                    $movements++;
                }
            }
        }
        return ['imported' => $imported, 'updated' => $updated, 'linked' => $linked, 'movements' => $movements];
    }

    private function findCaseId(?string $numero): ?int
    {
        if (!$numero) return null;
        $db = Database::getInstance();
        $st = $db->prepare('SELECT id FROM cases WHERE (numero_cnj = ? OR REPLACE(REPLACE(REPLACE(numero_cnj, ".", ""), "-", ""), "/", "") = ?) AND deleted_at IS NULL LIMIT 1');
        $st->execute([$numero, preg_replace('/\D/', '', $numero)]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function insertMovement(int $caseId, string $date, string $title, string $text, string $hash): bool
    {
        $db = Database::getInstance();
        $movementHash = md5('pub|' . $caseId . '|' . $hash);
        $st = $db->prepare('SELECT id FROM case_movements WHERE hash = ? LIMIT 1');
        $st->execute([$movementHash]);
        if ($st->fetchColumn()) return false;
        $desc = trim($title . "\n" . $text);
        $ins = $db->prepare("INSERT INTO case_movements (case_id, data_movimento, tipo, descricao, fonte, hash, created_at) VALUES (?, ?, 'publicacao', ?, 'dje_tjsp', ?, NOW())");
        $ins->execute([$caseId, $date . ' 00:00:00', $desc, $movementHash]);
        return true;
    }

    private function httpGet(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) JurisControl/1.0',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'],
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $eff = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        if ($err || $body === false) {
            Logger::warning('DJE TJSP HTTP error: ' . $err, ['url' => $url]);
            return ['ok' => false, 'body' => '', 'error' => $err, 'http_code' => $code, 'effective_url' => $eff];
        }
        return ['ok' => $code >= 200 && $code < 400, 'body' => (string)$body, 'error' => '', 'http_code' => $code, 'effective_url' => $eff];
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', ' ', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', ' ', $html);
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = preg_replace('#</(p|div|tr|li|td|th|h\d)>#i', "\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    private function extractDate(string $text): ?string
    {
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $text, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $text, $m)) return $m[0];
        return null;
    }

    private function extractTitle(string $text): ?string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        foreach ($lines as $line) {
            if (mb_strlen($line) >= 8 && mb_strlen($line) <= 220) return $line;
        }
        return null;
    }

    private function validDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function brDate(string $date): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : date('d/m/Y');
    }
}
