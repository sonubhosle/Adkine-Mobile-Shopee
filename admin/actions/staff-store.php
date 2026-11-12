<?php
/**
 * MobileShopFlat — POST-only: Staff Store
 * Alternative standalone POST handler for staff creation.
 * The inline handler in staff-create.php is the primary path;
 * this file exists as an explicit redirect target if needed.
 */

require_once __DIR__ . '/../../includes/admin-header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('/admin/staff-create.php'));
}

verify_csrf();

$name     = trim((string)($_POST['name']     ?? ''));
$email    = strtolower(trim((string)($_POST['email']    ?? '')));
$phone    = trim((string)($_POST['phone']    ?? ''));
$role     = trim((string)($_POST['role']     ?? ''));
$password = (string)($_POST['password']  ?? '');
$confirm  = (string)($_POST['confirm']   ?? '');

$errors = [];

if ($name === '')                              $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (!in_array($role, ['admin', 'manager', 'technician'], true))  $errors[] = 'Please select a valid role.';
if (strlen($password) < 8)                     $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm)                    $errors[] = 'Passwords do not match.';

if (empty($errors)) {
    $exists = db_fetch_value("SELECT id FROM users WHERE email = ?", [$email]);
    if ($exists) $errors[] = 'This email is already registered.';
}

if (!empty($errors)) {
    foreach ($errors as $err) {
        set_flash('error', $err);
    }
    redirect(url('/admin/staff-create.php'));
}

// Insert
$roleId = (int)db_fetch_value("SELECT id FROM roles WHERE slug = ?", [$role]);
$hash   = password_hash($password, PASSWORD_DEFAULT);
$userId = db_insert(
    "INSERT INTO users (full_name, email, phone, password_hash, role_id, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())",
    [$name, $email, $phone, $hash, $roleId]
);

set_flash('success', "Staff member \"{$name}\" created successfully.");
redirect(url('/admin/staff.php'));

