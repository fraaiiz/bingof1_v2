<?php

namespace App\Router;

class Router {
    private array $routes = [];

    // Méthode pour ajouter une route à la liste des routes
    public function addRoute(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    // Méthode pour gérer la requête entrante et exécuter le gestionnaire approprié
    public function handleRequest(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {

            $routePattern = preg_replace(
                '/\{[^}]+\}/',
                '([^/]+)',
                $route['path']
            );

            $pattern = '#^' . $routePattern . '$#';

            if (
                $route['method'] === $requestMethod &&
                preg_match($pattern, $requestPath, $matches)
            ) {
                array_shift($matches);

                call_user_func_array($route['handler'], $matches);

                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    public function get(string $path, callable $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void {
        $this->addRoute('POST', $path, $handler);
    }
}