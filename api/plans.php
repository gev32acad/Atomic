<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_current_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$plans = read_json('plans.json');

if ($method_req === 'GET') {
    json_response($plans);
}

// Admin-only operations
if ($user['rule'] !== 'admin') {
    json_error('Forbidden', 403);
}

if ($method_req === 'POST') {
    $name = $_POST['name'] ?? '';
    $max_concurrents = intval($_POST['max_concurrents'] ?? 1);
    $max_seconds = intval($_POST['max_seconds'] ?? 60);
    $min_seconds = intval($_POST['min_seconds'] ?? 10);
    $premium = filter_var($_POST['premium'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $api_access = filter_var($_POST['api_access'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    if (empty($name)) {
        json_error('Plan name is required');
    }
    
    $new_plan = [
        'id' => generate_id(),
        'name' => $name,
        'max_concurrents' => $max_concurrents,
        'max_seconds' => $max_seconds,
        'min_seconds' => $min_seconds,
        'premium' => $premium,
        'api_access' => $api_access
    ];
    
    $plans[] = $new_plan;
    write_json('plans.json', $plans);
    json_response($new_plan, 201);
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('Plan ID is required');
    }
    
    foreach ($plans as &$plan) {
        if ($plan['id'] === $id) {
            $plan['name'] = $input['name'] ?? $plan['name'];
            $plan['max_concurrents'] = $input['max_concurrents'] ?? $plan['max_concurrents'];
            $plan['max_seconds'] = $input['max_seconds'] ?? $plan['max_seconds'];
            $plan['min_seconds'] = $input['min_seconds'] ?? $plan['min_seconds'];
            $plan['premium'] = $input['premium'] ?? $plan['premium'];
            $plan['api_access'] = $input['api_access'] ?? $plan['api_access'];
            break;
        }
    }
    
    write_json('plans.json', $plans);
    json_response(['message' => 'Plan updated']);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('Plan ID is required');
    }
    
    $plans = array_values(array_filter($plans, function($p) use ($id) {
        return $p['id'] !== $id;
    }));
    
    write_json('plans.json', $plans);
    json_response(['message' => 'Plan deleted']);
}

json_error('Method not allowed', 405);
