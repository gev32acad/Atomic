<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user       = get_authenticated_user();
$method_req = $_SERVER['REQUEST_METHOD'];

if (!$user) {
    json_error('Unauthorized', 401);
}

$fav_file = 'favorites.json';

// GET – list user's favorites
if ($method_req === 'GET') {
    $all  = read_json($fav_file);
    $mine = array_values(array_filter($all, function($f) use ($user) {
        return ($f['user_id'] ?? '') === $user['id'];
    }));
    json_response($mine);
}

// POST – add a favorite
if ($method_req === 'POST') {
    verify_csrf_token();

    $name        = trim($_POST['name'] ?? '');
    $target      = trim($_POST['target'] ?? '');
    $port        = intval($_POST['port'] ?? 80);
    $method      = trim($_POST['method'] ?? '');
    $layer       = trim($_POST['layer'] ?? 'Layer4');
    $time        = intval($_POST['time'] ?? 60);
    $concurrents = intval($_POST['concurrents'] ?? 1);

    if (empty($name) || empty($target)) {
        json_error('Name and target are required');
    }
    if (strlen($name) > 64) {
        json_error('Name must be 64 characters or fewer');
    }

    $all = read_json($fav_file);

    // Limit per user
    $mine = array_filter($all, function($f) use ($user) { return ($f['user_id'] ?? '') === $user['id']; });
    if (count($mine) >= 20) {
        json_error('Favorites limit reached (max 20)');
    }

    $fav = [
        'id'          => generate_id(),
        'user_id'     => $user['id'],
        'name'        => $name,
        'target'      => $target,
        'port'        => $port,
        'method'      => $method,
        'layer'       => $layer,
        'time'        => $time,
        'concurrents' => $concurrents,
        'created_at'  => date('c'),
    ];
    $all[] = $fav;
    write_json($fav_file, $all);
    json_response(['message' => 'Favorite saved', 'favorite' => $fav], 201);
}

// DELETE – remove a favorite
if ($method_req === 'DELETE') {
    verify_csrf_token();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = $body['id'] ?? '';

    if (empty($id)) json_error('ID is required');

    $all     = read_json($fav_file);
    $updated = [];
    $found   = false;
    foreach ($all as $f) {
        if ($f['id'] === $id && $f['user_id'] === $user['id']) {
            $found = true;
            continue;
        }
        $updated[] = $f;
    }
    if (!$found) json_error('Favorite not found', 404);
    write_json($fav_file, $updated);
    json_response(['message' => 'Favorite deleted']);
}

json_error('Method not allowed', 405);
