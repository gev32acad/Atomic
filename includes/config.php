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
    $data = json_decode($content, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Failed to parse JSON from $file: " . json_last_error_msg());
        return [];
    }
    return $data ?: [];
}

function write_json($file, $data) {
    $path = DATA_DIR . $file;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("Failed to encode JSON for $file: " . json_last_error_msg());
        return false;
    }
    $result = file_put_contents($path, $json, LOCK_EX);
    if ($result === false) {
        error_log("Failed to write JSON file: $path");
        return false;
    }
    return true;
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
