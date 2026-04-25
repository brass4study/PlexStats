<?php

declare(strict_types=1);

namespace PlexStats\Presentation;

/**
 * Router minimalista: registra rutas (método + path) y despacha la petición.
 */
final class Router
{
    /** @var array<array{method: string, path: string, handler: callable}> */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath((string)(parse_url($uri, PHP_URL_PATH) ?? '/'));

        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $route['path'] === $path) {
                ($route['handler'])();
                return;
            }
        }

        http_response_code(404);
        echo '<h1>404 — Página no encontrada</h1>';
    }

    private function normalizePath(string $raw): string
    {
        $path = '/' . trim($raw, '/');
        return $path === '//' ? '/' : $path;
    }
}

