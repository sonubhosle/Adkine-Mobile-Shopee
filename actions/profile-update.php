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

    if (empty($fullName) || empty($phone)) {
        set_flash('error', 'Full name and phone number are required.');
        redirect('/account.php');
    }

    db_query("UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?", [$fullName, $phone, $userId]);
    $_SESSION['auth_user']['full_name'] = $fullName;
    $_SESSION['auth_user']['phone'] = $phone;

    set_flash('success', 'Profile information updated successfully.');
}

redirect('/account.php');

