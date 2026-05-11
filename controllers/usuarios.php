<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/maintenance.php';

function usuarios_set_flash(string $message): void {
    auth_start_session();
    $_SESSION['usuarios_flash'] = $message;
}

function usuarios_consume_flash(): ?string {
    auth_start_session();
    $message = $_SESSION['usuarios_flash'] ?? null;
    unset($_SESSION['usuarios_flash']);
    return $message;
}

function usuarios_redirect_back(): void {
    $location = $_SERVER['REQUEST_URI'] ?? '/redmine/?page=usuarios';
    header('Location: ' . $location);
    exit;
}
// CRUD básico para usuarios usando data/usuarios.json
function usuarios_data_file(): string {
    return __DIR__ . '/../data/usuarios.json';
}

function usuarios_config_file(): string {
    return __DIR__ . '/../data/configuracion.json';
}

function rut_base($rut) {
    $clean = preg_replace('/[^0-9kK]/', '', $rut ?? '');
    if ($clean === '') return '';
    $clean = strtoupper($clean);
    return strlen($clean) > 1 ? substr($clean, 0, -1) : $clean;
}

function ensure_usr_file($path) {
    if (!$path) {
        throw new RuntimeException('Ruta de usuarios no configurada');
    }
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function ensure_user_fields(array &$item) {
    $defaults = [
        'id' => uniqid('', true),
        'rut_sin_dv' => '',
        'nombre' => '',
        'apellido' => '',
        'rut' => '',
        'numero_celular' => '',
        'api' => '',
        'rol' => 'usuario',
        'password' => '',
        'estado_usuario' => 'activo',
    ];
    foreach ($defaults as $key => $value) {
        if (!isset($item[$key])) {
            $item[$key] = $value;
        }
    }
}

function load_usuarios($path) {
    ensure_usr_file($path);
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) $data = [];
    $changed = false;
    foreach ($data as &$item) {
        $prev = $item;
        ensure_user_fields($item);
        if ($item !== $prev) $changed = true;
    }
    if ($changed) save_usuarios($path, $data);
    return $data;
}

function save_usuarios($path, $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function find_user_index(array $rows, string $id): ?int {
    foreach ($rows as $idx => $row) {
        if ((string)($row['id'] ?? '') === (string)$id) return $idx;
    }
    return null;
}

function has_duplicate_id(array $rows, string $id): bool {
    foreach ($rows as $row) {
        if ((string)($row['id'] ?? '') === (string)$id) return true;
    }
    return false;
}

function has_duplicate_rut(array $rows, string $rutBase, string $excludeId = ''): bool {
    if ($rutBase === '') return false;
    foreach ($rows as $row) {
        if ($excludeId !== '' && (string)($row['id'] ?? '') === (string)$excludeId) {
            continue;
        }
        $rutExist = preg_replace('/[^0-9kK]/', '', $row['rut'] ?? '');
        if (rut_base($rutExist) === $rutBase) {
            return true;
        }
    }
    return false;
}

function normalize_phone(string $value): string {
    $digits = preg_replace('/[^0-9]/', '', $value ?? '');
    if ($digits === '') return '';
    if (strlen($digits) === 9 && strpos($digits, '9') === 0) {
        return '+56' . $digits;
    }
    if (strlen($digits) === 11 && strpos($digits, '56') === 0) {
        return '+' . $digits;
    }
    if (strpos($digits, '569') === 0 && strlen($digits) === 11) {
        return '+' . $digits;
    }
    return '+' . ltrim($digits, '0');
}

function has_duplicate_phone(array $rows, string $phone, string $excludeId = ''): bool {
    if ($phone === '') return false;
    foreach ($rows as $row) {
        if ($excludeId !== '' && (string)($row['id'] ?? '') === (string)$excludeId) {
            continue;
        }
        $existing = normalize_phone($row['numero_celular'] ?? '');
        if ($existing === $phone && $existing !== '') return true;
    }
    return false;
}

function sanitize_input(string $value): string {
    return trim(filter_var($value, FILTER_UNSAFE_RAW) ?? '');
}

function sanitize_phone(string $value): string {
    return preg_replace('/[^0-9+]/', '', $value ?? '');
}

function format_rut_value(string $rut): string {
    $clean = preg_replace('/[^0-9kK]/', '', $rut ?? '');
    if ($clean === '') return '';
    $clean = strtoupper($clean);
    if (strlen($clean) < 2) return $clean;
    $body = substr($clean, 0, -1);
    $dv = substr($clean, -1);
    $body = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $body);
    return $body . '-' . $dv;
}

