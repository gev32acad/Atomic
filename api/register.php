<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    json_error('All fields are required');
}

if (strlen($username) < 3) {
    json_error('Username must be at least 3 characters');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Invalid email address');
}

if (strlen($password) < 6) {
    json_error('Password must be at least 6 characters');
}

$users = read_json('users.json');

// Check if username or email already exists
foreach ($users as $user) {
    if ($user['username'] === $username) {
        json_error('Username already exists');
    }
    if ($user['email'] === $email) {
        json_error('Email already exists');
    }
}

$new_user = [
    'id' => generate_id(),
    'username' => $username,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_BCRYPT),
    'plan' => 'Starter',
    'rule' => 'user',
    'join_date' => date('c'),
    'max_concurrents' => 1,
    'max_seconds' => 60,
    'expiration_date' => null,
    'api_key' => 'atomic_' . bin2hex(random_bytes(12))
];

$users[] = $new_user;
write_json('users.json', $users);

json_response(['message' => 'Account created successfully'], 201);
