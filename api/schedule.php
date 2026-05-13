<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user       = get_authenticated_user();
$method_req = $_SERVER['REQUEST_METHOD'];

if (!$user) {
    json_error('Unauthorized', 401);
}

// GET – list user's pending scheduled attacks (stored in attacks.json with status="scheduled")
if ($method_req === 'GET') {
    $all  = read_json('attacks.json');
    $mine = array_values(array_filter($all, function($a) use ($user) {
        return ($a['user_id'] ?? '') === $user['id'] && ($a['status'] ?? '') === 'scheduled';
    }));
    json_response($mine);
}

// DELETE – cancel a scheduled attack (removes it from attacks.json, freeing the slot)
if ($method_req === 'DELETE') {
    verify_csrf_token();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = $body['id'] ?? '';
    if (empty($id)) json_error('ID is required');

    $atk_path = DATA_DIR . 'attacks.json';
    $fp = @fopen($atk_path, 'c+');
    if (!$fp) json_error('Server error', 500);
    flock($fp, LOCK_EX);

    $content = '';
    while (!feof($fp)) $content .= fread($fp, 8192);
    $all = json_decode($content, true) ?: [];

    $found   = false;
    $updated = [];
    foreach ($all as $a) {
        if ($a['id'] === $id && ($a['user_id'] ?? '') === $user['id'] && ($a['status'] ?? '') === 'scheduled') {
            $found = true;
            continue; // drop this entry → frees the slot
        }
        $updated[] = $a;
    }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    if (!$found) json_error('Scheduled attack not found', 404);
    json_response(['message' => 'Scheduled attack cancelled']);
}

json_error('Method not allowed', 405);
