<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart-functions.php';

session_init();

$itemId = (int)($_POST['item_id'] ?? $_GET['item_id'] ?? 0);
if ($itemId > 0) {
    cart_remove_item($itemId);
}

if (is_ajax()) {
    json_response(['status' => true, 'message' => 'Item removed from cart.']);
} else {
    redirect('/cart.php');
}

