<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart-functions.php';
require_once __DIR__ . '/includes/order-functions.php';
require_once __DIR__ . '/includes/payment-functions.php';

session_init();
require_auth();

$userId = auth_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verify_csrf()) {
    json_response(['status' => false, 'message' => 'Invalid security token.'], 403);
}

$orderId = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

if (empty($orderId) || empty($paymentId) || empty($signature)) {
    json_response(['status' => false, 'message' => 'Missing payment parameters.'], 400);
}

$expectedOrderId = (string)($_SESSION['razorpay_order_id'] ?? '');
if ($expectedOrderId === '' || !hash_equals($expectedOrderId, $orderId)) {
    json_response(['status' => false, 'message' => 'Payment order verification failed.'], 400);
}

if (!razorpay_verify_signature($orderId, $paymentId, $signature)) {
    json_response(['status' => false, 'message' => 'Payment signature verification failed.'], 400);
}

$addressId = (int)($_SESSION['checkout_shipping_address_id'] ?? 0);
$notes = $_SESSION['checkout_order_notes'] ?? '';

if ($addressId <= 0) {
    $firstAddr = db_fetch_one("SELECT id FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1", [$userId]);
    $addressId = $firstAddr ? (int)$firstAddr['id'] : 0;
}

if (!order_address_belongs_to_user($userId, $addressId)) {
    json_response(['status' => false, 'message' => 'The selected delivery address is invalid.'], 400);
}

try {
    $result = order_create(
        $userId,
        $addressId,
        $addressId,
        'razorpay',
        'paid',
        $orderId,
        $paymentId,
        $notes
    );

    unset($_SESSION['checkout_shipping_address_id'], $_SESSION['checkout_order_notes'], $_SESSION['razorpay_order_id']);

    set_flash('success', "Payment received! Order #{$result['order_number']} has been confirmed.");
    json_response([
        'status' => true,
        'order_id' => $result['order_id'],
        'order_number' => $result['order_number'],
        'redirect_url' => url('/order-detail.php?id=' . $result['order_id']),
    ]);
} catch (\Throwable $e) {
    json_response(['status' => false, 'message' => $e->getMessage()], 500);
}
