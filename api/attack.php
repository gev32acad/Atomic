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
    $action = $_POST['action'] ?? 'start';
    
    if ($action === 'stop') {
        $attack_id = $_POST['attack_id'] ?? '';
        if (empty($attack_id)) {
            json_error('Attack ID is required');
        }
        
        $attacks = read_json('attacks.json');
        foreach ($attacks as &$attack) {
            if ($attack['id'] === $attack_id && $attack['user_id'] === $user['id']) {
                $attack['time'] = 0; // Set time to 0 to mark as stopped
                break;
            }
        }
        write_json('attacks.json', $attacks);
        json_response(['message' => 'Attack stopped']);
    }
    
    // Start attack
    $target = $_POST['target'] ?? '';
    $port = $_POST['port'] ?? '80';
    $time = intval($_POST['time'] ?? 0);
    $method = $_POST['method'] ?? '';
    $concurrents = intval($_POST['concurrents'] ?? 1);
    $layer = $_POST['layer'] ?? 'Layer4';
    
    if (empty($target) || empty($method) || $time <= 0) {
        json_error('Target, method, and time are required');
    }
    
    if ($time > $user['max_seconds']) {
        json_error('Time exceeds your plan limit (' . $user['max_seconds'] . 's max)');
    }
    
    if ($concurrents > $user['max_concurrents']) {
        json_error('Concurrents exceed your plan limit (' . $user['max_concurrents'] . ' max)');
    }
    
    // Check concurrent running attacks
    $attacks = read_json('attacks.json');
    $now = time();
    $running_count = 0;
    foreach ($attacks as $attack) {
        if ($attack['user_id'] === $user['id']) {
            $start = strtotime($attack['start_time']);
            $duration = $attack['time'];
            if (($start + $duration) > $now) {
                $running_count++;
            }
        }
    }
    
    if ($running_count >= $user['max_concurrents']) {
        json_error('Maximum concurrent attacks reached');
    }
    
    // Verify method exists
    $methods = read_json('methods.json');
    $valid_method = false;
    foreach ($methods as $m) {
        if ($m['name'] === $method) {
            $valid_method = true;
            if ($m['premium'] && $user['plan'] === 'Starter') {
                json_error('This method requires a premium plan');
            }
            break;
        }
    }
    
    if (!$valid_method) {
        json_error('Invalid method');
    }
    
    $new_attack = [
        'id' => generate_id(),
        'user_id' => $user['id'],
        'target' => $target,
        'port' => $port,
        'time' => $time,
        'method' => $method,
        'concurrents' => $concurrents,
        'layer' => $layer,
        'start_time' => date('c'),
        'status' => 'running'
    ];
    
    $attacks[] = $new_attack;
    write_json('attacks.json', $attacks);
    
    json_response(['message' => 'Attack launched', 'attack' => $new_attack], 201);
}

json_error('Method not allowed', 405);
