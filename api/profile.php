<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$user = get_authenticated_user();
if (!$user) {
    json_error('Unauthorized', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'regenerate_api_key') {
        // Generate a new key: netstress_ + 24 hex chars = 34 chars total
        $new_key = 'netstress_' . bin2hex(random_bytes(12));

        $path = DATA_DIR . 'users.json';
        $fp = @fopen($path, 'c+');
        if (!$fp) {
            json_error('Server error', 500);
        }
        flock($fp, LOCK_EX);
        $content = '';
        while (!feof($fp)) $content .= fread($fp, 8192);
        $users = json_decode($content, true) ?: [];

        foreach ($users as &$u) {
            if ($u['id'] === $user['id']) {
                $u['api_key'] = $new_key;
                break;
            }
        }
        unset($u);

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        json_response(['api_key' => $new_key]);
    }

    json_error('Unknown action', 400);
}

// GET: return profile (without sensitive fields)
// Don't expose password or api_key in profile response (#6)
$profile = $user;
unset($profile['password'], $profile['api_key']);

json_response($profile);
