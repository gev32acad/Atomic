<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user || $user['role'] !== 'admin') {
    json_error('Forbidden', 403);
}

$method_req = $_SERVER['REQUEST_METHOD'];
$users = read_json('users.json');

if ($method_req === 'GET') {
    // Return users without passwords or api keys
    $safe_users = array_map(function($u) {
        unset($u['password'], $u['api_key']);
        return $u;
    }, $users);
    json_response(array_values($safe_users));
}

// CSRF check for state-changing requests (#1)
if (in_array($method_req, ['POST', 'PUT', 'DELETE'])) {
    verify_csrf_token();
}

if ($method_req === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $plan = $_POST['plan'] ?? 'Starter';
    $role = $_POST['role'] ?? 'user';
    $max_concurrents = intval($_POST['max_concurrents'] ?? 1);
    $max_seconds = intval($_POST['max_seconds'] ?? 60);
    $expiration_date = $_POST['expiration_date'] ?? null;
    
    if (empty($username) || empty($email) || empty($password)) {
        json_error('Username, email, and password are required');
    }

    // Validate username (#10)
    if (strlen($username) < 3 || strlen($username) > 20) {
        json_error('Username must be between 3 and 20 characters');
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        json_error('Username can only contain letters, numbers, and underscores');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Invalid email address');
    }
    if (strlen($password) < 6) {
        json_error('Password must be at least 6 characters');
    }
    if (!in_array($role, ['user', 'admin'], true)) {
        json_error('Invalid role');
    }
    
    // Check duplicates
    foreach ($users as $u) {
        if ($u['username'] === $username) json_error('Username already exists');
        if ($u['email'] === $email) json_error('Email already exists');
    }
    
    // Auto-sync plan limits (#17)
    $plan_limits = get_plan_limits($plan);
    if ($plan_limits) {
        $max_concurrents = $plan_limits['max_concurrents'];
        $max_seconds = $plan_limits['max_seconds'];
    }
    
    $new_user = [
        'id' => generate_id(),
        'username' => $username,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_BCRYPT),
        'plan' => $plan,
        'role' => $role,
        'join_date' => date('c'),
        'max_concurrents' => $max_concurrents,
        'max_seconds' => $max_seconds,
        'expiration_date' => $expiration_date ?: null,
        'api_key' => 'atomic_' . bin2hex(random_bytes(12))
    ];
    
    $users[] = $new_user;
    write_json('users.json', $users);
    
    $safe = $new_user;
    unset($safe['password']);
    json_response($safe, 201);
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('User ID is required');
    }

    // Validate updated fields
    if (isset($input['username'])) {
        $new_username = $input['username'];
        if (strlen($new_username) < 3 || strlen($new_username) > 20) {
            json_error('Username must be between 3 and 20 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            json_error('Username can only contain letters, numbers, and underscores');
        }
        // Uniqueness check (exclude the user being edited)
        foreach ($users as $u) {
            if ($u['id'] !== $id && $u['username'] === $new_username) {
                json_error('Username already exists');
            }
        }
    }
    if (isset($input['email'])) {
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            json_error('Invalid email address');
        }
        // Uniqueness check (exclude the user being edited)
        foreach ($users as $u) {
            if ($u['id'] !== $id && $u['email'] === $input['email']) {
                json_error('Email already exists');
            }
        }
    }
    if (isset($input['rule']) && !in_array($input['rule'], ['user', 'admin'], true)) {
        json_error('Invalid role');
    }
    
    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            if (isset($input['username'])) $u['username'] = $input['username'];
            if (isset($input['email'])) $u['email'] = $input['email'];
            if (!empty($input['password'])) $u['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            // Accept both 'role' (new) and 'rule' (legacy) keys; 'role' takes precedence
            $new_role = $input['role'] ?? $input['rule'] ?? null;
            if ($new_role !== null) $u['role'] = $new_role;
            if (array_key_exists('expiration_date', $input)) $u['expiration_date'] = $input['expiration_date'] ?: null;
            
            // Auto-sync limits when plan changes (#17)
            if (isset($input['plan']) && $input['plan'] !== $u['plan']) {
                $u['plan'] = $input['plan'];
                $plan_limits = get_plan_limits($u['plan']);
                if ($plan_limits) {
                    $u['max_concurrents'] = $plan_limits['max_concurrents'];
                    $u['max_seconds'] = $plan_limits['max_seconds'];
                }
            } else {
                // Manual override only if plan didn't change
                if (isset($input['max_concurrents'])) $u['max_concurrents'] = intval($input['max_concurrents']);
                if (isset($input['max_seconds'])) $u['max_seconds'] = intval($input['max_seconds']);
            }
            break;
        }
    }
    
    write_json('users.json', $users);
    json_response(['message' => 'User updated']);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';
    
    if (empty($id)) {
        json_error('User ID is required');
    }
    
    // Prevent self-deletion
    if ($id === $user['id']) {
        json_error('Cannot delete your own account');
    }
    
    $users = array_values(array_filter($users, function($u) use ($id) {
        return $u['id'] !== $id;
    }));
    
    write_json('users.json', $users);
    json_response(['message' => 'User deleted']);
}

json_error('Method not allowed', 405);

// Helper: Get plan limits from plans.json (#17)
function get_plan_limits($plan_name) {
    $plans = read_json('plans.json');
    foreach ($plans as $plan) {
        if ($plan['name'] === $plan_name) {
            return [
                'max_concurrents' => intval($plan['max_concurrents']),
                'max_seconds' => intval($plan['max_seconds'])
            ];
        }
    }
    return null;
}
