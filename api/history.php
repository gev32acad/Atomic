<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$attacks = read_json('attacks.json');
$now = time();
$history = [];

// Get page params
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = min(50, max(1, intval($_GET['per_page'] ?? 20)));

// Filter attacks for this user (admins can see all with ?all=1)
foreach ($attacks as $attack) {
    if ($user['rule'] === 'admin' && !empty($_GET['all'])) {
        // Admin sees all
    } elseif ($attack['user_id'] !== $user['id']) {
        continue;
    }
    
    $start = strtotime($attack['start_time']);
    $duration = $attack['time'];
    $end_time = $start + $duration;
    
    $attack['end_time'] = date('c', $end_time);
    $attack['status'] = ($end_time > $now && $duration > 0) ? 'running' : 'completed';
    if ($duration <= 0) {
        $attack['status'] = 'stopped';
    }
    
    $history[] = $attack;
}

// Sort by start_time descending (newest first)
usort($history, function($a, $b) {
    return strtotime($b['start_time']) - strtotime($a['start_time']);
});

// Paginate
$total = count($history);
$total_pages = ceil($total / $per_page);
$offset = ($page - 1) * $per_page;
$history = array_slice($history, $offset, $per_page);

json_response([
    'attacks' => $history,
    'pagination' => [
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages
    ]
]);
