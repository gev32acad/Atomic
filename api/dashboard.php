<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$attacks = read_json('attacks.json');

// Only consider this user's attacks
$user_attacks = array_values(array_filter($attacks, function($a) use ($user) {
    return isset($a['user_id']) && $a['user_id'] === $user['id'];
}));

// Count running attacks for current user
$now = time();
$running = 0;
foreach ($user_attacks as $attack) {
    if (empty($attack['start_time'])) continue;
    $start = strtotime($attack['start_time']);
    $duration = intval($attack['time'] ?? 0);
    if ($start && ($start + $duration) > $now) {
        $running++;
    }
}

// Attacks last 7 days (current user only)
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime("-$i days"));
    $count = 0;
    foreach ($user_attacks as $attack) {
        if (empty($attack['start_time'])) continue;
        if (date('Y-m-d', strtotime($attack['start_time'])) === $date) {
            $count++;
        }
    }
    $days[] = ['name' => $day_name, 'attacks' => $count];
}

// Active servers from servers.json
$servers = read_json('servers.json');
$active_servers = count(array_filter($servers, function($s) { return !empty($s['enabled']); }));

$response = [
    'active_servers'      => $active_servers,
    'total_attacks'       => count($user_attacks),
    'running_attacks'     => $running,
    'attacks_last_7_days' => $days,
];

json_response($response);
