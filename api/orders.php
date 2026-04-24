<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user || $user['rule'] !== 'admin') {
    json_error('Forbidden', 403);
}

$method_req = $_SERVER['REQUEST_METHOD'];

if ($method_req === 'GET') {
    $orders = read_json('orders.json');
    usort($orders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    json_response($orders);
}

if (in_array($method_req, ['POST', 'PUT', 'DELETE'])) {
    verify_csrf_token();
}

if ($method_req === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['id'] ?? '';
    $action   = $input['action'] ?? ''; // 'approve' or 'reject'
    $note     = $input['note'] ?? '';

    if (empty($order_id) || !in_array($action, ['approve', 'reject'])) {
        json_error('Order ID and valid action (approve/reject) are required');
    }

    $orders = read_json('orders.json');
    $order  = null;
    foreach ($orders as &$o) {
        if ($o['id'] === $order_id) {
            $order = &$o;
            break;
        }
    }

    if (!$order) {
        json_error('Order not found', 404);
    }

    if ($order['status'] !== 'pending') {
        json_error('Order is already ' . $order['status']);
    }

    $order['status']     = $action === 'approve' ? 'approved' : 'rejected';
    $order['admin_note'] = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
    $order['updated_at'] = date('c');

    if ($action === 'approve') {
        // Upgrade user's plan
        $plans = read_json('plans.json');
        $plan  = null;
        foreach ($plans as $p) {
            if ($p['id'] === $order['plan_id']) {
                $plan = $p;
                break;
            }
        }

        if ($plan) {
            $users = read_json('users.json');
            foreach ($users as &$u) {
                if ($u['id'] === $order['user_id']) {
                    $duration_days = isset($plan['duration_days']) ? intval($plan['duration_days']) : 30;
                    $u['plan']             = $plan['name'];
                    $u['max_concurrents']  = $plan['max_concurrents'];
                    $u['max_seconds']      = $plan['max_seconds'];
                    $u['expiration_date']  = date('c', strtotime("+{$duration_days} days"));
                    break;
                }
            }
            write_json('users.json', $users);
        }
    }

    write_json('orders.json', $orders);
    json_response(['message' => 'Order ' . $order['status']]);
}

if ($method_req === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['id'] ?? '';

    if (empty($order_id)) {
        json_error('Order ID is required');
    }

    $orders = read_json('orders.json');
    $orders = array_values(array_filter($orders, function($o) use ($order_id) {
        return $o['id'] !== $order_id;
    }));
    write_json('orders.json', $orders);

    json_response(['message' => 'Order deleted']);
}

json_error('Method not allowed', 405);
