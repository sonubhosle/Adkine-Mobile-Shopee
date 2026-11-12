<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart-functions.php';
require_once __DIR__ . '/../includes/order-functions.php';
require_once __DIR__ . '/../includes/payment-functions.php';

session_init();
require_auth();

$userId = auth_id();
$action = $_GET['action'] ?? '';

// Razorpay order creation endpoint
if ($action === 'create_razorpay_order') {
    if (!verify_csrf()) {
        json_response(['status' => false, 'message' => 'Invalid security token.'], 403);
    }

    $cart = cart_get_details();
    if (empty($cart['items'])) {
        json_response(['status' => false, 'message' => 'Shopping cart is empty.'], 400);
    }

    $addressId = $_POST['address_id'] ?? null;
    if ($addressId === 'new') {
        $fullName = trim($_POST['new_full_name'] ?? '');
        $phone = trim($_POST['new_phone'] ?? '');
        $line1 = trim($_POST['new_address_line1'] ?? '');
        $line2 = trim($_POST['new_address_line2'] ?? '');
        $city = trim($_POST['new_city'] ?? '');
        $state = trim($_POST['new_state'] ?? '');
        $pin = trim($_POST['new_postal_code'] ?? '');

        if (empty($fullName) || empty($phone) || empty($line1) || empty($city) || empty($state) || empty($pin)) {
            json_response(['status' => false, 'message' => 'Please fill all required address fields.'], 400);
        }

        $addressId = db_insert(
            "INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'shipping')",
            [$userId, $fullName, $phone, $line1, $line2, $city, $state, $pin]
        );
    } else {
        $addressId = (int)$addressId;
    }

    if (!order_address_belongs_to_user($userId, (int)$addressId)) {
        json_response(['status' => false, 'message' => 'Please select a valid delivery address.'], 400);
    }

    $_SESSION['checkout_shipping_address_id'] = $addressId;
    $_SESSION['checkout_order_notes'] = trim($_POST['notes'] ?? '');

    try {
        $receiptId = 'rcpt_' . time();
        $razorpayOrder = razorpay_create_order($receiptId, (float)$cart['grand_total'], ['user_id' => (string)$userId]);
        $_SESSION['razorpay_order_id'] = $razorpayOrder['id'] ?? null;
        json_response(['status' => true, 'order' => $razorpayOrder]);
    } catch (\Throwable $e) {
        json_response(['status' => false, 'message' => $e->getMessage()], 500);
    }
}

// Standard POST for COD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        redirect('/checkout.php');
    }

    $cart = cart_get_details();
    if (empty($cart['items'])) {
        set_flash('error', 'Cart is empty.');
        redirect('/cart.php');
    }

    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $addressId = $_POST['address_id'] ?? null;

    if ($addressId === 'new') {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $line1 = trim($_POST['address_line1'] ?? '');
        $line2 = trim($_POST['address_line2'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pin = trim($_POST['postal_code'] ?? '');

        if (empty($fullName) || empty($phone) || empty($line1) || empty($city) || empty($state) || empty($pin)) {
            set_flash('error', 'Please fill all required delivery address fields.');
            redirect('/checkout.php');
        }

        $addressId = db_insert(
            "INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'shipping')",
            [$userId, $fullName, $phone, $line1, $line2, $city, $state, $pin]
        );
    } else {
        $addressId = (int)$addressId;
    }

    if (!order_address_belongs_to_user($userId, (int)$addressId)) {
        set_flash('error', 'Please select a valid delivery address.');
        redirect('/checkout.php');
    }

    $notes = trim($_POST['notes'] ?? '');

    try {
        $result = order_create(
            $userId,
            $addressId,
            $addressId,
            $paymentMethod,
            'pending',
            null,
            null,
            $notes
        );

        set_flash('success', "Order #{$result['order_number']} placed successfully via Cash on Delivery!");
        redirect('/order-detail.php?id=' . $result['order_id']);
    } catch (\Throwable $e) {
        set_flash('error', $e->getMessage());
        redirect('/checkout.php');
    }
}
