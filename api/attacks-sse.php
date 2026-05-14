<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$user = get_authenticated_user();
if (!$user) {
    http_response_code(401);
    exit;
}

// Release the PHP session lock immediately so other requests from the same
// browser are not blocked while this long-running SSE stream is open.
session_write_close();

// Disable output compression and buffering
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
    @apache_setenv('dont-vary', '1');
}
@ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

set_time_limit(55);

$last_hash  = '';
$start_time = time();

while (!connection_aborted() && (time() - $start_time) < 50) {
    $attacks = read_json('attacks.json');
    $now     = time();
    $running = [];

    foreach ($attacks as $attack) {
        if (($attack['user_id'] ?? '') !== $user['id']) continue;
        $start     = strtotime($attack['start_time'] ?? '');
        $duration  = intval($attack['time'] ?? 0);
        $remaining = ($start + $duration) - $now;
        if ($remaining > 0) {
            $attack['remaining'] = $remaining;
            $running[] = $attack;
        }
    }

    $hash = md5(json_encode(array_column($running, 'id')));

    if ($hash !== $last_hash) {
        echo 'data: ' . json_encode($running) . "\n\n";
        flush();
        $last_hash = $hash;
    } else {
        // Keep-alive comment every ~10 s to prevent proxy timeouts
        if ((time() - $start_time) % 10 === 0) {
            echo ": ping\n\n";
            flush();
        }
    }

    sleep(1);
}

// Tell browser to reconnect after 3 s
echo "retry: 3000\n\n";
flush();
