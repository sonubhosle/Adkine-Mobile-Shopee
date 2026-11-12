<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

session_init();
require_auth();

$userId = auth_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid security token.');
        redirect('/account.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $line1 = trim($_POST['address_line1'] ?? '');
    $line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pin = trim($_POST['postal_code'] ?? '');
    $isDefault = !empty($_POST['is_default']) ? 1 : 0;

    if (empty($fullName) || empty($phone) || empty($line1) || empty($city) || empty($state) || empty($pin)) {
        set_flash('error', 'Please fill all required address fields.');
        redirect('/account.php');
    }

    if ($isDefault) {
        db_query("UPDATE addresses SET is_default = 0 WHERE user_id = ?", [$userId]);
    }

    db_insert(
        "INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, postal_code, is_default, type) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'shipping')",
        [$userId, $fullName, $phone, $line1, $line2, $city, $state, $pin, $isDefault]
    );

    set_flash('success', 'Address added to your address book.');
}

redirect('/account.php');

