<?php
/**
 * MobileShopFlat — POST-only: Staff Soft Delete (Deactivate)
 * Sets is_active = 0. Never hard-deletes a staff record.
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
    set_flash('error', 'You cannot deactivate your own account.');
    redirect(url('/admin/staff.php'));
}

// Verify staff member exists (non-customer)
$member = db_fetch_one(
    "SELECT u.id, u.full_name AS name FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE u.id = ? AND r.slug != 'customer'",
    [$id]
);

if (!$member) {
    set_flash('error', 'Staff member not found.');
    redirect(url('/admin/staff.php'));
}

// Soft delete: deactivate only
db_query("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?", [$id]);

set_flash('success', "Staff member \"{$member['name']}\" has been deactivated.");
redirect(url('/admin/staff.php'));

