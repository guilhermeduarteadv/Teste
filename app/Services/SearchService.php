<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;
use PDO;

class SearchService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function search(string $query, int $userId): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $results = [];

        $results = array_merge($results, $this->searchClients($query));
        $results = array_merge($results, $this->searchCases($query));
        $results = array_merge($results, $this->searchTasks($query));
        $results = array_merge($results, $this->searchDocuments($query));
        $results = array_merge($results, $this->searchFinancial($query));
        $results = array_merge($results, $this->searchPublications($query));
        $results = array_merge($results, $this->searchConsultancies($query));
        $results = array_merge($results, $this->searchAdministrativeProcedures($query));

        $this->logSearch($userId, $query, count($results));

        return $results;
    }

    public function suggest(string $query): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $all = [];
        $all = array_merge($all, $this->searchClients($query, 3));
        $all = array_merge($all, $this->searchCases($query, 3));
        $all = array_merge($all, $this->searchTasks($query, 2));

        return array_slice($all, 0, 5);
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function searchClients(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('clients')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, email, phone FROM clients
                 WHERE deleted_at IS NULL
                   AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR cpf LIKE ? OR cnpj LIKE ?)
                 ORDER BY name ASC LIMIT " . (int)$limit
            );
            $stmt->execute([$like, $like, $like, $like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'type'     => 'clients',
                    'type_label' => 'Clientes',
                    'id'       => $r['id'],
                    'title'    => $r['name'],
                    'subtitle' => $r['email'] ?: ($r['phone'] ?: ''),
                    'link'     => '/clients/' . $r['id'],
                    'icon'     => 'fas fa-user',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchCases(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('cases')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, numero_cnj, titulo, area FROM cases
                 WHERE deleted_at IS NULL
                   AND (numero_cnj LIKE ? OR titulo LIKE ? OR area LIKE ? OR parte_contraria LIKE ?)
                 ORDER BY created_at DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like, $like, $like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $title = $r['titulo'] ?: $r['numero_cnj'] ?: 'Processo #' . $r['id'];
                $out[] = [
                    'type'       => 'cases',
                    'type_label' => 'Processos',
                    'id'         => $r['id'],
                    'title'      => $title,
                    'subtitle'   => $r['numero_cnj'] ?: ($r['area'] ?: ''),
                    'link'       => '/cases/' . $r['id'],
                    'icon'       => 'fas fa-gavel',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchTasks(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('tasks')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $titleCol = $this->firstExistingColumn('tasks', ['title', 'titulo', 'descricao']);
            if ($titleCol === null) {
                return [];
            }
            $stmt = $this->db->prepare(
                "SELECT id, `{$titleCol}` AS title, status FROM tasks
                 WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                   AND `{$titleCol}` LIKE ?
                 ORDER BY id DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'type'       => 'tasks',
                    'type_label' => 'Tarefas',
                    'id'         => $r['id'],
                    'title'      => $r['title'] ?: 'Tarefa #' . $r['id'],
                    'subtitle'   => $r['status'] ?: '',
                    'link'       => '/tasks',
                    'icon'       => 'fas fa-tasks',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchDocuments(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('documents')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, titulo, nome_arquivo, descricao FROM documents
                 WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                   AND (titulo LIKE ? OR nome_arquivo LIKE ? OR descricao LIKE ?)
                 ORDER BY id DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like, $like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $title = $r['titulo'] ?: $r['nome_arquivo'] ?: 'Documento #' . $r['id'];
                $out[] = [
                    'type'       => 'documents',
                    'type_label' => 'Documentos',
                    'id'         => $r['id'],
                    'title'      => $title,
                    'subtitle'   => $r['descricao'] ? mb_substr($r['descricao'], 0, 80) : '',
                    'link'       => '/documents',
                    'icon'       => 'fas fa-file-alt',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchFinancial(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('financial_entries')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, descricao, valor, status FROM financial_entries
                 WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                   AND descricao LIKE ?
                 ORDER BY id DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'type'       => 'financial',
                    'type_label' => 'Financeiro',
                    'id'         => $r['id'],
                    'title'      => $r['descricao'] ?: 'Lançamento #' . $r['id'],
                    'subtitle'   => 'R$ ' . number_format((float)($r['valor'] ?? 0), 2, ',', '.') . ' — ' . ($r['status'] ?: ''),
                    'link'       => '/financial',
                    'icon'       => 'fas fa-dollar-sign',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchPublications(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('publications')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, titulo, conteudo FROM publications
                 WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                   AND (titulo LIKE ? OR conteudo LIKE ?)
                 ORDER BY id DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'type'       => 'publications',
                    'type_label' => 'Publicações',
                    'id'         => $r['id'],
                    'title'      => $r['titulo'] ?: 'Publicação #' . $r['id'],
                    'subtitle'   => $r['conteudo'] ? mb_substr(strip_tags($r['conteudo']), 0, 80) : '',
                    'link'       => '/publications',
                    'icon'       => 'fas fa-newspaper',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchConsultancies(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('legal_consultancies')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, titulo, area, status FROM legal_consultancies
                 WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                   AND (titulo LIKE ? OR area LIKE ? OR descricao LIKE ?)
                 ORDER BY id DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like, $like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'type'       => 'consultancies',
                    'type_label' => 'Consultorias',
                    'id'         => $r['id'],
                    'title'      => $r['titulo'],
                    'subtitle'   => ($r['area'] ?: '') . ($r['status'] ? ' — ' . $r['status'] : ''),
                    'link'       => '/consultancies/' . $r['id'],
                    'icon'       => 'fas fa-comments',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function searchAdministrativeProcedures(string $query, int $limit = 20): array
    {
        if (!$this->tableExists('administrative_procedures')) {
            return [];
        }
        $like = '%' . $query . '%';
        try {
            $stmt = $this->db->prepare(
                "SELECT id, titulo, numero_processo, status FROM administrative_procedures
                 WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                   AND (titulo LIKE ? OR numero_processo LIKE ? OR assunto LIKE ?)
                 ORDER BY id DESC LIMIT " . (int)$limit
            );
            $stmt->execute([$like, $like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'type'       => 'administrative',
                    'type_label' => 'Administrativos',
                    'id'         => $r['id'],
                    'title'      => $r['titulo'],
                    'subtitle'   => $r['numero_processo'] ?: ($r['status'] ?: ''),
                    'link'       => '/administrative-procedures/' . $r['id'],
                    'icon'       => 'fas fa-city',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function firstExistingColumn(string $table, array $columns)
    {
        try {
            foreach ($columns as $col) {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
                $stmt->execute([$col]);
                if ($stmt->fetch()) {
                    return $col;
                }
            }
        } catch (\Throwable $e) {}
        return null;
    }

    private function logSearch(int $userId, string $query, int $resultsCount): void
    {
        try {
            if (!$this->tableExists('search_logs')) {
                return;
            }
            $stmt = $this->db->prepare(
                "INSERT INTO search_logs (user_id, query, results_count, created_at) VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$userId, $query, $resultsCount]);
        } catch (\Throwable $e) {}
    }
}
