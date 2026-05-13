<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

// Invalidate current session token so it cannot be reused (#4)
if (!empty($_SESSION['token'])) {
    add_token_to_blacklist($_SESSION['token']);
}

session_regenerate_id(true);
session_destroy();
header('Location: login.php');
exit;
