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

$productId = (int)($_POST['product_id'] ?? 0);
$variantId = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if ($productId <= 0) {
    json_response(['status' => false, 'message' => 'Invalid product identifier.'], 400);
}

$result = cart_add_item($productId, $variantId, $quantity);
json_response($result, $result['status'] ? 200 : 400);

