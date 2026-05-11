<?php
require_once __DIR__ . '/app/bootstrap.php';

$routes = require APP_BASE_PATH . '/config/routes.php';
(new App\Core\Router($routes))->dispatch($_GET['page'] ?? null);
