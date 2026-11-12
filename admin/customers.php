<?php
/**
 * MobileShopFlat — Admin Customers List
 */

require_once __DIR__ . '/../includes/admin-header.php';

// ── POST: Toggle customer active status ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_query("UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?", [$id]);
        set_flash('success', 'Customer status updated.');
    }
    redirect(url('/admin/customers.php'));
}

// ── Filters & Pagination ─────────────────────────────────────────────────────
$search = trim((string)($_GET['search'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 25;
$offset = ($page - 1) * $limit;

$whereParts = ["r.slug = 'customer'"];
$params     = [];

if ($search !== '') {
    $whereParts[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

$where = 'WHERE ' . implode(' AND ', $whereParts);

$total = (int)(db_fetch_value(
    "SELECT COUNT(DISTINCT u.id)
     FROM users u
     JOIN roles r ON r.id = u.role_id
     $where",
    $params
) ?? 0);

$pages     = (int)ceil($total / $limit);
$customers = db_fetch_all(
    "SELECT u.id, u.full_name AS name, u.email, u.phone, (CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS is_active, u.created_at,
            COUNT(DISTINCT o.id) AS order_count,
            COALESCE(SUM(o.total_amount), 0) AS total_spent
     FROM users u
     JOIN roles r ON r.id = u.role_id
     LEFT JOIN orders o ON o.user_id = u.id
     $where
     GROUP BY u.id
     ORDER BY u.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

// Build pagination query string helper
$qs = $search !== '' ? '&search=' . urlencode($search) : '';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Customers CRM</h1>
            <p class="mt-1 text-sm text-slate-500">All registered customer accounts and their purchase history</p>
        </div>
        <div class="text-sm text-slate-500 font-medium">
            <span class="bg-slate-100 px-3 py-1.5 rounded-xl"><?= number_format($total) ?> customers</span>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" action="<?= url('/admin/customers.php') ?>" class="flex gap-2">
        <div class="relative w-80">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
            <input name="search" value="<?= e($search) ?>" placeholder="Name, email or phone..."
                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 pl-9 text-sm shadow-xs focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?= url('/admin/customers.php') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
                <tr>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Registered</th>
                    <th class="px-4 py-3 text-right">Orders</th>
                    <th class="px-4 py-3 text-right">Total Spent</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-sm shrink-0">
                                <?= strtoupper(substr($c['name'] ?? 'C', 0, 1)) ?>
                            </div>
                            <div class="min-w-0">
                                <a href="<?= url('/admin/customer-detail.php?id=' . (int)$c['id']) ?>"
                                   class="font-semibold text-slate-900 hover:text-indigo-600 truncate block">
                                    <?= e($c['name']) ?>
                                </a>
                                <div class="text-xs text-slate-400"><?= e($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-sm"><?= e($c['phone'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-slate-500 text-xs"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-700"><?= (int)$c['order_count'] ?></td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-800"><?= format_currency($c['total_spent']) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                            <?= $c['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-3">
                            <a href="<?= url('/admin/customer-detail.php?id=' . (int)$c['id']) ?>"
                               class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline text-xs">View</a>
                            <form method="POST" action="<?= url('/admin/customers.php') ?>"
                                  onsubmit="return confirm('Toggle status for this customer?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button type="submit"
                                    class="font-semibold text-xs <?= $c['is_active'] ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' ?> hover:underline">
                                    <?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$customers): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                        <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                        No customers found<?= $search !== '' ? ' matching your search.' : '.' ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between text-xs text-slate-500">
        <p>Showing <?= count($customers) ?> of <?= number_format($total) ?> customers &bull; Page <?= $page ?> of <?= max(1, $pages) ?></p>
        <?php if ($pages > 1): ?>
        <div class="inline-flex gap-1">
            <?php if ($page > 1): ?>
                <a href="<?= url('/admin/customers.php?page=' . ($page - 1) . $qs) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                <a href="<?= url('/admin/customers.php?page=' . $p . $qs) ?>"
                   class="px-3 py-1.5 rounded-lg border <?= $p === $page ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'border-slate-200 bg-white hover:bg-slate-50' ?>">
                   <?= $p ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= url('/admin/customers.php?page=' . ($page + 1) . $qs) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

