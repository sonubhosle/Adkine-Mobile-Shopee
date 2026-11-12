<?php
/**
 * MobileShopFlat — Admin Staff Edit
 */

require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid staff ID.');
    redirect(url('/admin/staff.php'));
}

if ($id === auth_id()) {
    set_flash('error', 'Use your profile settings to edit your own account.');
    redirect(url('/admin/staff.php'));
}

// Load member (must be non-customer)
$member = db_fetch_one(
    "SELECT u.*, u.full_name AS name, (CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS is_active,
            r.name AS role_name, r.slug AS role_slug
     FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE u.id = ? AND r.slug != 'customer'",
    [$id]
);

if (!$member) {
    set_flash('error', 'Staff member not found.');
    redirect(url('/admin/staff.php'));
}

$errors = [];

// ── POST: Update staff ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'update';

    // ── Reset Password only ──────────────────────────────────────────────────
    if ($action === 'reset_password') {
        $newPass  = (string)($_POST['new_password']     ?? '');
        $confPass = (string)($_POST['confirm_password'] ?? '');

        if (strlen($newPass) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        } elseif ($newPass !== $confPass) {
            $errors['confirm_password'] = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            db_query("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$hash, $id]);
            set_flash('success', 'Password reset successfully.');
            redirect(url('/admin/staff-edit.php?id=' . $id));
        }
        set_flash('error', 'Please fix the errors below.');
    }

    // ── Update profile ───────────────────────────────────────────────────────
    if ($action === 'update') {
        $name  = trim((string)($_POST['name']  ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $role  = trim((string)($_POST['role']  ?? ''));

        // Merge for re-display
        $member = array_merge($member, ['name' => $name, 'email' => $email, 'phone' => $phone, 'role_slug' => $role]);

        if ($name === '')                              $errors['name']  = 'Full name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email is required.';
        if (!in_array($role, ['admin', 'manager', 'technician'], true))  $errors['role']  = 'Select a valid role.';

        if (!isset($errors['email'])) {
            $dup = db_fetch_value("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
            if ($dup) $errors['email'] = 'This email is already in use.';
        }

        if (empty($errors)) {
            $roleId = (int)db_fetch_value("SELECT id FROM roles WHERE slug = ?", [$role]);
            db_query(
                "UPDATE users SET full_name = ?, email = ?, phone = ?, role_id = ?, updated_at = NOW() WHERE id = ?",
                [$name, $email, $phone, $roleId, $id]
            );

            set_flash('success', 'Staff member updated successfully.');
            redirect(url('/admin/staff.php'));
        }

        set_flash('error', 'Please fix the errors below.');
    }
}
?>

<div class="max-w-2xl space-y-6">
    <!-- Page Header -->
    <div class="flex items-center gap-3">
        <a href="<?= url('/admin/staff.php') ?>"
           class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition text-slate-500">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-900">Edit Staff Member</h1>
            <p class="mt-0.5 text-sm text-slate-500"><?= e($member['name']) ?> &bull; <?= e(ucfirst($member['role_name'] ?? $member['role_slug'])) ?></p>
        </div>
    </div>

    <!-- Edit Profile Card -->
    <form method="POST" action="<?= url('/admin/staff-edit.php?id=' . $id) ?>"
          class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update">

        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-indigo-500"></i> Profile Information
            </h2>
        </div>

        <div class="px-6 py-5 space-y-4">
            <!-- Full Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($member['name']) ?>"
                       class="w-full rounded-xl border <?= isset($errors['name']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['name'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($member['email']) ?>"
                       class="w-full rounded-xl border <?= isset($errors['email']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($member['phone'] ?? '') ?>"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-semibold text-slate-700 mb-1">Role <span class="text-rose-500">*</span></label>
                <select id="role" name="role"
                        class="w-full rounded-xl border <?= isset($errors['role']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                    <option value="admin"      <?= ($member['role_slug'] ?? '') === 'admin'      ? 'selected' : '' ?>>Admin</option>
                    <option value="manager"    <?= ($member['role_slug'] ?? '') === 'manager'    ? 'selected' : '' ?>>Manager</option>
                    <option value="technician" <?= ($member['role_slug'] ?? '') === 'technician' ? 'selected' : '' ?>>Technician</option>
                </select>
                <?php if (isset($errors['role'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['role']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
            <a href="<?= url('/admin/staff.php') ?>"
               class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <i data-lucide="save" class="w-4 h-4"></i> Save Changes
            </button>
        </div>
    </form>

    <!-- Reset Password Card -->
    <form method="POST" action="<?= url('/admin/staff-edit.php?id=' . $id) ?>"
          class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden"
          onsubmit="return confirm('Reset this staff member\'s password?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset_password">

        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i data-lucide="lock" class="w-4 h-4 text-amber-500"></i> Reset Password
            </h2>
        </div>

        <div class="px-6 py-5 space-y-4">
            <div>
                <label for="new_password" class="block text-sm font-semibold text-slate-700 mb-1">New Password <span class="text-rose-500">*</span></label>
                <input type="password" id="new_password" name="new_password" placeholder="Minimum 8 characters"
                       class="w-full rounded-xl border <?= isset($errors['new_password']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['new_password'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['new_password']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label for="confirm_password" class="block text-sm font-semibold text-slate-700 mb-1">Confirm New Password <span class="text-rose-500">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password"
                       class="w-full rounded-xl border <?= isset($errors['confirm_password']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['confirm_password'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['confirm_password']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="px-6 py-4 bg-amber-50 border-t border-amber-100 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700 transition">
                <i data-lucide="key" class="w-4 h-4"></i> Reset Password
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

