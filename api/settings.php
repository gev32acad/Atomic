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
    $settings = read_json('settings.json');
    // Mask the API key in the response
    if (!empty($settings['hub_api_key'])) {
        $settings['hub_api_key_set'] = true;
        $settings['hub_api_key'] = str_repeat('*', 8);
    } else {
        $settings['hub_api_key_set'] = false;
    }
    json_response($settings);
}

if ($method_req === 'POST') {
    verify_csrf_token();

    $settings = read_json('settings.json');

    $hub_api_url = trim($_POST['hub_api_url'] ?? '');
    $hub_api_key = $_POST['hub_api_key'] ?? '';

    if (!empty($hub_api_url) && (!filter_var($hub_api_url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $hub_api_url))) {
        json_error('Invalid Hub API URL. Must start with http:// or https://');
    }

    $settings['hub_api_url'] = $hub_api_url;

    // Only update the key if a real value was submitted (not the masked placeholder)
    if ($hub_api_key !== '' && !preg_match('/^\*+$/', $hub_api_key)) {
        $settings['hub_api_key'] = $hub_api_key;
    } elseif ($hub_api_key === '') {
        $settings['hub_api_key'] = '';
    }

    write_json('settings.json', $settings);
    json_response(['message' => 'Settings saved']);
}

json_error('Method not allowed', 405);
