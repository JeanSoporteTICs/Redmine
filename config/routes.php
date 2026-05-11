<?php

return [
    'dashboard' => [
        'view' => 'views/Dashboard/dashboard.php',
        'active' => 'mensajes',
    ],
    'webhook' => [
        'view' => 'views/Webhook/simulador.php',
        'active' => 'webhook',
    ],
    'horas-extra' => [
        'view' => 'views/HorasExtra/horas_extra.php',
        'active' => 'horas',
    ],
    'historico' => [
        'view' => 'views/Historico/historico.php',
        'active' => 'historico',
    ],
    'usuarios' => [
        'view' => 'views/Usuarios/usuarios.php',
        'active' => 'usuarios',
    ],
    'configuracion' => [
        'view' => 'views/Configuracion/configuracion.php',
        'active' => 'configuracion',
    ],
    'sync-categorias' => [
        'view' => 'views/Categorias/categorias.php',
        'active' => 'configuracion',
    ],
    'unidades-cf' => [
        'view' => 'views/Configuracion/unidades_cf.php',
        'active' => 'configuracion',
    ],
    'estadisticas' => [
        'view' => 'views/Estadisticas/estadisticas.php',
        'active' => 'estadisticas',
    ],
    'estadisticas-api' => [
        'view' => 'views/Estadisticas/estadisticas_manual.php',
        'active' => 'estadisticas_api',
    ],
    'actividad' => [
        'view' => 'views/Security/activity.php',
        'active' => 'security',
    ],
];
