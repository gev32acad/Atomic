<?php
require_once __DIR__ . '/config.php';

// =================== Token Blacklist ===================

function add_token_to_blacklist($token) {
    $blacklist_file = DATA_DIR . 'token_blacklist.json';
    $fp = @fopen($blacklist_file, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    $content = '';
    while (!feof($fp)) $content .= fread($fp, 8192);
    $list = json_decode($content, true) ?: [];
    $now = time();
    // Clean up expired tokens (older than 24 h token lifetime)
    $list = array_values(array_filter($list, function($entry) use ($now) {
        return ($now - $entry['ts']) < 86400;
    }));
    $list[] = ['token' => $token, 'ts' => $now];
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($list, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function is_token_blacklisted($token) {
    $blacklist_file = DATA_DIR . 'token_blacklist.json';
    if (!file_exists($blacklist_file)) return false;
    $content = file_get_contents($blacklist_file);
    $list = json_decode($content, true) ?: [];
    $now = time();
    foreach ($list as $entry) {
        if (($now - $entry['ts']) < 86400 && $entry['token'] === $token) {
            return true;
        }
    }
    return false;
}

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

    // Check blacklist (logout invalidation)
    if (is_token_blacklisted($token)) return null;
    
    return $data['user_id'];
}

function get_authenticated_user() {
    // First check API key (for external API calls) – header only to avoid URL logging
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? null;
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
                // Plan expired - downgrade to Starter (only if not already Starter)
                if ($user['plan'] !== 'Starter') {
                    return downgrade_expired_user($user);
                }
                // Already Starter but stale expiration_date – clear it silently
                return clear_stale_expiration($user);
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
            if (!$has_api_access) {
                return null;
            }
            // Check plan expiration
            if (!empty($user['expiration_date']) && strtotime($user['expiration_date']) < time()) {
                if ($user['plan'] !== 'Starter') {
                    return downgrade_expired_user($user);
                }
            }
            return $user;
        }
    }
    return null;
}

// Downgrade expired user to Starter plan (#16) – protected against race condition
function downgrade_expired_user($user) {
    $users_file = 'users.json';
    $path = DATA_DIR . $users_file;

    $fp = @fopen($path, 'c+');
    if (!$fp) {
        // Fallback: return downgraded user without writing
        $user['plan'] = 'Starter';
        $user['max_concurrents'] = 1;
        $user['max_seconds'] = 60;
        $user['expiration_date'] = null;
        return $user;
    }
    flock($fp, LOCK_EX);

    $content = '';
    while (!feof($fp)) $content .= fread($fp, 8192);
    $users = json_decode($content, true) ?: [];

    $updated_user = $user;
    foreach ($users as &$u) {
        if ($u['id'] === $user['id']) {
            // Re-check under lock: another request may have already downgraded
            if ($u['plan'] === 'Starter') {
                flock($fp, LOCK_UN);
                fclose($fp);
                return $u;
            }
            $u['plan'] = 'Starter';
            $u['max_concurrents'] = 1;
            $u['max_seconds'] = 60;
            $u['expiration_date'] = null;
            $updated_user = $u;
            break;
        }
    }
    unset($u);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $updated_user;
}

// Clear a stale expiration_date when plan is already Starter
function clear_stale_expiration($user) {
    $users = read_json('users.json');
    foreach ($users as &$u) {
        if ($u['id'] === $user['id']) {
            $u['expiration_date'] = null;
            $user = $u;
            break;
        }
    }
    unset($u);
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
    if ($user['role'] !== 'admin') {
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
    return $user && $user['role'] === 'admin';
}
