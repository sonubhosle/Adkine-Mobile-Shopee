<?php
/**
 * MobileShopFlat — Razorpay Gateway Functions
 */

require_once __DIR__ . '/config.php';

function razorpay_is_configured(): bool
{
    if (!defined('RAZORPAY_KEY_ID') || !defined('RAZORPAY_KEY_SECRET') || RAZORPAY_KEY_ID === '' || RAZORPAY_KEY_SECRET === '') {
        return false;
    }

    return APP_ENV !== 'production' || str_starts_with(RAZORPAY_KEY_ID, 'rzp_live_');
}

function razorpay_create_order(string $receiptId, float $amount, array $notes = []): array
{
    if (!razorpay_is_configured()) {
        throw new RuntimeException('Online payments are not configured. Please choose Cash on Delivery or contact support.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The server payment client is unavailable. Please contact support.');
    }

    $amountInPaise = (int)round($amount * 100);
    $payload = json_encode([
        'receipt' => $receiptId,
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'payment_capture' => 1,
        'notes' => $notes,
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('Unable to start the Razorpay payment. Please try again or choose Cash on Delivery.');
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

function razorpay_verify_signature(string $orderId, string $paymentId, string $signature): bool
{
    if (!defined('RAZORPAY_KEY_SECRET') || empty(RAZORPAY_KEY_SECRET)) {
        return false;
    }

    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expectedSignature, $signature);
}

