<?php
require_once __DIR__ . '/config.php';

function generate_token($user_id) {
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'exp' => time() + 86400 // 24 hours
    ]));
    $signature = hash_hmac('sha256', $payload, TOKEN_SECRET);
    return $payload . '.' . $signature;
}

function verify_token($token) {
    if (empty($token)) return null;
    
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;
    
    [$payload, $signature] = $parts;
    $expected_signature = hash_hmac('sha256', $payload, TOKEN_SECRET);
    
    if (!hash_equals($expected_signature, $signature)) return null;
    
    $data = json_decode(base64_decode($payload), true);
    if (!$data || !isset($data['exp']) || $data['exp'] < time()) return null;
    
    return $data['user_id'];
}

function get_authenticated_user() {
    $token = $_SESSION['token'] ?? null;
    if (!$token) return null;
    
    $user_id = verify_token($token);
    if (!$user_id) return null;
    
    $users = read_json('users.json');
    foreach ($users as $user) {
        if ($user['id'] === $user_id) {
            return $user;
        }
    }
    return null;
}

function require_auth() {
    $user = get_authenticated_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_admin() {
    $user = require_auth();
    if ($user['rule'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
    return $user;
}

function is_logged_in() {
    return get_authenticated_user() !== null;
}

function is_admin() {
    $user = get_authenticated_user();
    return $user && $user['rule'] === 'admin';
}
