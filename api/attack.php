<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$method_req = $_SERVER['REQUEST_METHOD'];

if ($method_req === 'GET') {
    // Process any due scheduled attacks for this user
    process_due_scheduled_attacks($user);

    // Get running attacks for current user
    $attacks = read_json('attacks.json');
    $now = time();
    $running = [];
    
    foreach ($attacks as $attack) {
        if ($attack['user_id'] === $user['id']) {
            $start = strtotime($attack['start_time']);
            $duration = $attack['time'];
            $remaining = ($start + $duration) - $now;
            if ($remaining > 0) {
                $attack['remaining'] = $remaining;
                $running[] = $attack;
            }
        }
    }
    
    json_response($running);
}

if ($method_req === 'POST') {
    // CSRF check only for session-based auth (not API key)
    if (empty($_SERVER['HTTP_X_API_KEY'])) {
        verify_csrf_token();
    }
    
    $action = $_POST['action'] ?? 'start';
    
    if ($action === 'stop') {
        $attack_id = $_POST['attack_id'] ?? '';
        if (empty($attack_id)) {
            json_error('Attack ID is required');
        }

        $attacks_path = DATA_DIR . 'attacks.json';
        $fp = @fopen($attacks_path, 'c+');
        if (!$fp) json_error('Server error: could not open attacks file', 500);
        flock($fp, LOCK_EX);
        $content = '';
        while (!feof($fp)) $content .= fread($fp, 8192);
        $attacks = json_decode($content, true) ?: [];

        foreach ($attacks as &$attack) {
            if ($attack['id'] === $attack_id && $attack['user_id'] === $user['id']) {
                $attack['time'] = 0; // Mark as stopped
                break;
            }
        }
        unset($attack);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($attacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        json_response(['message' => 'Attack stopped']);
    }
    
    // Start attack
    $target = trim($_POST['target'] ?? $_POST['ip'] ?? '');
    $port = $_POST['port'] ?? '80';
    $time = intval($_POST['time'] ?? 0);
    $method = $_POST['method'] ?? '';
    $concurrents = intval($_POST['concurrents'] ?? 1);
    $layer = $_POST['layer'] ?? 'Layer4';
    $scheduled_at = trim($_POST['scheduled_at'] ?? '');
    
    if (!in_array($layer, ['Layer4', 'Layer7'], true)) {
        json_error('Invalid layer. Must be Layer4 or Layer7');
    }
    
    if (empty($target) || empty($method) || $time <= 0) {
        json_error('Target, method, and time are required');
    }
    
    // Input validation (#5)
    validate_attack_target($target, $layer);
    
    // Validate port
    $port_num = intval($port);
    if ($port_num < 1 || $port_num > 65535) {
        json_error('Port must be between 1 and 65535');
    }
    
    if ($time > $user['max_seconds']) {
        json_error('Time exceeds your plan limit (' . $user['max_seconds'] . 's max)');
    }

    // Validate min_seconds per plan + resolve allow_schedule flag
    $plans = read_json('plans.json');
    $min_seconds    = 10;
    $allow_schedule = false;
    foreach ($plans as $plan) {
        if ($plan['name'] === $user['plan']) {
            $min_seconds    = intval($plan['min_seconds'] ?? 10);
            $allow_schedule = !empty($plan['allow_schedule']);
            break;
        }
    }
    if ($time < $min_seconds) {
        json_error("Time must be at least {$min_seconds} seconds for your plan");
    }

    if ($concurrents < 1 || $concurrents > $user['max_concurrents']) {
        json_error('Concurrents exceed your plan limit (' . $user['max_concurrents'] . ' max)');
    }

    // Gate scheduled attacks behind plan feature
    $want_schedule = false;
    if (!empty($scheduled_at)) {
        if (!$allow_schedule) {
            json_error('Your plan does not include Attack Scheduling. Upgrade to Advanced or Enterprise to unlock it.');
        }
        $scheduled_ts = strtotime($scheduled_at);
        if ($scheduled_ts === false || $scheduled_ts < time() + 30) {
            json_error('Scheduled time must be at least 30 seconds in the future');
        }
        $want_schedule = true;
    }

    // Verify method exists and check premium access
    $methods = read_json('methods.json');
    $valid_method = false;
    $method_obj = null;
    foreach ($methods as $m) {
        if ($m['name'] === $method) {
            $valid_method = true;
            $method_obj = $m;
            if ($m['premium'] && $user['plan'] === 'Starter') {
                json_error('This method requires a premium plan');
            }
            break;
        }
    }

    if (!$valid_method) {
        json_error('Invalid method');
    }

    // Lock attacks.json for atomic concurrent-count check + insert.
    // Both running and scheduled attacks consume a slot – all stored in attacks.json
    // so this single flock() prevents races between concurrent requests.
    $attacks_path = DATA_DIR . 'attacks.json';
    $fp = @fopen($attacks_path, 'c+');
    if (!$fp) {
        json_error('Server error: could not open attacks file', 500);
    }
    flock($fp, LOCK_EX);

    $content = '';
    while (!feof($fp)) $content .= fread($fp, 8192);
    $attacks = json_decode($content, true) ?: [];

    $now = time();
    $running_count = 0;
    foreach ($attacks as $attack) {
        if ($attack['user_id'] !== $user['id']) continue;
        // Scheduled attacks occupy a slot until they fire or are cancelled
        if (($attack['status'] ?? '') === 'scheduled') {
            $running_count++;
            continue;
        }
        // Still-running attacks
        $start    = strtotime($attack['start_time'] ?? '');
        $duration = intval($attack['time'] ?? 0);
        if ($start && ($start + $duration) > $now) {
            $running_count++;
        }
    }

    if ($running_count >= $user['max_concurrents']) {
        flock($fp, LOCK_UN);
        fclose($fp);
        json_error('Maximum concurrent attacks reached');
    }

    $new_attack = [
        'id'          => generate_id(),
        'user_id'     => $user['id'],
        'target'      => $target,
        'port'        => $port_num,
        'time'        => $time,
        'method'      => $method,
        'concurrents' => $concurrents,
        'layer'       => $layer,
        'status'      => 'running',
        'server_id'   => null,
        'server_response' => null
    ];

    // Scheduled attack: stored in attacks.json with status="scheduled" so the slot
    // is reserved atomically under this same flock – prevents unlimited scheduling abuse.
    if ($want_schedule) {
        $new_attack['status']       = 'scheduled';
        $new_attack['scheduled_at'] = date('c', $scheduled_ts);
        // start_time is intentionally omitted; set by process_due_scheduled_attacks when it fires

        $attacks[] = $new_attack;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($attacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        json_response(['message' => 'Attack scheduled', 'attack' => $new_attack], 201);
    }

    $new_attack['start_time'] = date('c'); // set only for immediate (non-scheduled) attacks
    $attacks[] = $new_attack;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($attacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    // Dispatch to backend server
    $server = find_server_for_attack($method, $layer, $attacks, $now);
    if (!$server) {
        // No server found – check whether a matching server exists at all (ignoring slot limit)
        $any = find_server_for_attack($method, $layer, [], 0, true);
        if ($any) {
            json_error('All server slots are currently occupied. Please try again in a moment.', 503);
        }
        // No backend server configured for this layer/method – reject the attack
        json_error('No backend server is available for this layer/method. Please contact an administrator.', 503);
    }

    $dispatch = dispatch_to_server($server, $new_attack);
    $srv_resp = $dispatch['success'] ? 'ok' : ('error: ' . substr($dispatch['error'] ?: (string)$dispatch['status_code'], 0, 100));

    // Update attack record with server info (locked write to avoid race condition)
    $attacks_path2 = DATA_DIR . 'attacks.json';
    $fp2 = @fopen($attacks_path2, 'c+');
    if ($fp2) {
        flock($fp2, LOCK_EX);
        $c2 = '';
        while (!feof($fp2)) $c2 .= fread($fp2, 8192);
        $atk2 = json_decode($c2, true) ?: [];
        foreach ($atk2 as &$a) {
            if ($a['id'] === $new_attack['id']) {
                $a['server_id']       = $server['id'];
                $a['server_response'] = $srv_resp;
                break;
            }
        }
        unset($a);
        ftruncate($fp2, 0);
        rewind($fp2);
        fwrite($fp2, json_encode($atk2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp2);
        flock($fp2, LOCK_UN);
        fclose($fp2);
    }
    $new_attack['server_id'] = $server['id'];

    json_response(['message' => 'Attack launched', 'attack' => $new_attack], 201);
}

json_error('Method not allowed', 405);

// =================== Helper: find a compatible backend server ===================

/**
 * Find an available backend server for the given method/layer.
 *
 * @param string $method
 * @param string $layer
 * @param array  $running_attacks  Current in-memory attacks list (used for slot counting)
 * @param int    $now              Current unix timestamp
 * @param bool   $ignore_slots     When true, skip the slot-capacity check (used to detect "all full" vs "no server")
 */
function find_server_for_attack($method, $layer, $running_attacks = [], $now = 0, $ignore_slots = false) {
    $servers = read_json('servers.json');
    foreach ($servers as $server) {
        if (empty($server['enabled'])) continue;
        // Check layer compatibility
        $srv_layer = $server['layer'] ?? 'Layer4';
        if ($srv_layer !== $layer) continue;
        // Empty methods list = server accepts all methods
        $srv_methods = $server['methods'] ?? [];
        if (!empty($srv_methods) && !in_array($method, $srv_methods, true)) continue;

        if (!$ignore_slots) {
            // Count running attacks currently dispatched to this server
            $max_slots = intval($server['max_slots'] ?? PHP_INT_MAX);
            if ($max_slots > 0) {
                $used = 0;
                foreach ($running_attacks as $a) {
                    if (($a['server_id'] ?? null) !== $server['id']) continue;
                    $start = strtotime($a['start_time'] ?? '');
                    if ($start === false) continue; // skip if date is invalid
                    if (($start + intval($a['time'])) > $now) {
                        $used++;
                    }
                }
                if ($used >= $max_slots) continue; // server full – try next
            }
        }

        return $server;
    }
    return null;
}

// =================== Helper: dispatch GET request to backend server ===================

function dispatch_to_server($server, $attack) {
    $url = $server['api_url'] ?? '';
    if (empty($url)) {
        return ['success' => false, 'status_code' => 0, 'response' => '', 'error' => 'No URL configured'];
    }

    $replacements = [
        '{host}'        => urlencode($attack['target']),
        '{ip}'          => urlencode($attack['target']),
        '{port}'        => urlencode((string)$attack['port']),
        '{time}'        => urlencode((string)$attack['time']),
        '{method}'      => urlencode($attack['method']),
        '{concurrents}' => urlencode((string)$attack['concurrents']),
    ];
    $url = str_replace(array_keys($replacements), array_values($replacements), $url);

    if (!function_exists('curl_init')) {
        // Fallback: file_get_contents
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $response = @file_get_contents($url, false, $ctx);
        $ok = $response !== false;
        return ['success' => $ok, 'status_code' => 0, 'response' => (string)$response, 'error' => $ok ? '' : 'file_get_contents failed'];
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => VERIFY_BACKEND_SSL,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_USERAGENT      => 'NetStress/1.0',
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);

    return [
        'success'     => ($http_code >= 200 && $http_code < 300),
        'status_code' => $http_code,
        'response'    => (string)$response,
        'error'       => $error,
    ];
}

// =================== Scheduled Attack Processor ===================

function process_due_scheduled_attacks($user) {
    // Scheduled attacks are stored in attacks.json with status="scheduled".
    // This function promotes due ones to status="running" inside the same file,
    // keeping everything under one flock() so slot counts stay consistent.
    $atk_path = DATA_DIR . 'attacks.json';
    $fp = @fopen($atk_path, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);

    $content = '';
    while (!feof($fp)) $content .= fread($fp, 8192);
    $attacks = json_decode($content, true) ?: [];

    $now     = time();
    $changed = false;

    foreach ($attacks as &$a) {
        if (($a['user_id'] ?? '') !== $user['id']) continue;
        if (($a['status'] ?? '') !== 'scheduled') continue;

        $ts = isset($a['scheduled_at']) ? strtotime($a['scheduled_at']) : 0;
        if ($ts > 0 && $ts <= $now) {
            $a['status']     = 'running';
            $a['start_time'] = date('c');
            unset($a['scheduled_at']);
            $changed = true;
        }
    }
    unset($a);

    if ($changed) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($attacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
}
