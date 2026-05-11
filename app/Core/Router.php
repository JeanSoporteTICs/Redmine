<?php

namespace App\Core;

final class Router
{
    /** @var array<string,array<string,string>> */
    private array $routes;

    /**
     * @param array<string,array<string,string>> $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(?string $page): void
    {
        $page = $this->normalizePage($page);
        $route = $this->routes[$page] ?? null;

        if ($route === null) {
            http_response_code(404);
            View::render('errors/404', ['page' => $page]);
            return;
        }

        (new \App\Controllers\PageController())->show($route);
    }

    private function normalizePage(?string $page): string
    {
        $page = trim((string) $page);
        if ($page === '') {
            return 'dashboard';
        }

        $page = strtolower(str_replace(['_', ' '], '-', $page));
        return preg_replace('/[^a-z0-9-]/', '', $page) ?: 'dashboard';
    }
}
