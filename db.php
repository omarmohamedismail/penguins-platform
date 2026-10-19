<?php
// ==========================================
// PENGUINS — Database connection (MySQL) + security bootstrap
// ==========================================
//
// اتصال قاعدة البيانات — نفس الإعدادات اللي هتستخدمها لو فتحت phpMyAdmin على جهازك
// (لو بتستخدم XAMPP/WAMP/Laragon القيم دي شغالة زي ما هي من غير أي تعديل)
// ------------------------------------------
// Optional local file stays outside version control. Local constants override environment variables.
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) require $localConfig;
foreach (['DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_NAME' => 'penguins_coaching', 'DB_USER' => 'root', 'DB_PASS' => ''] as $key => $fallback) {
    if (!defined($key)) define($key, getenv($key) !== false ? getenv($key) : $fallback);
}

set_exception_handler(function (Throwable $error) {
    error_log('Penguins: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => 'The service is temporarily unavailable. Please try again.'], 500);
});

// ---- Never leak errors/stack traces to the browser ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return strlen($s); }
}

// ---- Hardened session cookie settings (must run before session_start) ----
function penguins_secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('penguins_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (!empty($_SESSION['user_id'])) {
        $ua = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($_SESSION['ua_hash'])) {
            $_SESSION['ua_hash'] = $ua;
        } elseif (!hash_equals($_SESSION['ua_hash'], $ua)) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
    }
}

function penguins_security_headers() {
    header('Cache-Control: no-store, private');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.tailwindcss.com https://cdn.jsdelivr.net 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src https://fonts.gstatic.com; img-src * data:; connect-src 'self';");
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function penguins_db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsnBase = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsnBase . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $opts);

    return $pdo;
}

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function require_login() {
    penguins_secure_session_start();
    if (empty($_SESSION['user_id'])) {
        json_response(['ok' => false, 'error' => 'Not logged in.'], 401);
    }
    return $_SESSION['user_id'];
}

// ==========================================
// CSRF protection
// ==========================================
function csrf_token() {
    penguins_secure_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function require_csrf() {
    penguins_secure_session_start();
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($sent === '' || $expected === '' || !hash_equals($expected, $sent)) {
        json_response(['ok' => false, 'error' => 'Security check failed. Please refresh the page and try again.'], 403);
    }
}

// ==========================================
// Brute-force / rate limiting
// ==========================================
function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
function too_many_attempts($email) {
    $pdo = penguins_db();
    $windowStart = time() - (15 * 60);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE (email = ? OR ip = ?) AND attempted_at > ?');
    $stmt->execute([strtolower($email), client_ip(), $windowStart]);
    return (int)$stmt->fetchColumn() >= 6;
}
function record_failed_attempt($email) {
    $pdo = penguins_db();
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)');
    $stmt->execute([strtolower($email), client_ip(), time()]);
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < " . (time() - 3600));
}
function clear_attempts($email) {
    $pdo = penguins_db();
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = ? AND ip = ?');
    $stmt->execute([strtolower($email), client_ip()]);
}
function read_json_object($limit) {
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        json_response(['ok' => false, 'error' => 'Use application/json.'], 415);
    }
    $raw = file_get_contents('php://input', false, null, 0, $limit + 1);
    if (strlen($raw) > $limit) json_response(['ok' => false, 'error' => 'Request is too large.'], 413);
    $object = json_decode($raw);
    if (json_last_error() !== JSON_ERROR_NONE || !is_object($object)) {
        json_response(['ok' => false, 'error' => 'Expected a JSON object.'], 400);
    }
    return json_decode($raw, true);
}
function require_method($method) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
    }
}

