<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function any(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'pattern'    => $this->compile($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $path): string
    {
        // {id} -> ([^/]+)
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '([^/]+)', $path);
        return '#^' . $pattern . '/?$#';
    }

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches);

                // Run middleware
                foreach ($route['middleware'] as $mwClass) {
                    $mw = new $mwClass();
                    $mw->handle();
                }

                $handler = $route['handler'];
                if (is_string($handler) && str_contains($handler, '@')) {
                    [$ctrl, $action] = explode('@', $handler);
                    $class           = "App\\Controllers\\{$ctrl}";
                    $instance        = new $class();
                    echo $instance->{$action}(...$matches);
                    return;
                }
                if (is_callable($handler)) {
                    echo $handler(...$matches);
                    return;
                }
            }
        }
        http_response_code(404);
        $view = new View();
        echo $view->render('errors/404', [], 'auth');
    }
}
