<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$allowed_layers = ['Layer4', 'Layer7'];
$categories = read_json('categories.json');
$categories = normalize_categories($categories, $allowed_layers);
$categories = sync_categories_with_methods($categories);

if ($method_req === 'GET') {
    json_response($categories);
}

if (($user['role'] ?? '') !== 'admin') {
    json_error('Forbidden', 403);
}

if (in_array($method_req, ['POST', 'PUT', 'DELETE'], true)) {
    verify_csrf_token();
}

if ($method_req === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $layer = trim($_POST['layer'] ?? 'Layer4');

    if ($name === '') {
        json_error('Category name is required');
    }
    if (!in_array($layer, $allowed_layers, true)) {
        json_error('Layer must be Layer4 or Layer7');
    }
    if (category_exists($categories, $name, $layer)) {
        json_error('Category already exists for this layer');
    }

    $new_category = [
        'id' => generate_id(),
        'name' => $name,
        'layer' => $layer
    ];
    $categories[] = $new_category;
    write_json('categories.json', $categories);
    json_response($new_category, 201);
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $input['id'] ?? '';
    $name = trim($input['name'] ?? '');
    $layer = trim($input['layer'] ?? '');

    if ($id === '') {
        json_error('Category ID is required');
    }
    if ($name === '') {
        json_error('Category name is required');
    }
    if (!in_array($layer, $allowed_layers, true)) {
        json_error('Layer must be Layer4 or Layer7');
    }

    foreach ($categories as $c) {
        $is_different_id = (($c['id'] ?? '') !== $id);
        $is_same_name = (strcasecmp(trim($c['name'] ?? ''), $name) === 0);
        $is_same_layer = (($c['layer'] ?? '') === $layer);
        if ($is_different_id && $is_same_name && $is_same_layer) {
            json_error('Category already exists for this layer');
        }
    }

    $found = false;
    $old_name = '';
    $old_layer = '';
    foreach ($categories as &$category) {
        if (($category['id'] ?? '') === $id) {
            $old_name = trim($category['name'] ?? '');
            $old_layer = $category['layer'] ?? '';
            $category['name'] = $name;
            $category['layer'] = $layer;
            $found = true;
            break;
        }
    }
    unset($category);

    if (!$found) {
        json_error('Category not found', 404);
    }

    if ($old_name !== '' && ($old_name !== $name || $old_layer !== $layer)) {
        $methods = read_json('methods.json');
        $in_use_on_old_layer = false;
        foreach ($methods as $m) {
            $method_cat = trim($m['category'] ?? '');
            if (strcasecmp($method_cat, $old_name) !== 0) continue;
            if (method_matches_layer($m, $old_layer)) {
                $in_use_on_old_layer = true;
                break;
            }
        }

        if ($old_layer !== $layer && $in_use_on_old_layer) {
            json_error('Cannot change category layer while methods are using this category');
        }

        if ($old_name !== $name) {
            foreach ($methods as &$m) {
                $method_cat = trim($m['category'] ?? '');
                if (strcasecmp($method_cat, $old_name) !== 0) {
                    continue;
                }
                if (method_matches_layer($m, $old_layer)) {
                    $m['category'] = $name;
                }
            }
            unset($m);
            write_json('methods.json', $methods);
        }
    }

    write_json('categories.json', $categories);
    json_response(['message' => 'Category updated']);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $input['id'] ?? '';

    if ($id === '') {
        json_error('Category ID is required');
    }

    $target = null;
    foreach ($categories as $c) {
        if (($c['id'] ?? '') === $id) {
            $target = $c;
            break;
        }
    }
    if (!$target) {
        json_error('Category not found', 404);
    }

    $target_name = trim($target['name'] ?? '');
    $target_layer = $target['layer'] ?? 'Layer4';
    $methods = read_json('methods.json');
    foreach ($methods as $m) {
        if (strcasecmp(trim($m['category'] ?? ''), $target_name) !== 0) {
            continue;
        }
        if (method_matches_layer($m, $target_layer)) {
            json_error('Cannot delete category that is currently used by a method');
        }
    }

    $categories = array_values(array_filter($categories, function($c) use ($id) {
        return ($c['id'] ?? '') !== $id;
    }));

    write_json('categories.json', $categories);
    json_response(['message' => 'Category deleted']);
}

json_error('Method not allowed', 405);

function normalize_categories($categories, $allowed_layers) {
    if (!is_array($categories)) return [];
    $out = [];
    foreach ($categories as $c) {
        if (!is_array($c)) continue;
        $name = trim($c['name'] ?? '');
        $layer = $c['layer'] ?? '';
        if ($name === '' || !in_array($layer, $allowed_layers, true)) continue;
        $out[] = [
            'id' => (string)($c['id'] ?? generate_id()),
            'name' => $name,
            'layer' => $layer
        ];
    }
    return array_values($out);
}

function sync_categories_with_methods($categories) {
    $methods = read_json('methods.json');
    $changed = false;

    foreach ($methods as $m) {
        $name = trim((string)($m['category'] ?? ''));
        if ($name === '') continue;

        if (!empty($m['layer4']) && !category_exists($categories, $name, 'Layer4')) {
            $categories[] = ['id' => generate_id(), 'name' => $name, 'layer' => 'Layer4'];
            $changed = true;
        }
        if (!empty($m['layer7']) && !category_exists($categories, $name, 'Layer7')) {
            $categories[] = ['id' => generate_id(), 'name' => $name, 'layer' => 'Layer7'];
            $changed = true;
        }
    }

    if ($changed) {
        write_json('categories.json', $categories);
    }

    return $categories;
}

function category_exists($categories, $name, $layer) {
    foreach ($categories as $c) {
        if (($c['layer'] ?? '') === $layer && strcasecmp(trim($c['name'] ?? ''), $name) === 0) {
            return true;
        }
    }
    return false;
}

function method_matches_layer($method, $layer) {
    if ($layer === 'Layer7') {
        return !empty($method['layer7']);
    }
    return !empty($method['layer4']);
}