function usuarios_load_config(): array {
    $path = usuarios_config_file();
    if (!is_file($path)) return [];
    $cfg = json_decode((string)file_get_contents($path), true);
    return is_array($cfg) ? $cfg : [];
}

function usuarios_current_api_token(): string {
    $cfg = usuarios_load_config();
    $token = trim((string)($cfg['platform_token'] ?? ''));
    if ($token !== '') return $token;
    if (!function_exists('auth_get_user_id') || !function_exists('auth_find_user_by_id')) return '';
    $user = auth_find_user_by_id(auth_get_user_id());
    return is_array($user) ? trim((string)($user['api'] ?? '')) : '';
}

function usuarios_redmine_memberships_url(): string {
    $cfg = usuarios_load_config();
    $url = trim((string)($cfg['members_url'] ?? ''));
    if ($url !== '') {
        return preg_replace('#/settings/members/?$#', '/memberships.json', $url);
    }
    $platformUrl = trim((string)($cfg['platform_url'] ?? ''));
    if ($platformUrl === '') return '';
    if (preg_match('#/projects/([^/]+)/settings/members/?$#', $platformUrl)) {
        return preg_replace('#/settings/members/?$#', '/memberships.json', $platformUrl);
    }
    if (preg_match('#/projects/([^/]+)/issues(?:\.json)?$#', $platformUrl)) {
        return preg_replace('#/issues(?:\.json)?$#', '/memberships.json', $platformUrl);
    }
    $parts = parse_url($platformUrl);
    if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return '';
    $path = $parts['path'] ?? '';
    if (!preg_match('#/projects/([^/]+)#', $path, $m)) return '';
    $prefix = preg_replace('#/projects/.*$#', '', $path);
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    return $parts['scheme'] . '://' . $parts['host'] . $port . $prefix . '/projects/' . $m[1] . '/memberships.json';
}

function usuarios_fetch_redmine_members(): array {
    if (!function_exists('curl_init')) {
        return ['error' => 'La extension curl de PHP no esta disponible.'];
    }
    $baseUrl = usuarios_redmine_memberships_url();
    if ($baseUrl === '') {
        return ['error' => 'No se pudo determinar la URL de miembros Redmine. Revisa platform_url.'];
    }
    $token = usuarios_current_api_token();
    if ($token === '') {
        return ['error' => 'Falta token API de Redmine. Configura platform_token o API del usuario actual.'];
    }
    $members = [];
    $offset = 0;
    $limit = 100;
    do {
        $url = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'limit=' . $limit . '&offset=' . $offset;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Redmine-API-Key: ' . $token, 'Accept: application/json'],
            CURLOPT_TIMEOUT => 25,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $err !== '') {
            return ['error' => 'No se pudo conectar a Redmine: ' . $err];
        }
        if ($code >= 400) {
            return ['error' => 'Redmine respondio HTTP ' . $code . ' al consultar miembros.'];
        }
        $json = json_decode((string)$body, true);
        if (!is_array($json) || !isset($json['memberships']) || !is_array($json['memberships'])) {
            return ['error' => 'La respuesta de Redmine no contiene memberships.'];
        }
        $batch = $json['memberships'];
        $members = array_merge($members, $batch);
        $total = (int)($json['total_count'] ?? count($members));
        $offset += $limit;
    } while (count($members) < $total && !empty($batch));
    return ['members' => $members];
}

function usuarios_split_name(string $name): array {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') return ['', ''];
    $parts = explode(' ', $name);
    $first = array_shift($parts);
    return [$first, trim(implode(' ', $parts))];
}

