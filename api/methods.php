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
$categories = normalize_categories_for_lookup(read_json('categories.json'));
$categories = augment_categories_with_methods($categories, $methods);

if ($method_req === 'GET') {
    json_response($methods);
}

// Admin-only operations
if ($user['role'] !== 'admin') {
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
    $category = trim($_POST['category'] ?? 'Other');
    
    if (empty($name)) {
        json_error('Method name is required');
    }
    if (!($layer4 xor $layer7)) {
        json_error('Method must belong to exactly one layer (Layer4 or Layer7)');
    }
    if (!category_allowed_for_method($category, $layer4, $layer7, $categories)) {
        json_error('Selected category is not valid for the chosen layer');
    }
    
    $new_method = [
        'id' => generate_id(),
        'name' => $name,
        'description' => $description,
        'layer7' => $layer7,
        'layer4' => $layer4,
        'amplification' => $amplification,
        'premium' => $premium,
        'proxy' => $proxy,
        'category' => $category
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
            $new_layer7 = array_key_exists('layer7', $input) ? (bool)$input['layer7'] : !empty($method['layer7']);
            $new_layer4 = array_key_exists('layer4', $input) ? (bool)$input['layer4'] : !empty($method['layer4']);
            $new_category = trim((string)($input['category'] ?? ($method['category'] ?? 'Other')));

            if (!($new_layer4 xor $new_layer7)) {
                json_error('Method must belong to exactly one layer (Layer4 or Layer7)');
            }
            if (!category_allowed_for_method($new_category, $new_layer4, $new_layer7, $categories)) {
                json_error('Selected category is not valid for the chosen layer');
            }

            $method['name'] = $input['name'] ?? $method['name'];
            $method['description'] = $input['description'] ?? $method['description'];
            $method['layer7'] = $new_layer7;
            $method['layer4'] = $new_layer4;
            $method['amplification'] = $input['amplification'] ?? $method['amplification'];
            $method['premium'] = $input['premium'] ?? $method['premium'];
            $method['proxy'] = $input['proxy'] ?? $method['proxy'];
            $method['category'] = $new_category;
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

function normalize_categories_for_lookup($categories) {
    if (!is_array($categories)) return [];
    $out = [];
    foreach ($categories as $c) {
        if (!is_array($c)) continue;
        $name = trim((string)($c['name'] ?? ''));
        $layer = (string)($c['layer'] ?? '');
        if ($name === '' || !in_array($layer, ['Layer4', 'Layer7'], true)) continue;
        $out[] = ['name' => $name, 'layer' => $layer];
    }
    return $out;
}

function category_allowed_for_method($category, $layer4, $layer7, $categories) {
    $category = trim((string)$category);
    if ($category === '') return false;
    foreach ($categories as $c) {
        if (strcasecmp($c['name'], $category) !== 0) continue;
        if ($layer4 && !$layer7 && $c['layer'] === 'Layer4') return true;
        if ($layer7 && !$layer4 && $c['layer'] === 'Layer7') return true;
    }
    return false;
}

function augment_categories_with_methods($categories, $methods) {
    foreach ($methods as $m) {
        $name = trim((string)($m['category'] ?? ''));
        if ($name === '') continue;
        if (!empty($m['layer4']) && !has_category_for_layer($categories, $name, 'Layer4')) {
            $categories[] = ['name' => $name, 'layer' => 'Layer4'];
        }
        if (!empty($m['layer7']) && !has_category_for_layer($categories, $name, 'Layer7')) {
            $categories[] = ['name' => $name, 'layer' => 'Layer7'];
        }
    }

    return $categories;
}

function has_category_for_layer($categories, $name, $layer) {
    foreach ($categories as $c) {
        if ($c['layer'] === $layer && strcasecmp($c['name'], $name) === 0) {
            return true;
        }
    }
    return false;
}
