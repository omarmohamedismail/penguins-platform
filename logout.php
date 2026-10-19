<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();
require_method('POST');
require_csrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: auth.php');
exit;