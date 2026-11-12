<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();

// Support both POST and GET for logout simplicity
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

logout_user();
set_flash('info', 'You have been signed out safely.');
redirect('/login.php');

