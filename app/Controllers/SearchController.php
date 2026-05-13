<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use App\Services\SearchService;

class SearchController extends Controller
{
    private $service;

    public function __construct()
    {
        $this->service = new SearchService();
    }

    public function index(): void
    {
        $query   = trim($this->input('q', ''));
        $results = [];

        if (strlen($query) >= 2) {
            $userId  = (int)Session::get('user_id');
            $all     = $this->service->search($query, $userId);

            // Group by type
            foreach ($all as $item) {
                $type = $item['type'];
                if (!isset($results[$type])) {
                    $results[$type] = [
                        'label' => $item['type_label'],
                        'icon'  => $item['icon'],
                        'items' => [],
                    ];
                }
                $results[$type]['items'][] = $item;
            }
        }

        $this->render('search/index', [
            'pageTitle' => 'Busca Global',
            'query'     => $query,
            'results'   => $results,
        ]);
    }

    public function api(): void
    {
        $query  = trim($this->input('q', ''));
        $userId = (int)Session::get('user_id');

        if (strlen($query) < 2) {
            $this->json(['success' => true, 'query' => $query, 'results' => [], 'total' => 0]);
            return;
        }

        $all     = $this->service->search($query, $userId);
        $grouped = [];

        foreach ($all as $item) {
            $type = $item['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [
                    'label' => $item['type_label'],
                    'icon'  => $item['icon'],
                    'items' => [],
                ];
            }
            $grouped[$type]['items'][] = $item;
        }

        $this->json([
            'success' => true,
            'query'   => $query,
            'results' => $grouped,
            'total'   => count($all),
        ]);
    }

    public function suggest(): void
    {
        $query = trim($this->input('q', ''));

        if (strlen($query) < 2) {
            $this->json([]);
            return;
        }

        $suggestions = $this->service->suggest($query);
        $this->json($suggestions);
    }
}
