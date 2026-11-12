<?php
/**
 * MobileShopFlat — POST-only: Staff Update
 * Receives: id, name, email, phone, role (via hidden fields + form)
 */

require_once __DIR__ . '/../../includes/admin-header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('/admin/staff.php'));
}

verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid staff ID.');
    redirect(url('/admin/staff.php'));
}

if ($id === auth_id()) {
    set_flash('error', 'You cannot edit your own account from here.');
    redirect(url('/admin/staff.php'));
}

// Verify staff exists
$member = db_fetch_one(
    "SELECT u.id FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE u.id = ? AND r.slug != 'customer'",
    [$id]
);

if (!$member) {
    set_flash('error', 'Staff member not found.');
    redirect(url('/admin/staff.php'));
}

$name  = trim((string)($_POST['name']  ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$phone = trim((string)($_POST['phone'] ?? ''));
$role  = trim((string)($_POST['role']  ?? ''));

$errors = [];
if ($name === '')                              $errors[] = 'Full name is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (!in_array($role, ['admin', 'manager', 'technician'], true))  $errors[] = 'Select a valid role.';

if (empty($errors)) {
    $dup = db_fetch_value("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
    if ($dup) $errors[] = 'This email is already in use by another account.';
}

if (!empty($errors)) {
    foreach ($errors as $err) set_flash('error', $err);
    redirect(url('/admin/staff-edit.php?id=' . $id));
}

// Update user & role
$roleId = (int)db_fetch_value("SELECT id FROM roles WHERE slug = ?", [$role]);
db_query(
    "UPDATE users SET full_name = ?, email = ?, phone = ?, role_id = ?, updated_at = NOW() WHERE id = ?",
    [$name, $email, $phone, $roleId, $id]
);

set_flash('success', 'Staff member updated successfully.');
redirect(url('/admin/staff.php'));

