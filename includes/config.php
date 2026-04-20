<?php
session_start();

define('DATA_DIR', __DIR__ . '/../data/');
define('SITE_NAME', 'AtomicStresser');
define('TOKEN_SECRET', 'atomic_secret_key_change_me');

// Helper functions for JSON data
function read_json($file) {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function write_json($file, $data) {
    $path = DATA_DIR . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generate_id() {
    return uniqid() . bin2hex(random_bytes(4));
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function json_error($message, $code = 400) {
    json_response(['detail' => $message], $code);
}