function usuarios_import_from_redmine(array &$rows): array {
    $remote = usuarios_fetch_redmine_members();
    if (isset($remote['error'])) return ['error' => $remote['error']];
    $created = 0;
    $updated = 0;
    $byId = [];
    foreach ($rows as $idx => $row) {
        $byId[(string)($row['id'] ?? '')] = $idx;
    }
    $rolePerms = [];
    if (function_exists('auth_load_roles')) {
        $roles = auth_load_roles();
        $rolePerms = is_array($roles['usuario'] ?? null) ? $roles['usuario'] : [];
    }
    foreach (($remote['members'] ?? []) as $membership) {
        if (!is_array($membership)) continue;
        $user = $membership['user'] ?? null;
        if (!is_array($user) || empty($user['id'])) continue;
        $id = (string)$user['id'];
        $name = trim((string)($user['name'] ?? ''));
        [$nombre, $apellido] = usuarios_split_name($name);
        $payload = [
            'id' => $id,
            'rut_sin_dv' => '',
            'nombre' => $nombre !== '' ? $nombre : 'Redmine',
            'apellido' => $apellido,
            'rut' => '',
            'numero_celular' => '',
            'rol' => 'usuario',
            'api' => '',
            'password' => '',
            'permisos' => $rolePerms,
            'estado_usuario' => 'baneado',
            'redmine_membership_id' => $membership['id'] ?? '',
        ];
        if (isset($byId[$id])) {
            $idx = $byId[$id];
            if (($rows[$idx]['rol'] ?? '') === 'root') {
                continue;
            }
            $rows[$idx]['nombre'] = $payload['nombre'];
            $rows[$idx]['apellido'] = $payload['apellido'];
            if (!isset($rows[$idx]['estado_usuario']) || $rows[$idx]['estado_usuario'] === '') {
                $rows[$idx]['estado_usuario'] = 'activo';
            }
            $rows[$idx]['redmine_membership_id'] = $payload['redmine_membership_id'];
            $updated++;
            continue;
        }
        $rows[] = $payload;
        $created++;
    }
    return ['created' => $created, 'updated' => $updated];
}

