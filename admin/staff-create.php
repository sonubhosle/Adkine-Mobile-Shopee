<?php
/**
 * MobileShopFlat — Admin Staff Create
 */

require_once __DIR__ . '/../includes/admin-header.php';

$errors = [];
$old    = ['name' => '', 'email' => '', 'phone' => '', 'role' => 'technician'];

// ── POST: Create staff member ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim((string)($_POST['name']     ?? ''));
    $email    = strtolower(trim((string)($_POST['email']    ?? '')));
    $phone    = trim((string)($_POST['phone']    ?? ''));
    $role     = trim((string)($_POST['role']     ?? ''));
    $password = (string)($_POST['password']  ?? '');
    $confirm  = (string)($_POST['confirm']   ?? '');

    $old = compact('name', 'email', 'phone', 'role');

    // Validate
    if ($name === '')                              $errors['name']     = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email address is required.';
    if (!in_array($role, ['admin', 'manager', 'technician'], true))  $errors['role']  = 'Please select a valid role.';
    if (strlen($password) < 8)                     $errors['password'] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                    $errors['confirm']  = 'Passwords do not match.';

    // Email uniqueness
    if (!isset($errors['email'])) {
        $exists = db_fetch_value("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exists) $errors['email'] = 'This email is already registered.';
    }

    if (empty($errors)) {
        $roleId = (int)db_fetch_value("SELECT id FROM roles WHERE slug = ?", [$role]);
        $hash   = password_hash($password, PASSWORD_DEFAULT);
        $userId = db_insert(
            "INSERT INTO users (full_name, email, phone, password_hash, role_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())",
            [$name, $email, $phone, $hash, $roleId]
        );

        set_flash('success', "Staff member \"{$name}\" created successfully.");
        redirect(url('/admin/staff.php'));
    }

    set_flash('error', 'Please fix the errors below.');
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
            <h1 class="text-xl font-bold text-slate-900">Add Staff Member</h1>
            <p class="mt-0.5 text-sm text-slate-500">Create a new admin, manager, or technician account</p>
        </div>
    </div>

    <!-- Form Card -->
    <form method="POST" action="<?= url('/admin/staff-create.php') ?>" class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <?= csrf_field() ?>

        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4 text-indigo-500"></i> Account Details
            </h2>
        </div>

        <div class="px-6 py-5 space-y-4">
            <!-- Full Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>"
                       placeholder="e.g. Ravi Kumar"
                       class="w-full rounded-xl border <?= isset($errors['name']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['name'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email Address <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
                       placeholder="staff@mobileshop.com"
                       class="w-full rounded-xl border <?= isset($errors['email']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($old['phone']) ?>"
                       placeholder="+91 9999999999"
                       class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="block text-sm font-semibold text-slate-700 mb-1">Role <span class="text-rose-500">*</span></label>
                <select id="role" name="role"
                        class="w-full rounded-xl border <?= isset($errors['role']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                    <option value="">— Select Role —</option>
                    <option value="admin"       <?= $old['role'] === 'admin'       ? 'selected' : '' ?>>Admin</option>
                    <option value="manager"     <?= $old['role'] === 'manager'     ? 'selected' : '' ?>>Manager</option>
                    <option value="technician"  <?= $old['role'] === 'technician'  ? 'selected' : '' ?>>Technician</option>
                </select>
                <?php if (isset($errors['role'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['role']) ?></p>
                <?php endif; ?>
                <p class="mt-1 text-xs text-slate-400">Admin: full access &bull; Manager: orders/stock &bull; Technician: repairs only</p>
            </div>

            <hr class="border-slate-100">

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                <input type="password" id="password" name="password"
                       placeholder="Minimum 8 characters"
                       class="w-full rounded-xl border <?= isset($errors['password']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['password'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="confirm" class="block text-sm font-semibold text-slate-700 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
                <input type="password" id="confirm" name="confirm"
                       placeholder="Re-enter password"
                       class="w-full rounded-xl border <?= isset($errors['confirm']) ? 'border-rose-400 bg-rose-50' : 'border-slate-300' ?> px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none transition">
                <?php if (isset($errors['confirm'])): ?>
                    <p class="mt-1 text-xs text-rose-600"><?= e($errors['confirm']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
            <a href="<?= url('/admin/staff.php') ?>"
               class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">Cancel</a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                <i data-lucide="user-check" class="w-4 h-4"></i> Create Staff Member
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

