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

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['password'] ?? '';
    $confirmPass = $_POST['password_confirmation'] ?? '';

    $user = db_fetch_one("SELECT password_hash FROM users WHERE id = ?", [$userId]);
    if (!$user || !password_verify($currentPass, $user['password_hash'])) {
        set_flash('error', 'Current password does not match our records.');
        redirect('/account.php');
    }

    if (strlen($newPass) < 8) {
        set_flash('error', 'New password must be at least 8 characters long.');
        redirect('/account.php');
    }

    if ($newPass !== $confirmPass) {
        set_flash('error', 'New passwords do not match.');
        redirect('/account.php');
    }

    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    db_query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$hash, $userId]);

    set_flash('success', 'Password updated successfully.');
}

redirect('/account.php');

