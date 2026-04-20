<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_current_user();
if (!$user) {
    json_error('Invalid or expired token', 401);
}

json_response(['status' => 'valid', 'user_id' => $user['id']]);
