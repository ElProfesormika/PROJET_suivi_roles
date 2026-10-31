<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = $value;
                }
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action(...$params);
            return;
        }

        http_response_code(404);
        $controller = new Controller();
        $controller->render('errors/404', [
            'title' => 'Page introuvable',
        ]);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }
}
