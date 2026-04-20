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
    // First check API key (for external API calls)
    $api_key = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
    if ($api_key) {
        return get_user_by_api_key($api_key);
    }
    
    // Then check session token
    $token = $_SESSION['token'] ?? null;
    if (!$token) return null;
    
    $user_id = verify_token($token);
    if (!$user_id) return null;
    
    $users = read_json('users.json');
    foreach ($users as $user) {
        if ($user['id'] === $user_id) {
            // Check plan expiration (#16)
            if (!empty($user['expiration_date']) && strtotime($user['expiration_date']) < time()) {
                // Plan expired - downgrade to Starter
                return downgrade_expired_user($user);
            }
            return $user;
        }
    }
    return null;
}

// API-Key authentication for external API access (#9, #18)
function get_user_by_api_key($api_key) {
    if (empty($api_key) || !str_starts_with($api_key, 'atomic_') || strlen($api_key) !== 31) {
        return null;
    }
    
    $users = read_json('users.json');
    foreach ($users as $user) {
        if (isset($user['api_key']) && hash_equals($user['api_key'], $api_key)) {
            // Check if user's plan allows API access
            $plans = read_json('plans.json');
            $has_api_access = false;
            foreach ($plans as $plan) {
                if ($plan['name'] === $user['plan'] && !empty($plan['api_access'])) {
                    $has_api_access = true;
                    break;
                }
            }
            // Starter plan has no API access
            if (!$has_api_access && $user['plan'] === 'Starter') {
                return null;
            }
            // Check plan expiration
            if (!empty($user['expiration_date']) && strtotime($user['expiration_date']) < time()) {
                return downgrade_expired_user($user);
            }
            return $user;
        }
    }
    return null;
}

// Downgrade expired user to Starter plan (#16)
function downgrade_expired_user($user) {
    $users = read_json('users.json');
    foreach ($users as &$u) {
        if ($u['id'] === $user['id']) {
            $u['plan'] = 'Starter';
            $u['max_concurrents'] = 1;
            $u['max_seconds'] = 60;
            $u['expiration_date'] = null;
            $user = $u;
            break;
        }
    }
    write_json('users.json', $users);
    return $user;
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
