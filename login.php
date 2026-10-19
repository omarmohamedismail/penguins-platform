<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
}
require_csrf();

$input = read_json_object(5000);
foreach ($input as $value) {
    if (!is_string($value)) json_response(['ok' => false, 'error' => 'Fields must be strings.'], 422);
}
$email = trim(strtolower((string)($input['email'] ?? '')));
$pass  = (string)($input['password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'field' => 'email', 'error' => "That email doesn't look right."], 422);
}
if ($pass === '' || strlen($pass) > 72) {
    json_response(['ok' => false, 'field' => 'password', 'error' => 'Enter your password.'], 422);
}

// ---- Brute-force protection ----
if (too_many_attempts($email)) {
    json_response(['ok' => false, 'error' => 'Too many attempts. Wait 15 minutes and try again.'], 429);
}

$pdo = penguins_db();
$stmt = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Always run password_verify even when no user is found (against a dummy hash) so response
// timing doesn't reveal whether the email exists (timing side-channel).
$dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
$hashToCheck = $user['password_hash'] ?? $dummyHash;
$passwordOk = password_verify($pass, $hashToCheck);

if (!$user || !$passwordOk) {
    record_failed_attempt($email);
    json_response(['ok' => false, 'field' => 'password', 'error' => 'Email or password is incorrect.'], 401);
}

// Transparently upgrade older hashes to Argon2id on successful login
$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
if (password_needs_rehash($user['password_hash'], $algo)) {
    $newHash = password_hash($pass, $algo);
    $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upd->execute([$newHash, $user['id']]);
}

clear_attempts($email);

// Regenerate the session ID on login (prevents session fixation attacks)
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['ua_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

json_response(['ok' => true, 'name' => $user['name']]);