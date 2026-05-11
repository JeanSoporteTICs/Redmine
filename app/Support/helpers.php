<?php

namespace App\Support;

function app_config(?string $key = null, $default = null) {
    static $config;

    if ($config === null) {
        $config = require APP_BASE_PATH . '/config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function app_base_path(string $path = ''): string
{
    $base = APP_BASE_PATH;
    return $path === '' ? $base : $base . '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function app_base_url(string $path = ''): string
{
    $baseUrl = rtrim((string) app_config('base_url', ''), '/');
    if ($path === '') {
        return $baseUrl;
    }

    return $baseUrl . '/' . ltrim($path, '/');
}

function route_url(string $page, array $params = []): string
{
    $params = array_merge(['page' => $page], $params);
    return app_base_url('/?' . http_build_query($params));
}

function bootstrap_app(): void
{
    require_once APP_BASE_PATH . '/init_paths.php';
}
