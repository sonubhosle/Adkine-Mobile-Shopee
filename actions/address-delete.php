<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

session_init();
require_auth();

$userId = auth_id();
$addressId = (int)($_POST['address_id'] ?? 0);

if ($addressId > 0 && verify_csrf()) {
    db_query("DELETE FROM addresses WHERE id = ? AND user_id = ?", [$addressId, $userId]);
    set_flash('info', 'Address deleted.');
}

redirect('/account.php');

