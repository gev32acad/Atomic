<?php
session_start();

define('DATA_DIR', __DIR__ . '/../data/');
define('SITE_NAME', 'NetStress');
define('TOKEN_SECRET', 'a7f3c9e2b1d8f64ec3a2b9d7f5e38c1ab4d6f2e9c71a3b5d1f8e4c2a6b3d9f7');

// Hardcoded crypto exchange rates (approximate USD values) - used server-side for amount validation
define('CRYPTO_RATES', ['BTC' => 65000, 'ETH' => 3200, 'LTC' => 80, 'XMR' => 170]);

// Backend SSL peer verification (set to true when backend servers have valid certificates)
define('VERIFY_BACKEND_SSL', false);

// Crypto wallet addresses for payments
define('CRYPTO_BTC_ADDRESS', '1A1zP1eP5QGefi2DMPTfTL5SLmv7Divf');
define('CRYPTO_ETH_ADDRESS', '0x742d35Cc6634C0532925a3b844Bc454e4438f44e');
define('CRYPTO_LTC_ADDRESS', 'LaMT348PWRnrqeeWArpwQPbuanpXDZGEUz');
define('CRYPTO_XMR_ADDRESS', '888tNkZrPN6JsEgekjMnABU4TBzc2Dt29EPAvkRDZVN');

// Telegram support link
define('TELEGRAM_LINK', 'https://t.me/netstressme');



// Rate limiting settings
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900); // 15 minutes

// =================== Security Headers ===================

function send_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none'");
}
send_security_headers();

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
    $key = $action . ':' . $identifier;
    $now = time();

    $fp = fopen($rate_file, 'c+');
    if (!$fp) {
        // If the lock file cannot be opened, deny the request to fail safely
        return RATE_LIMIT_WINDOW;
    }

    flock($fp, LOCK_EX);

    $content = '';
    while (!feof($fp)) {
        $content .= fread($fp, 8192);
    }
    $limits = json_decode($content, true) ?: [];

    // Clean up expired entries
    foreach ($limits as $k => $entries) {
        $limits[$k] = array_values(array_filter($entries, function($timestamp) use ($now) {
            return ($now - $timestamp) < RATE_LIMIT_WINDOW;
        }));
        if (empty($limits[$k])) {
            unset($limits[$k]);
        }
    }

    // Check current count
    $attempts = $limits[$key] ?? [];
    if (count($attempts) >= RATE_LIMIT_MAX_ATTEMPTS) {
        $oldest = min($attempts);
        $retry_after = RATE_LIMIT_WINDOW - ($now - $oldest);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $retry_after;
    }

    // Record this attempt and persist atomically
    $limits[$key][] = $now;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($limits, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return false; // Not rate limited
}

function clear_rate_limit($action, $identifier) {
    $rate_file = DATA_DIR . 'rate_limits.json';

    $fp = @fopen($rate_file, 'c+');
    if (!$fp) return;

    flock($fp, LOCK_EX);

    $content = '';
    while (!feof($fp)) {
        $content .= fread($fp, 8192);
    }
    $limits = json_decode($content, true) ?: [];
    $key = $action . ':' . $identifier;
    unset($limits[$key]);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($limits, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// =================== Input Validation ===================

function validate_ipv4($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false && preg_match('/^https?:\/\//', $url);
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

    // Check global blacklist
    $blacklist = read_json('blacklist.json');
    foreach ($blacklist as $entry) {
        $type = $entry['type'] ?? 'ip';
        $val  = $entry['value'] ?? '';
        if ($type === 'ip' && $val === $target) {
            json_error('Target is blocked by the global blacklist');
        }
        if ($type === 'cidr' && $layer === 'Layer4' && ip_in_cidr($target, $val)) {
            json_error('Target is blocked by the global blacklist');
        }
        if ($type === 'url' && $layer === 'Layer7') {
            $th = parse_url($target, PHP_URL_HOST);
            $bh = parse_url($val,    PHP_URL_HOST);
            if ($th && $bh && strtolower($th) === strtolower($bh)) {
                json_error('Target is blocked by the global blacklist');
            }
        }
    }

    return true;
}

function ip_in_cidr($ip, $cidr) {
    if (strpos($cidr, '/') === false) return $ip === $cidr;
    [$subnet, $bits] = explode('/', $cidr, 2);
    $bits = intval($bits);
    if ($bits < 0 || $bits > 32) return false;
    $mask       = -1 << (32 - $bits);
    $ip_long    = ip2long($ip);
    $sub_long   = ip2long($subnet);
    if ($ip_long === false || $sub_long === false) return false;
    return ($ip_long & $mask) === ($sub_long & $mask);
}

// =================== Live Crypto Rates ===================

function get_crypto_rates() {
    $cache_file = DATA_DIR . 'rates_cache.json';
    $cache_ttl  = 300; // 5 minutes

    if (file_exists($cache_file)) {
        $cache = json_decode(@file_get_contents($cache_file), true);
        if ($cache && isset($cache['timestamp']) && (time() - $cache['timestamp']) < $cache_ttl && isset($cache['rates'])) {
            return $cache['rates'];
        }
    }

    $live = fetch_coingecko_rates();
    if ($live) {
        @file_put_contents($cache_file, json_encode(['timestamp' => time(), 'rates' => $live], JSON_PRETTY_PRINT), LOCK_EX);
        return $live;
    }

    return CRYPTO_RATES; // fallback
}

function fetch_coingecko_rates() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,litecoin,monero&vs_currencies=usd';
    $map = ['bitcoin' => 'BTC', 'ethereum' => 'ETH', 'litecoin' => 'LTC', 'monero' => 'XMR'];
    $response = null;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'NetStress/1.0',
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) $response = null;
    } else {
        $ctx      = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $response = @file_get_contents($url, false, $ctx);
    }

    if (!$response) return null;
    $data = json_decode($response, true);
    if (!$data) return null;

    $rates = [];
    foreach ($map as $cg_id => $symbol) {
        $rates[$symbol] = isset($data[$cg_id]['usd']) ? floatval($data[$cg_id]['usd']) : CRYPTO_RATES[$symbol];
    }
    return $rates;
}
