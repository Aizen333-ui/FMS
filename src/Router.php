<?php
declare(strict_types=1);

namespace Fms;

final class Router
{
    /** @var array<string, array{0:callable,1:list<string>}> */
    private array $routes = [];

    /** @param list<string> $roles */
    public function map(string $method, string $path, callable $handler, array $roles = []): void
    {
        $this->routes[$method . ' ' . $path] = [$handler, $roles];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $route = $this->routes[$method . ' ' . $path] ?? null;
        if ($route === null) {
            http_response_code(404);
            Http::render('errors/404', ['title' => 'Page not found']);
            return;
        }
        [$handler, $roles] = $route;
        if ($roles !== []) {
            if (!Auth::check()) {
                $target = str_starts_with($path, '/admin') ? '/admin/login' : '/';
                Http::flash('error', 'Please sign in to continue.');
                Http::redirect($target);
            }
            if (!in_array((string) Auth::role(), $roles, true)) {
                http_response_code(403);
                Http::render('errors/403', ['title' => 'Not allowed']);
                return;
            }
        }
        if ($method === 'POST') {
            Http::verifyCsrf();
        }
        $handler();
    }
}
