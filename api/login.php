<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    json_error('Username and password are required');
}

$users = read_json('users.json');
$found_user = null;

foreach ($users as $user) {
    if ($user['username'] === $username) {
        $found_user = $user;
        break;
    }
}

if (!$found_user || !password_verify($password, $found_user['password'])) {
    json_error('Invalid username or password', 401);
}

$token = generate_token($found_user['id']);
$_SESSION['token'] = $token;

json_response([
    'access_token' => $token,
    'admin' => $found_user['rule'] === 'admin'
]);
