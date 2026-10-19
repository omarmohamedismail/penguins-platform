<?php
require_once __DIR__ . '/db.php';
penguins_secure_session_start();
penguins_security_headers();
$userId = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Invalid request method.'], 405);
}
require_csrf();

$decoded = read_json_object(2 * 1024 * 1024);
$lists = ['clients','leads','invoices','plans','planTemplates','sessions','slots','feedback','submissions','pointsClients','readiness','cohorts','automations','contracts','community','shop','library','tasks'];
foreach ($lists as $key) {
    if (!isset($decoded[$key]) || !is_array($decoded[$key]) || !array_is_list($decoded[$key])) {
        json_response(['ok' => false, 'error' => 'Invalid dashboard collection: ' . $key], 422);
    }
}
if (!isset($decoded['account']) || !is_array($decoded['account'])) {
    json_response(['ok' => false, 'error' => 'Invalid account data.'], 422);
}
unset($decoded['_csrf']);
$clean = json_encode($decoded, JSON_THROW_ON_ERROR);

$pdo = penguins_db();
$stmt = $pdo->prepare("INSERT INTO user_data (user_id, data_json) VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE data_json = VALUES(data_json)");
$stmt->execute([$userId, $clean]);

json_response(['ok' => true]);