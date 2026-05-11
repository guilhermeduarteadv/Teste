<?php
declare(strict_types=1);

namespace Core;

class Router
{
    private $routes = [];
    private $middlewares = [];
    private $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute(string $method, string $path, $handler, array $middlewares): void
    {
        $this->routes[] = [
            'method'      => $method,
            'path'        => $path,  // base path is stripped at dispatch time
            'handler'     => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $rawUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        // Strip base path prefix so routes are matched without it
        if ($this->basePath !== '' && strpos($rawUri, $this->basePath) === 0) {
            $rawUri = substr($rawUri, strlen($this->basePath));
        }
        $uri = '/' . trim($rawUri, '/');

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            $pattern = $this->buildPattern($route['path']);
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                $params = array_values(array_filter($matches));

                foreach ($route['middlewares'] as $middleware) {
                    $mw = new $middleware();
                    if (!$mw->handle()) {
                        return;
                    }
                }

                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        $this->notFound();
    }

    private function buildPattern(string $path): string
    {
        $path = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $path);
        return '#^' . $path . '$#';
    }

    private function callHandler($handler, array $params): void
    {
        // Closures/functions go directly; arrays ['Class','method'] always instantiate
        if (!is_array($handler) && is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }
        [$class, $method] = $handler;
        $controller = new $class();
        call_user_func_array([$controller, $method], $params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        if (file_exists(ROOT_PATH . '/app/Views/errors/404.php')) {
            require ROOT_PATH . '/app/Views/errors/404.php';
        } else {
            echo '<h1>404 - Página não encontrada</h1>';
        }
    }
}
