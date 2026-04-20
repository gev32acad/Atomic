<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$methods = read_json('methods.json');

if ($method_req === 'GET') {
    json_response($methods);
}

// Admin-only operations
if ($user['rule'] !== 'admin') {
    json_error('Forbidden', 403);
}

// CSRF check for state-changing requests (#1)
if (in_array($method_req, ['POST', 'PUT', 'DELETE'])) {
    verify_csrf_token();
}

if ($method_req === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $layer7 = filter_var($_POST['layer7'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $layer4 = filter_var($_POST['layer4'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $amplification = filter_var($_POST['amplification'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $premium = filter_var($_POST['premium'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $proxy = filter_var($_POST['proxy'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    
    if (empty($name)) {
        json_error('Method name is required');
    }
    
    $new_method = [
        'id' => generate_id(),
        'name' => $name,
        'description' => $description,
        'layer7' => $layer7,
        'layer4' => $layer4,
        'amplification' => $amplification,
        'premium' => $premium,
        'proxy' => $proxy
    ];
    
    $methods[] = $new_method;
    write_json('methods.json', $methods);
    json_response($new_method, 201);
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('Method ID is required');
    }
    
    foreach ($methods as &$method) {
        if ($method['id'] === $id) {
            $method['name'] = $input['name'] ?? $method['name'];
            $method['description'] = $input['description'] ?? $method['description'];
            $method['layer7'] = $input['layer7'] ?? $method['layer7'];
            $method['layer4'] = $input['layer4'] ?? $method['layer4'];
            $method['amplification'] = $input['amplification'] ?? $method['amplification'];
            $method['premium'] = $input['premium'] ?? $method['premium'];
            $method['proxy'] = $input['proxy'] ?? $method['proxy'];
            break;
        }
    }
    
    write_json('methods.json', $methods);
    json_response(['message' => 'Method updated']);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('Method ID is required');
    }
    
    $methods = array_values(array_filter($methods, function($m) use ($id) {
        return $m['id'] !== $id;
    }));
    
    write_json('methods.json', $methods);
    json_response(['message' => 'Method deleted']);
}

json_error('Method not allowed', 405);
