<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart-functions.php';

session_init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => false, 'message' => 'Invalid request method.'], 405);
}

if (!verify_csrf()) {
    json_response(['status' => false, 'message' => 'Invalid security token.'], 403);
}

$itemId = (int)($_POST['item_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);

if ($itemId <= 0) {
    json_response(['status' => false, 'message' => 'Invalid item.'], 400);
}

$result = cart_update_item($itemId, $quantity);
json_response($result, $result['status'] ? 200 : 400);

