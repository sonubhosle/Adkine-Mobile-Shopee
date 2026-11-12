<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart-functions.php';

session_init();

$action = $_GET['action'] ?? '';
if ($action === 'remove') {
    cart_remove_coupon();
    set_flash('info', 'Promo coupon removed.');
    redirect('/cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        redirect('/cart.php');
    }

    $couponCode = trim($_POST['coupon_code'] ?? '');
    if (empty($couponCode)) {
        set_flash('error', 'Please enter a coupon code.');
        redirect('/cart.php');
    }

    $result = cart_apply_coupon($couponCode);
    if ($result['status']) {
        set_flash('success', $result['message']);
    } else {
        set_flash('error', $result['message']);
    }
}

redirect('/cart.php');

