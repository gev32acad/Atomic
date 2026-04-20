<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

ob_end_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

// CSRF check (#1)
verify_csrf_token();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    json_error('Username and password are required');
}

// Rate limiting (#2)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limited = check_rate_limit('login', $ip);
if ($rate_limited !== false) {
    json_error("Too many login attempts. Try again in {$rate_limited} seconds.", 429);
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

// Clear rate limit on successful login
clear_rate_limit('login', $ip);

$token = generate_token($found_user['id']);
$_SESSION['token'] = $token;

json_response([
    'access_token' => $token,
    'admin' => $found_user['rule'] === 'admin'
]);
