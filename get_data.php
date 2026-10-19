<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();
$userId = require_login();
require_method('GET');

$pdo = penguins_db();
$stmt = $pdo->prepare('SELECT data_json FROM user_data WHERE user_id = ?');
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json_response(['ok' => false, 'error' => 'No data found for this account.'], 404);
}

// Hand the page a fresh CSRF token alongside the data so save_data.php can be called safely.
$decoded = json_decode($row['data_json'], true);
$decoded['_csrf'] = csrf_token();

header('Content-Type: application/json');
echo json_encode($decoded);