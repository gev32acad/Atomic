<?php
session_start();

define('DATA_DIR', __DIR__ . '/../data/');
define('SITE_NAME', 'AtomicStresser');
define('TOKEN_SECRET', 'atomic_secret_key_change_me');

// Crypto wallet addresses for payments
define('CRYPTO_BTC_ADDRESS', '1A1zP1eP5QGefi2DMPTfTL5SLmv7Divf');
define('CRYPTO_ETH_ADDRESS', '0x742d35Cc6634C0532925a3b844Bc454e4438f44e');
define('CRYPTO_LTC_ADDRESS', 'LaMT348PWRnrqeeWArpwQPbuanpXDZGEUz');
define('CRYPTO_XMR_ADDRESS', '888tNkZrPN6JsEgekjMnABU4TBzc2Dt29EPAvkRDZVN');

// Telegram support link
define('TELEGRAM_LINK', 'https://t.me/atomicstresser');

// Hub API settings – set HUB_API_URL to the external stresser endpoint
// e.g. 'http://1.1.1.1/api.php'
define('HUB_API_URL', '');
define('HUB_API_KEY', '');

// Rate limiting settings
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900); // 15 minutes

// Helper functions for JSON data
function read_json($file) {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Failed to parse JSON from $file: " . json_last_error_msg());
        return [];
    }
    return $data ?: [];
}

function write_json($file, $data) {
    $path = DATA_DIR . $file;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("Failed to encode JSON for $file: " . json_last_error_msg());
        return false;
    }
    $result = file_put_contents($path, $json, LOCK_EX);
    if ($result === false) {
        error_log("Failed to write JSON file: $path");
        return false;
    }
    return true;
}

function generate_id() {
    return uniqid() . bin2hex(random_bytes(4));
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function json_error($message, $code = 400) {
    json_response(['detail' => $message], $code);
}

// =================== CSRF Protection ===================

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verify_csrf_token() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_error('Invalid CSRF token', 403);
    }
}

// =================== Rate Limiting ===================

function check_rate_limit($action, $identifier) {
    $rate_file = DATA_DIR . 'rate_limits.json';
    $limits = [];
    if (file_exists($rate_file)) {
        $limits = json_decode(file_get_contents($rate_file), true) ?: [];
    }
    
    $key = $action . ':' . $identifier;
    $now = time();
    
    // Clean up expired entries
    foreach ($limits as $k => $entries) {
        $limits[$k] = array_filter($entries, function($timestamp) use ($now) {
            return ($now - $timestamp) < RATE_LIMIT_WINDOW;
        });
        if (empty($limits[$k])) {
            unset($limits[$k]);
        }
    }
    
    // Check current count
    $attempts = $limits[$key] ?? [];
    if (count($attempts) >= RATE_LIMIT_MAX_ATTEMPTS) {
        $oldest = min($attempts);
        $retry_after = RATE_LIMIT_WINDOW - ($now - $oldest);
        return $retry_after; // Return seconds until retry is allowed
    }
    
    // Record this attempt
    $limits[$key][] = $now;
    $write_result = file_put_contents($rate_file, json_encode($limits, JSON_PRETTY_PRINT), LOCK_EX);
    if ($write_result === false) {
        error_log("Failed to write rate limit file: $rate_file");
    }
    
    return false; // Not rate limited
}

function clear_rate_limit($action, $identifier) {
    $rate_file = DATA_DIR . 'rate_limits.json';
    if (!file_exists($rate_file)) return;
    $limits = json_decode(file_get_contents($rate_file), true) ?: [];
    $key = $action . ':' . $identifier;
    unset($limits[$key]);
    file_put_contents($rate_file, json_encode($limits, JSON_PRETTY_PRINT), LOCK_EX);
}

// =================== Input Validation ===================

function validate_ipv4($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false && preg_match('/^https?:\/\//', $url);
}

// =================== Hub API ===================

/**
 * Forward an attack to the configured external hub API.
 * Returns the decoded JSON response on success, or false on failure / when
 * HUB_API_URL is not configured.
 */
function send_hub_request(array $params) {
    $base_url = rtrim(HUB_API_URL, '/');
    if (empty($base_url)) {
        return false; // Hub not configured
    }

    if (!empty(HUB_API_KEY)) {
        $params['key'] = HUB_API_KEY;
    }

    $url = $base_url . '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err || $response === false) {
        error_log("Hub API request failed: $err");
        return false;
    }

    $decoded = json_decode($response, true);
    if ($decoded === null) {
        error_log("Hub API returned non-JSON response: " . substr($response, 0, 200));
        return false;
    }
    return $decoded;
}

function validate_attack_target($target, $layer) {
    if ($layer === 'Layer4') {
        if (!validate_ipv4($target)) {
            json_error('Invalid IPv4 address. Example: 192.168.1.1');
        }
    } elseif ($layer === 'Layer7') {
        if (!validate_url($target)) {
            json_error('Invalid URL. Must start with http:// or https:// (e.g. https://example.com)');
        }
    }
    return true;
}
