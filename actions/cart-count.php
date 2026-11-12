<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart-functions.php';

session_init();
$cart = cart_get_details();

json_response([
    'status' => true,
    'total_items' => $cart['total_items'],
    'grand_total' => $cart['grand_total'],
    'formatted_total' => format_currency($cart['grand_total']),
]);

