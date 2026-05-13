<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user || $user['rule'] !== 'admin') {
    json_error('Forbidden', 403);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$servers = read_json('servers.json');

if ($method_req === 'GET') {
    $safe = array_map(function ($s) {
        if (!empty($s['api_key'])) {
            $s['api_key']     = str_repeat('*', 8);
            $s['api_key_set'] = true;
        } else {
            $s['api_key_set'] = false;
        }
        return $s;
    }, $servers);
    json_response(array_values($safe));
}

if (in_array($method_req, ['POST', 'PUT', 'DELETE'])) {
    verify_csrf_token();
}

if ($method_req === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $api_url = trim($_POST['api_url'] ?? '');
    $api_key = $_POST['api_key']      ?? '';
    $layer   = $_POST['layer']        ?? 'Layer4';
    $methods = $_POST['methods']      ?? [];

    if (empty($name)) {
        json_error('Server name is required');
    }
    if (empty($api_url)) {
        json_error('API URL is required');
    }
    if (!filter_var($api_url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $api_url)) {
        json_error('Invalid API URL. Must start with http:// or https://');
    }
    if (!in_array($layer, ['Layer4', 'Layer7', 'Both'], true)) {
        json_error('Invalid layer. Must be Layer4, Layer7, or Both');
    }
    if (!is_array($methods)) {
        $methods = [];
    }

    $new_server = [
        'id'      => generate_id(),
        'name'    => $name,
        'api_url' => $api_url,
        'api_key' => $api_key,
        'layer'   => $layer,
        'methods' => array_values(array_unique($methods)),
    ];

    $servers[] = $new_server;
    write_json('servers.json', $servers);

    // Mask the key before returning
    $new_server['api_key']     = !empty($api_key) ? str_repeat('*', 8) : '';
    $new_server['api_key_set'] = !empty($api_key);
    json_response($new_server, 201);
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = $input['id'] ?? '';

    if (empty($id)) {
        json_error('Server ID is required');
    }

    $found = false;
    foreach ($servers as &$server) {
        if ($server['id'] !== $id) {
            continue;
        }
        $found = true;

        if (isset($input['name'])) {
            $server['name'] = trim($input['name']);
        }
        if (isset($input['api_url'])) {
            $url = trim($input['api_url']);
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $url)) {
                json_error('Invalid API URL. Must start with http:// or https://');
            }
            $server['api_url'] = $url;
        }
        if (isset($input['layer'])) {
            if (!in_array($input['layer'], ['Layer4', 'Layer7', 'Both'], true)) {
                json_error('Invalid layer');
            }
            $server['layer'] = $input['layer'];
        }
        if (isset($input['methods']) && is_array($input['methods'])) {
            $server['methods'] = array_values(array_unique($input['methods']));
        }
        // Only update api_key if a real (non-masked) value is provided
        if (isset($input['api_key'])) {
            $key = $input['api_key'];
            if ($key !== '' && !preg_match('/^\*+$/', $key)) {
                $server['api_key'] = $key;
            } elseif ($key === '') {
                $server['api_key'] = '';
            }
        }
        break;
    }
    unset($server);

    if (!$found) {
        json_error('Server not found', 404);
    }

    write_json('servers.json', $servers);
    json_response(['message' => 'Server updated']);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = $input['id'] ?? '';

    if (empty($id)) {
        json_error('Server ID is required');
    }

    $servers = array_values(array_filter($servers, fn($s) => $s['id'] !== $id));
    write_json('servers.json', $servers);
    json_response(['message' => 'Server deleted']);
}

json_error('Method not allowed', 405);
