<?php

namespace App\Controllers;

use App\Core\Controller;
use RuntimeException;

class PageController extends Controller
{
    /**
     * @param array<string,string> $route
     */
    public function show(array $route): void
    {
        $view = $route['view'] ?? '';
        $file = APP_BASE_PATH . '/' . ltrim(str_replace('\\', '/', $view), '/');

        if ($view === '' || !is_file($file)) {
            throw new RuntimeException('MVC route view not found: ' . $view);
        }

        $activeNav = $route['active'] ?? '';
        require $file;
    }
}
