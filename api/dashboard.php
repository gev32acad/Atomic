<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$attacks = read_json('attacks.json');

// Count running attacks (those that haven't expired)
$now = time();
$running = 0;
foreach ($attacks as $attack) {
    $start = strtotime($attack['start_time']);
    $duration = $attack['time'];
    if (($start + $duration) > $now) {
        $running++;
    }
}

// Attacks last 7 days
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    $count = 0;
    foreach ($attacks as $attack) {
        if (date('Y-m-d', strtotime($attack['start_time'])) === $date) {
            $count++;
        }
    }
    $days[] = ['name' => $day_name, 'attacks' => $count];
}

// Active servers from servers.json (#12)
$servers = read_json('servers.json');
$active_servers = count(array_filter($servers, function($s) { return !empty($s['enabled']); }));

$response = [
    'active_servers'      => $active_servers,
    'total_attacks'       => count($attacks),
    'running_attacks'     => $running,
    'attacks_last_7_days' => $days,
];

// Restrict sensitive platform stats to admins only (#11)
if ($user['role'] === 'admin') {
    $users = read_json('users.json');
    $response['registered_users'] = count($users);
} else {
    $response['registered_users'] = null;
}

json_response($response);
