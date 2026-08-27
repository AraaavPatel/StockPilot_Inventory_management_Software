<?php

namespace App\Core;

/**
 * Router
 *
 * Minimal but complete: static/dynamic segments ({id}), per-route
 * middleware stack, grouped by HTTP verb. Dispatches to
 * "App\Controllers\XController@method".
 */
class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $uri, string $action, array $middleware = []): void
    {
        $this->routes['GET'][] = compact('uri', 'action', 'middleware');
    }

    public function post(string $uri, string $action, array $middleware = []): void
    {
        $this->routes['POST'][] = compact('uri', 'action', 'middleware');
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $basePath = $this->basePath();

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes[$method] ?? [] as $route) {
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route['uri']);
            $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches);

                foreach ($route['middleware'] as $middleware) {
                    (new $middleware())->handle();
                }

                [$controllerName, $methodName] = explode('@', $route['action']);
                $controllerClass = "App\\Controllers\\{$controllerName}";

                $controller = new $controllerClass();
                call_user_func_array([$controller, $methodName], $matches);
                return;
            }
        }

        http_response_code(404);
        $notFoundView = __DIR__ . '/../Views/errors/404.php';
        if (file_exists($notFoundView)) {
            require $notFoundView;
        } else {
            echo '404 - Page not found';
        }
    }

    /**
     * Detects the sub-folder StockPilot is served from
     * (e.g. /stockpilot/public) so routing works under Laragon/XAMPP
     * without extra config.
     */
    private function basePath(): string
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $scriptDir === '/' ? '' : $scriptDir;
    }
}
