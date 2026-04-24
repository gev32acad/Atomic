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
    // Return current user's orders
    $orders = read_json('orders.json');
    $my_orders = array_values(array_filter($orders, function($o) use ($user) {
        return $o['user_id'] === $user['id'];
    }));
    // Sort by created_at descending
    usort($my_orders, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    json_response($my_orders);
}

if ($method_req === 'POST') {
    verify_csrf_token();

    $plan_id = $_POST['plan_id'] ?? '';
    $crypto  = strtoupper(trim($_POST['crypto'] ?? ''));
    $amount  = $_POST['amount'] ?? '';

    if (empty($plan_id) || empty($crypto) || empty($amount)) {
        json_error('plan_id, crypto and amount are required');
    }

    $allowed_cryptos = ['BTC', 'ETH', 'LTC', 'XMR'];
    if (!in_array($crypto, $allowed_cryptos)) {
        json_error('Invalid crypto currency');
    }

    $plans = read_json('plans.json');
    $plan = null;
    foreach ($plans as $p) {
        if ($p['id'] === $plan_id) {
            $plan = $p;
            break;
        }
    }

    if (!$plan) {
        json_error('Plan not found');
    }

    if ($plan['price'] == 0) {
        json_error('Free plans cannot be purchased');
    }

    // Rate limit: max 5 orders per user per hour
    $orders = read_json('orders.json');
    $one_hour_ago = time() - 3600;
    $recent = array_filter($orders, function($o) use ($user, $one_hour_ago) {
        return $o['user_id'] === $user['id'] && strtotime($o['created_at']) > $one_hour_ago;
    });
    if (count($recent) >= 5) {
        json_error('Too many orders. Please wait before submitting another.', 429);
    }

    $order_id = 'ORD-' . strtoupper(bin2hex(random_bytes(5)));

    $new_order = [
        'id'          => $order_id,
        'user_id'     => $user['id'],
        'username'    => $user['username'],
        'plan_id'     => $plan['id'],
        'plan_name'   => $plan['name'],
        'price_usd'   => $plan['price'],
        'crypto'      => $crypto,
        'amount'      => $amount,
        'status'      => 'pending',
        'created_at'  => date('c'),
        'updated_at'  => date('c'),
        'admin_note'  => ''
    ];

    $orders[] = $new_order;
    write_json('orders.json', $orders);

    json_response(['message' => 'Order submitted', 'order_id' => $order_id], 201);
}

json_error('Method not allowed', 405);
