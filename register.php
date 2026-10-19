<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();

// Works whether or not the mbstring extension is enabled on the host (some cheap shared hosting omits it).
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return strlen($s); }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
}
require_csrf();

$input = read_json_object(20000);
foreach ($input as $value) {
    if (!is_string($value)) json_response(['ok' => false, 'error' => 'Fields must be strings.'], 422);
}

$name  = trim(strip_tags((string)($input['name'] ?? '')));
$email = trim(strtolower((string)($input['email'] ?? '')));
$niche = trim(strip_tags((string)($input['niche'] ?? 'Fitness / Strength')));
$pass  = (string)($input['password'] ?? '');

$allowedNiches = ['Fitness / Strength', 'Nutrition', 'Life & Mindset', 'Business / Executive', 'Other'];
if (!in_array($niche, $allowedNiches, true)) $niche = 'Other';

// ---- Validation (mirrors the client-side checks, but this is the real gate) ----
if ($name === '' || mb_strlen($name) > 80) {
    json_response(['ok' => false, 'field' => 'name', 'error' => 'Enter a name (up to 80 characters).'], 422);
}
if ($email === '' || mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'field' => 'email', 'error' => "That email doesn't look right."], 422);
}
if (mb_strlen($pass) < 10 || strlen($pass) > 72) {
    json_response(['ok' => false, 'field' => 'password', 'error' => 'Use 10 or more characters, up to 72 bytes.'], 422);
}
// Basic strength check: block the most common weak passwords / all-one-character-type passwords
$commonPasswords = ['password123', '1234567890', 'qwertyuiop', 'password1234', 'letmein12345'];
if (in_array(strtolower($pass), $commonPasswords, true)) {
    json_response(['ok' => false, 'field' => 'password', 'error' => 'That password is too common. Choose something harder to guess.'], 422);
}

if (too_many_attempts($email)) json_response(['ok' => false, 'error' => 'Too many attempts. Try again later.'], 429);
record_failed_attempt($email);
$pdo = penguins_db();

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['ok' => false, 'field' => 'email', 'error' => 'An account with that email already exists. Try logging in instead.'], 409);
}

// Argon2id: the strongest hashing algorithm PHP ships with, falls back to bcrypt if unavailable.
$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
$hash = password_hash($pass, $algo);

$pdo->beginTransaction();
$stmt = $pdo->prepare('INSERT INTO users (name, email, niche, password_hash) VALUES (?, ?, ?, ?)');
$stmt->execute([$name, $email, $niche, $hash]);
$userId = (int)$pdo->lastInsertId();

// Seed a genuinely blank dashboard — the coach's own name, no demo clients or numbers.
$blank = penguins_blank_dashboard($name, $email, $niche);
$stmt = $pdo->prepare('INSERT INTO user_data (user_id, data_json) VALUES (?, ?)');
$stmt->execute([$userId, json_encode($blank)]);

$pdo->commit();

// Regenerate the session ID on privilege change (prevents session fixation attacks)
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $name;
$_SESSION['ua_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

json_response(['ok' => true, 'name' => $name]);

// ==========================================
// The starting shape of a brand new coach's dashboard data.
// Keeps the exact same structure the dashboard JS already expects,
// just with every list empty instead of pre-filled demo content.
// ==========================================
function penguins_blank_dashboard($name, $email, $niche) {
    return [
        'seenWelcome' => false,
        'colorMode' => 'dark',
        'theme' => ['accent' => '#6C5CE7', 'gold' => '#F5B942'],
        'lang' => 'en',
        'account' => ['name' => $name, 'email' => $email, 'niche' => $niche, 'bio' => '', 'rate' => 0, 'memberSince' => date('M Y')],
        'pendingAccountChange' => null,
        'clients' => [], 'leads' => [], 'invoices' => [], 'plans' => [], 'planTemplates' => [],
        'sessions' => [], 'slots' => [], 'feedback' => [], 'submissions' => [], 'pointsClients' => [],
        'readiness' => [], 'cohorts' => [], 'automations' => [], 'contracts' => [], 'community' => [],
        'shop' => [], 'library' => [], 'chats' => new stdClass(), 'activeChat' => null,
        'nutritionTargets' => new stdClass(), 'nutritionLogs' => new stdClass(),
        'sessionNotes' => new stdClass(), 'clientPRs' => new stdClass(), 'clientGoals' => new stdClass(),
        'tasks' => [], 'referrals' => ['clicks' => 0, 'signups' => 0],
        'businessGoals' => ['revenueTarget' => 0, 'clientsTarget' => 0],
    ];
}