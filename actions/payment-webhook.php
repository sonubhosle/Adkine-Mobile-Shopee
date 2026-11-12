<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$rawPayload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (defined('RAZORPAY_WEBHOOK_SECRET') && !empty(RAZORPAY_WEBHOOK_SECRET)) {
    $expected = hash_hmac('sha256', $rawPayload, RAZORPAY_WEBHOOK_SECRET);
    if (!hash_equals($expected, $signature)) {
        http_response_code(400);
        exit('Invalid signature');
    }
}

$event = json_decode($rawPayload, true);
if (!empty($event['event']) && $event['event'] === 'payment.captured') {
    $payment = $event['payload']['payment']['entity'] ?? [];
    $orderId = $payment['order_id'] ?? null;
    $paymentId = $payment['id'] ?? null;

    if ($orderId) {
        db_query(
            "UPDATE orders SET payment_status = 'paid', razorpay_payment_id = ?, updated_at = NOW() WHERE razorpay_order_id = ?",
            [$paymentId, $orderId]
        );
    }
}

json_response(['status' => 'ok']);