function handle_usuarios() {
    $DATA_FILE = usuarios_data_file();
    $rows = load_usuarios($DATA_FILE);
    $flash = usuarios_consume_flash();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (function_exists('csrf_validate')) csrf_validate();
        if (function_exists('maintenance_mode_block_if_enabled')) maintenance_mode_block_if_enabled();
        $action = $_POST['action'] ?? '';
        if ($action === 'import_redmine') {
            $result = usuarios_import_from_redmine($rows);
            if (isset($result['error'])) {
                return [$rows, 'Error: ' . $result['error']];
            }
            save_usuarios($DATA_FILE, $rows);
            usuarios_set_flash('Usuarios importados desde Redmine. Nuevos: ' . (int)($result['created'] ?? 0) . ', actualizados: ' . (int)($result['updated'] ?? 0) . '. Los nuevos quedan baneados por defecto.');
            usuarios_redirect_back();
        }
        $rut_input = preg_replace('/[^0-9kK]/', '', $_POST['rut'] ?? '');
        $rut_sin_dv = rut_base($rut_input);
        $id_input = sanitize_input($_POST['rut_sin_dv'] ?? '');
        $phone_input = sanitize_phone($_POST['numero_celular'] ?? '');
        $phone_base = normalize_phone($phone_input);

        if ($action === 'create') {
            if ($rut_input !== '' && $rut_sin_dv === '') {
                return [$rows, 'Error: RUT inválido'];
            }
            if ($hasDupId = ($id_input !== '' && has_duplicate_id($rows, $id_input))) {
                return [$rows, 'Error: el ID ya existe'];
            }
            if (has_duplicate_rut($rows, $rut_sin_dv)) {
                return [$rows, 'Error: el RUT ya existe'];
            }
            if (has_duplicate_phone($rows, $phone_base)) {
                return [$rows, 'Error: el celular ya existe'];
            }
            $pwd = sanitize_input($_POST['password'] ?? '');
            $pwd2 = sanitize_input($_POST['password_confirm'] ?? '');
            if ($pwd !== $pwd2) {
                return [$rows, 'Error: las contraseñas no coinciden'];
            }
            $hash = $pwd !== '' ? password_hash($pwd, PASSWORD_DEFAULT) : '';
            $assignedRole = sanitize_input($_POST['rol'] ?? 'usuario');
            $rolePerms = [];
            if (function_exists('auth_load_roles')) {
                $roles = auth_load_roles();
                $cfg = $roles[$assignedRole] ?? [];
                if (is_array($cfg)) {
                    $rolePerms = $cfg;
                }
            }
            $requiredName = sanitize_input($_POST['nombre'] ?? '');
            $requiredLast = sanitize_input($_POST['apellido'] ?? '');
            if ($requiredName === '' || $requiredLast === '') {
                return [$rows, 'Error: nombre y apellido son obligatorios'];
            }
            if ($phone_base === '') {
                return [$rows, 'Error: el celular debe contener dígitos válidos'];
            }
            $rows[] = [
                'id' => $id_input !== '' ? $id_input : ($rut_sin_dv ?: uniqid('', true)),
                'rut_sin_dv' => $rut_sin_dv,
                'nombre' => $requiredName,
                'apellido' => $requiredLast,
                'rut' => format_rut_value($rut_input),
                'numero_celular' => $phone_base,
                'rol' => $assignedRole,
                'api' => sanitize_input($_POST['api'] ?? ''),
                'password' => $hash,
                'permisos' => $rolePerms,
                'estado_usuario' => sanitize_input($_POST['estado_usuario'] ?? 'activo') === 'baneado' ? 'baneado' : 'activo',
            ];
            save_usuarios($DATA_FILE, $rows);
            usuarios_set_flash('Usuario creado');
            usuarios_redirect_back();
        } elseif ($action === 'update') {
            $id = $_POST['id'] ?? '';
            $index = find_user_index($rows, $id);
            if ($index === null) return [$rows, 'Error: usuario no encontrado'];
            $current = &$rows[$index];
            $rut_input = $rut_input ?: ($current['rut'] ?? '');
            $rut_sin_dv = rut_base($rut_input) ?: ($current['rut_sin_dv'] ?? '');
            if (has_duplicate_rut($rows, $rut_sin_dv, $id)) {
                return [$rows, 'Error: el RUT ya existe'];
            }
            if (has_duplicate_phone($rows, $phone_base, $id)) {
                return [$rows, 'Error: el celular ya existe'];
            }
            $pwd = sanitize_input($_POST['password'] ?? '');
            $pwd2 = sanitize_input($_POST['password_confirm'] ?? '');
            if ($pwd !== '' || $pwd2 !== '') {
                if ($pwd !== $pwd2) {
                    return [$rows, 'Error: las contraseñas no coinciden'];
                }
                $current['password'] = password_hash($pwd, PASSWORD_DEFAULT);
            }
            $requiredNameUp = sanitize_input($_POST['nombre'] ?? $current['nombre']);
            $requiredLastUp = sanitize_input($_POST['apellido'] ?? $current['apellido']);
            if ($requiredNameUp === '' || $requiredLastUp === '') {
                return [$rows, 'Error: nombre y apellido son obligatorios'];
            }
            if ($phone_base === '') {
                return [$rows, 'Error: el celular debe contener dígitos válidos'];
            }
            $current['rut_sin_dv'] = $rut_sin_dv;
            $current['nombre'] = $requiredNameUp;
            $current['apellido'] = $requiredLastUp;
            $current['rut'] = format_rut_value($rut_input);
            $current['numero_celular'] = $phone_base;
            $current['rol'] = sanitize_input($_POST['rol'] ?? ($current['rol'] ?? 'usuario'));
            $current['api'] = sanitize_input($_POST['api'] ?? $current['api']);
            $current['estado_usuario'] = sanitize_input($_POST['estado_usuario'] ?? ($current['estado_usuario'] ?? 'activo')) === 'baneado' ? 'baneado' : 'activo';
            save_usuarios($DATA_FILE, $rows);
            usuarios_set_flash('Usuario actualizado');
            usuarios_redirect_back();
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            $rows = array_values(array_filter($rows, fn($r) => (string)($r['id'] ?? '') !== (string)$id));
            save_usuarios($DATA_FILE, $rows);
            usuarios_set_flash('Usuario eliminado');
            usuarios_redirect_back();
        }
    }
    return [$rows, $flash];
}
