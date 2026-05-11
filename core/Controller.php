<?php
declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Inject common template variables
        $data += [
            'csrf_token' => Session::csrfToken(),
            'error'      => Session::getFlash('error'),
            'success'    => Session::getFlash('success'),
            'old'        => Session::getFlash('old', []),
        ];
        extract($data);
        $viewFile = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            Logger::error("View not found: {$viewFile}");
            http_response_code(500);
            echo 'Erro interno: view não encontrada.';
            return;
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        $layoutFile = ROOT_PATH . '/app/Views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function redirect(string $url, int $code = 302): void
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function validateCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($token)) {
            Logger::security('CSRF token mismatch', ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
            http_response_code(403);
            die('Requisição inválida: token CSRF inválido.');
        }
    }

    protected function requirePermission(string $permission): void
    {
        $user = Session::get('user');
        if (!$user) {
            $this->redirect('/login');
        }
        if (!isset($user['permissions'][$permission]) || !$user['permissions'][$permission]) {
            Session::flash('error', 'Você não tem permissão para realizar esta ação.');
            $this->redirect('/dashboard');
        }
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
