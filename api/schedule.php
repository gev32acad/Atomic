<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user       = get_authenticated_user();
$method_req = $_SERVER['REQUEST_METHOD'];

if (!$user) {
    json_error('Unauthorized', 401);
}

// GET – list user's scheduled attacks
if ($method_req === 'GET') {
    $all  = read_json('scheduled.json');
    $mine = array_values(array_filter($all, function($s) use ($user) {
        return ($s['user_id'] ?? '') === $user['id'] && ($s['status'] ?? '') === 'scheduled';
    }));
    json_response($mine);
}

// DELETE – cancel a scheduled attack
if ($method_req === 'DELETE') {
    verify_csrf_token();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = $body['id'] ?? '';
    if (empty($id)) json_error('ID is required');

    $sched_path = DATA_DIR . 'scheduled.json';
    $fp = @fopen($sched_path, 'c+');
    if (!$fp) json_error('Server error', 500);
    flock($fp, LOCK_EX);
    $content = '';
    while (!feof($fp)) $content .= fread($fp, 8192);
    $all = json_decode($content, true) ?: [];

    $found   = false;
    $updated = [];
    foreach ($all as $s) {
        if ($s['id'] === $id && ($s['user_id'] ?? '') === $user['id']) {
            $found = true;
            continue;
        }
        $updated[] = $s;
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
