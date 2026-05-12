<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

// Don't expose password or api_key in profile response (#6)
$profile = $user;
unset($profile['password'], $profile['api_key']);

json_response($profile);
