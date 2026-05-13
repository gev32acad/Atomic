<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$request_user = get_authenticated_user();
if (!$request_user) {
    json_error('Unauthorized', 401);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$is_admin   = $request_user['role'] === 'admin';

// =================== GET ===================

if ($method_req === 'GET') {
    $servers = read_json('servers.json');

    // ?action=check&id=xxx – ping a single server (admin only)
    if (isset($_GET['action']) && $_GET['action'] === 'check' && isset($_GET['id'])) {
        if (!$is_admin) json_error('Forbidden', 403);
        $id = $_GET['id'];
        $target = null;
        foreach ($servers as $s) {
            if ($s['id'] === $id) { $target = $s; break; }
        }
        if (!$target) json_error('Server not found', 404);

        $online = ping_server($target);
        json_response(['online' => $online]);
    }

    if ($is_admin) {
        // Admin gets full server list (including api_key, url)
        json_response(array_values($servers));
    } else {
        // Regular users only see id, name, layer, methods, enabled – not url/apikey
        $safe = array_map(function($s) {
            return [
                'id'      => $s['id'],
                'name'    => $s['name'],
                'layer'   => $s['layer'],
                'methods' => $s['methods'] ?? [],
                'enabled' => $s['enabled'],
            ];
        }, $servers);
        json_response(array_values($safe));
    }
}

// State-changing operations are admin-only
if (!$is_admin) {
    json_error('Forbidden', 403);
}

if (in_array($method_req, ['POST', 'PUT', 'DELETE'])) {
    verify_csrf_token();
}

// =================== POST (create) ===================

if ($method_req === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $api_url = trim($_POST['api_url'] ?? '');
    $layer   = $_POST['layer'] ?? 'Layer4';
    $api_key = trim($_POST['api_key'] ?? '');
    $enabled = filter_var($_POST['enabled'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

    // methods: comma-separated string or JSON array
    $methods_raw = trim($_POST['methods'] ?? '');
    $methods = parse_methods_input($methods_raw);

    if (empty($name)) {
        json_error('Server name is required');
    }
    if (empty($api_url) || !filter_var(strtok($api_url, '?'), FILTER_VALIDATE_URL)) {
        json_error('Valid API URL is required (e.g. http://example.com/api?host={host}&port={port}&time={time}&method={method}&key={apikey})');
    }
    if (!in_array($layer, ['Layer4', 'Layer7', 'Both'], true)) {
        json_error('Layer must be Layer4, Layer7, or Both');
    }

    $new_server = [
        'id'      => generate_id(),
        'name'    => $name,
        'api_url' => $api_url,
        'layer'   => $layer,
        'methods' => $methods,
        'api_key' => $api_key,
        'enabled' => $enabled,
    ];

    $servers   = read_json('servers.json');
    $servers[] = $new_server;
    write_json('servers.json', $servers);

    json_response($new_server, 201);
}

// =================== PUT (update) ===================

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = $input['id'] ?? '';

    if (empty($id)) json_error('Server ID is required');

    $servers = read_json('servers.json');
    $found   = false;

    foreach ($servers as &$s) {
        if ($s['id'] === $id) {
            if (isset($input['name']))    $s['name']    = trim($input['name']);
            if (isset($input['api_url'])) $s['api_url'] = trim($input['api_url']);
            if (isset($input['layer']) && in_array($input['layer'], ['Layer4', 'Layer7', 'Both'], true)) {
                $s['layer'] = $input['layer'];
            }
            if (isset($input['methods'])) {
                $s['methods'] = is_array($input['methods'])
                    ? array_values(array_filter(array_map('trim', $input['methods'])))
                    : parse_methods_input($input['methods']);
            }
            if (array_key_exists('api_key', $input)) $s['api_key'] = trim($input['api_key'] ?? '');
            if (isset($input['enabled']))  $s['enabled']  = (bool)$input['enabled'];
            $found = true;
            break;
        }
    }
    unset($s);

    if (!$found) json_error('Server not found', 404);

    write_json('servers.json', $servers);
    json_response(['message' => 'Server updated']);
}

// =================== DELETE ===================

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = $input['id'] ?? '';

    if (empty($id)) json_error('Server ID is required');

    $servers = read_json('servers.json');
    $servers = array_values(array_filter($servers, function($s) use ($id) {
        return $s['id'] !== $id;
    }));

    write_json('servers.json', $servers);
    json_response(['message' => 'Server deleted']);
}

json_error('Method not allowed', 405);

// =================== Helpers ===================

function parse_methods_input($raw) {
    if (empty($raw)) return [];
    $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_map('strtoupper', array_map('trim', $parts)));
}

function ping_server($server) {
    $url = $server['api_url'] ?? '';
    if (empty($url)) return false;

    // Replace placeholders with dummy values for a health-check request
    $replacements = [
        '{host}'        => 'test',
        '{ip}'          => '1.1.1.1',
        '{port}'        => '80',
        '{time}'        => '1',
        '{method}'      => 'TEST',
        '{apikey}'      => urlencode($server['api_key'] ?? ''),
        '{key}'         => urlencode($server['api_key'] ?? ''),
        '{concurrents}' => '1',
    ];
    $test_url = str_replace(array_keys($replacements), array_values($replacements), $url);

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $test_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY         => true,
        ]);
        curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return empty($error) && $code > 0;
    }

    // Fallback
    $ctx  = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    $resp = @file_get_contents($test_url, false, $ctx);
    return $resp !== false;
}
