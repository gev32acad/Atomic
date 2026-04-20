<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user || $user['rule'] !== 'admin') {
    json_error('Forbidden', 403);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$users = read_json('users.json');

if ($method_req === 'GET') {
    // Return users without passwords
    $safe_users = array_map(function($u) {
        unset($u['password']);
        return $u;
    }, $users);
    json_response(array_values($safe_users));
}

if ($method_req === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $plan = $_POST['plan'] ?? 'Starter';
    $rule = $_POST['rule'] ?? 'user';
    $max_concurrents = intval($_POST['max_concurrents'] ?? 1);
    $max_seconds = intval($_POST['max_seconds'] ?? 60);
    $expiration_date = $_POST['expiration_date'] ?? null;
    
    if (empty($username) || empty($email) || empty($password)) {
        json_error('Username, email, and password are required');
    }
    
    // Check duplicates
    foreach ($users as $u) {
        if ($u['username'] === $username) json_error('Username already exists');
        if ($u['email'] === $email) json_error('Email already exists');
    }
    
    $new_user = [
        'id' => generate_id(),
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'plan' => $plan,
        'rule' => $rule,
        'join_date' => date('c'),
        'max_concurrents' => $max_concurrents,
        'max_seconds' => $max_seconds,
        'expiration_date' => $expiration_date ?: null,
        'api_key' => 'atomic_' . bin2hex(random_bytes(12))
    ];
    
    $users[] = $new_user;
    write_json('users.json', $users);
    
    $safe = $new_user;
    unset($safe['password']);
    json_response($safe, 201);
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('User ID is required');
    }
    
    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            if (isset($input['username'])) $u['username'] = $input['username'];
            if (isset($input['email'])) $u['email'] = $input['email'];
            if (!empty($input['password'])) $u['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            if (isset($input['plan'])) $u['plan'] = $input['plan'];
            if (isset($input['rule'])) $u['rule'] = $input['rule'];
            if (isset($input['max_concurrents'])) $u['max_concurrents'] = intval($input['max_concurrents']);
            if (isset($input['max_seconds'])) $u['max_seconds'] = intval($input['max_seconds']);
            if (array_key_exists('expiration_date', $input)) $u['expiration_date'] = $input['expiration_date'] ?: null;
            break;
        }
    }
    
    write_json('users.json', $users);
    json_response(['message' => 'User updated']);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('User ID is required');
    }
    
    // Prevent self-deletion
    if ($id === $user['id']) {
        json_error('Cannot delete your own account');
    }
    
    $users = array_values(array_filter($users, function($u) use ($id) {
        return $u['id'] !== $id;
    }));
    
    write_json('users.json', $users);
    json_response(['message' => 'User deleted']);
}

json_error('Method not allowed', 405);
