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

// Rate limiting by IP (#2)
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limited = check_rate_limit('login_ip', $ip);
if ($rate_limited !== false) {
    json_error("Too many login attempts. Try again in {$rate_limited} seconds.", 429);
}

// Rate limiting by username to prevent distributed brute-force (#15)
$rate_limited_user = check_rate_limit('login_user', $username);
if ($rate_limited_user !== false) {
    json_error("Too many login attempts for this account. Try again in {$rate_limited_user} seconds.", 429);
}

$users = read_json('users.json');
$found_user = null;

foreach ($users as $user) {
    if ($user['username'] === $username) {
        $found_user = $user;
        break;
    }
}

// Always run password_verify to prevent timing attacks (#2)
// Use a dummy hash when user is not found so response time is consistent
$dummy_hash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012345';
$hash_to_check = $found_user ? $found_user['password'] : $dummy_hash;
$password_ok = password_verify($password, $hash_to_check);

if (!$found_user || !$password_ok) {
    json_error('Invalid username or password', 401);
}

// Clear rate limits on successful login
clear_rate_limit('login_ip', $ip);
clear_rate_limit('login_user', $username);

// Regenerate session ID to prevent session fixation (#3)
session_regenerate_id(true);

$token = generate_token($found_user['id']);
$_SESSION['token'] = $token;

json_response([
    'access_token' => $token,
    'admin' => $found_user['role'] === 'admin'
]);
