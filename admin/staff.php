<?php
/**
 * MobileShopFlat — Admin Staff List
 */

require_once __DIR__ . '/../includes/admin-header.php';

// ── POST: Toggle status ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        set_flash('error', 'Invalid staff ID.');
        redirect(url('/admin/staff.php'));
    }

    if ($id === auth_id()) {
        set_flash('error', 'You cannot modify your own account here.');
        redirect(url('/admin/staff.php'));
    }

    if ($action === 'toggle_status') {
        db_query("UPDATE users SET status = IF(status = 'active', 'suspended', 'active'), updated_at = NOW() WHERE id = ?", [$id]);
        set_flash('success', 'Staff status updated.');
    } elseif ($action === 'delete') {
        // Soft deactivate
        db_query("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?", [$id]);
        set_flash('success', 'Staff member deactivated.');
    }

    redirect(url('/admin/staff.php'));
}

// ── Load staff (all non-customer roles) ──────────────────────────────────────
$staff = db_fetch_all(
    "SELECT u.id, u.full_name AS name, u.email, u.phone, (CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS is_active, u.created_at,
            r.name AS role_name, r.slug AS role_slug
     FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE r.slug != 'customer'
     ORDER BY u.created_at DESC"
);

// Role badge helper
function roleBadge(string $slug, string $name): string {
    $map = [
        'admin'       => 'bg-purple-50 text-purple-700 border-purple-200',
        'manager'     => 'bg-blue-50 text-blue-700 border-blue-200',
        'technician'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    ];
    $cls = $map[$slug] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border $cls\">" . e(ucfirst($name)) . "</span>";
}
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Staff &amp; Roles</h1>
            <p class="mt-1 text-sm text-slate-500">Admin, manager, and technician accounts</p>
        </div>
        <a href="<?= url('/admin/staff-create.php') ?>"
           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
            <i data-lucide="user-plus" class="w-4 h-4"></i> Add Staff
        </a>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
                <tr>
                    <th class="px-4 py-3">Staff Member</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($staff as $m): ?>
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shrink-0
                                <?= $m['role_slug'] === 'admin' ? 'bg-purple-100 text-purple-700' : ($m['role_slug'] === 'manager' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700') ?>">
                                <?= strtoupper(substr($m['name'] ?? 'S', 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-900 truncate">
                                    <?= e($m['name']) ?>
                                    <?php if ($m['id'] === auth_id()): ?>
                                        <span class="ml-1 text-[10px] px-1 py-0.5 bg-indigo-50 text-indigo-600 rounded font-bold">You</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-slate-400"><?= e($m['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-sm"><?= e($m['phone'] ?? '—') ?></td>
                    <td class="px-4 py-3"><?= roleBadge($m['role_slug'], $m['role_name']) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border
                            <?= $m['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?>">
                            <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                    <td class="px-4 py-3 text-right">
                        <?php if ($m['id'] !== auth_id()): ?>
                        <div class="inline-flex items-center gap-3">
                            <a href="<?= url('/admin/staff-edit.php?id=' . (int)$m['id']) ?>"
                               class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline text-xs">Edit</a>
                            <form method="POST" action="<?= url('/admin/staff.php') ?>"
                                  onsubmit="return confirm('<?= $m['is_active'] ? 'Deactivate' : 'Activate' ?> this staff member?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                <button type="submit"
                                    class="font-semibold text-xs hover:underline
                                        <?= $m['is_active'] ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' ?>">
                                    <?= $m['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <span class="text-xs text-slate-300">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$staff): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                        <i data-lucide="shield-off" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                        No staff members found.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <p class="text-xs text-slate-400"><?= count($staff) ?> staff members total</p>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

