<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user       = get_authenticated_user();
$method_req = $_SERVER['REQUEST_METHOD'];

if (!$user || $user['role'] !== 'admin') {
    json_error('Admin access required', 403);
}

$bl_file = 'blacklist.json';

// GET – list entries
if ($method_req === 'GET') {
    json_response(read_json($bl_file));
}

// POST – add entry
if ($method_req === 'POST') {
    verify_csrf_token();

    $type  = trim($_POST['type'] ?? 'ip');
    $value = trim($_POST['value'] ?? '');
    $note  = trim($_POST['note'] ?? '');

    if (!in_array($type, ['ip', 'cidr', 'url'], true)) json_error('Type must be ip, cidr, or url');
    if (empty($value)) json_error('Value is required');

    if ($type === 'ip' && !filter_var($value, FILTER_VALIDATE_IP)) json_error('Invalid IP address');
    if ($type === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) json_error('Invalid URL');
    if ($type === 'cidr') {
        $parts = explode('/', $value, 2);
        if (count($parts) !== 2 || !filter_var($parts[0], FILTER_VALIDATE_IP)
            || !ctype_digit($parts[1]) || intval($parts[1]) > 32) {
            json_error('Invalid CIDR notation (e.g. 10.0.0.0/8)');
        }
    }

    $all     = read_json($bl_file);
    $entry   = ['id' => generate_id(), 'type' => $type, 'value' => $value, 'note' => $note, 'created_at' => date('c')];
    $all[]   = $entry;
    write_json($bl_file, $all);
    json_response(['message' => 'Entry added', 'entry' => $entry], 201);
}

// DELETE – remove entry
if ($method_req === 'DELETE') {
    verify_csrf_token();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = $body['id'] ?? '';
    if (empty($id)) json_error('ID is required');

    $all     = read_json($bl_file);
    $updated = array_values(array_filter($all, function($e) use ($id) { return $e['id'] !== $id; }));
    if (count($updated) === count($all)) json_error('Entry not found', 404);
    write_json($bl_file, $updated);
    json_response(['message' => 'Entry deleted']);
}

json_error('Method not allowed', 405);
