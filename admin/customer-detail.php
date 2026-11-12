<?php
/**
 * MobileShopFlat — Admin Customer Detail
 */

require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid customer ID.');
    redirect(url('/admin/customers.php'));
}

// Load customer (must have customer role)
$customer = db_fetch_one(
    "SELECT u.*, u.full_name AS name, (CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS is_active
     FROM users u
     JOIN roles r ON r.id = u.role_id AND r.slug = 'customer'
     WHERE u.id = ?",
    [$id]
);

if (!$customer) {
    set_flash('error', 'Customer not found.');
    redirect(url('/admin/customers.php'));
}

// Quick stats
$orderCount  = (int)(db_fetch_value("SELECT COUNT(*) FROM orders WHERE user_id = ?", [$id]) ?? 0);
$totalSpent  = (float)(db_fetch_value("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = ?", [$id]) ?? 0);
$repairCount = (int)(db_fetch_value("SELECT COUNT(*) FROM repairs WHERE user_id = ? OR customer_name = ?", [$id, $customer['name']]) ?? 0);

// Last 10 orders
$orders = db_fetch_all(
    "SELECT id, order_number, total_amount AS grand_total, order_status, created_at
     FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
    [$id]
);

// Last 10 repairs (match by customer user_id if column exists, else by name)
$repairs = db_fetch_all(
    "SELECT id, repair_ticket_number, customer_name, status, created_at
     FROM repairs WHERE customer_name = ? ORDER BY created_at DESC LIMIT 10",
    [$customer['name']]
);

// Saved addresses
$addresses = db_fetch_all(
    "SELECT *, full_name AS name, postal_code AS pincode FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id ASC",
    [$id]
);

// Order status badge helper
function orderStatusBadge(string $status): string {
    $map = [
        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
        'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
        'shipped'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'delivered'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled'  => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border $cls\">" . e(ucfirst($status)) . "</span>";
}

function repairStatusBadge(string $status): string {
    $map = [
        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
        'in_progress'=> 'bg-blue-50 text-blue-700 border-blue-200',
        'completed'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled'  => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border $cls\">" . e(ucwords(str_replace('_', ' ', $status))) . "</span>";
}
?>

<div class="space-y-6">
    <!-- Back + Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/customers.php') ?>"
               class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition text-slate-500">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-900"><?= e($customer['name']) ?></h1>
                <p class="mt-0.5 text-sm text-slate-500">Customer Profile &bull; ID #<?= $id ?></p>
            </div>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border
            <?= $customer['is_active'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?>">
            <?= $customer['is_active'] ? 'Active Account' : 'Inactive Account' ?>
        </span>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                <i data-lucide="shopping-bag" class="w-5 h-5 text-blue-600"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?= $orderCount ?></div>
                <div class="text-xs text-slate-500 font-medium">Total Orders</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                <i data-lucide="indian-rupee" class="w-5 h-5 text-emerald-600"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?= format_currency($totalSpent) ?></div>
                <div class="text-xs text-slate-500 font-medium">Total Spent</div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                <i data-lucide="wrench" class="w-5 h-5 text-amber-600"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900"><?= $repairCount ?></div>
                <div class="text-xs text-slate-500 font-medium">Repair Tickets</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Profile Info -->
        <div class="space-y-4">
            <!-- Personal Info Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Personal Info
                    </h2>
                </div>
                <div class="px-5 py-4 space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">
                            <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900"><?= e($customer['name']) ?></div>
                            <div class="text-xs text-slate-500">Full Name</div>
                        </div>
                    </div>
                    <div class="border-t border-slate-100 pt-3 space-y-2.5">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span><?= e($customer['email']) ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span><?= e($customer['phone'] ?? '—') ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                            <span>Joined <?= date('d M Y', strtotime($customer['created_at'])) ?></span>
                        </div>
                        <?php if (!empty($customer['email_verified_at'])): ?>
                        <div class="flex items-center gap-2 text-emerald-600">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 shrink-0"></i>
                            <span class="text-xs">Email verified</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Saved Addresses -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-slate-400"></i>
                        Saved Addresses <span class="ml-auto text-xs text-slate-400 font-normal"><?= count($addresses) ?></span>
                    </h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($addresses as $addr): ?>
                    <div class="px-5 py-3 text-sm">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-slate-800"><?= e($addr['name']) ?></span>
                            <?php if ($addr['is_default']): ?>
                                <span class="text-[10px] px-1.5 py-0.5 bg-indigo-50 text-indigo-600 rounded font-bold">Default</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-slate-500 text-xs leading-relaxed">
                            <?= e($addr['address_line1']) ?><?= !empty($addr['address_line2']) ? ', ' . e($addr['address_line2']) : '' ?><br>
                            <?= e($addr['city']) ?>, <?= e($addr['state']) ?> — <?= e($addr['pincode']) ?><br>
                            <?= e($addr['phone']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$addresses): ?>
                    <div class="px-5 py-6 text-center text-xs text-slate-400">No saved addresses.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Orders + Repairs -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Order History -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="shopping-bag" class="w-4 h-4 text-slate-400"></i> Order History
                    </h2>
                    <span class="text-xs text-slate-400">Last 10 orders</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Order #</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-2.5 font-mono text-xs font-semibold text-indigo-700">
                                    <?= e($o['order_number']) ?>
                                </td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td class="px-4 py-2.5 text-right font-semibold text-slate-800"><?= format_currency($o['grand_total']) ?></td>
                                <td class="px-4 py-2.5"><?= orderStatusBadge($o['order_status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$orders): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No orders placed yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Repair History -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="wrench" class="w-4 h-4 text-slate-400"></i> Repair History
                    </h2>
                    <span class="text-xs text-slate-400">Last 10 tickets</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Ticket #</th>
                                <th class="px-4 py-3">Device</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($repairs as $r): ?>
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-2.5 font-mono text-xs font-semibold text-amber-700">
                                    <?= e($r['repair_ticket_number']) ?>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700 text-xs"><?= e($r['customer_name']) ?></td>
                                <td class="px-4 py-2.5"><?= repairStatusBadge($r['status']) ?></td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$repairs): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">No repair tickets found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

