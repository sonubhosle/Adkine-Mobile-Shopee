<?php
/**
 * MobileShopFlat — Admin Orders List
 */

require_once __DIR__ . '/../includes/admin-header.php';

$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// ── POST: bulk / inline status update ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid CSRF token.');
        redirect(url('/admin/orders.php'));
    }
    $orderId   = (int)($_POST['id'] ?? 0);
    $newStatus = $_POST['order_status'] ?? '';
    $comment   = trim($_POST['comment'] ?? '');

    if ($orderId <= 0 || !in_array($newStatus, $validStatuses, true)) {
        set_flash('error', 'Invalid order or status.');
        redirect(url('/admin/orders.php'));
    }

    $order = db_fetch_one("SELECT id, order_status FROM orders WHERE id = ?", [$orderId]);
    if (!$order) {
        set_flash('error', 'Order not found.');
        redirect(url('/admin/orders.php'));
    }

    db_query("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $orderId]);
    db_insert(
        "INSERT INTO order_status_history (order_id, old_status, new_status, comment, changed_by, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$orderId, $order['order_status'], $newStatus, $comment, auth_id()]
    );

    // If order was cancelled, restore inventory
    if ($newStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
        require_once __DIR__ . '/../includes/inventory-functions.php';
        $items = db_fetch_all("SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?", [$orderId]);
        foreach ($items as $itm) {
            inventory_add_stock(
                (int)$itm['product_id'],
                $itm['variant_id'] ? (int)$itm['variant_id'] : null,
                (int)$itm['quantity'],
                'return',
                $orderId,
                'Stock restored from order #' . $orderId . ' cancellation'
            );
        }
    }

    set_flash('success', 'Order status updated successfully.');
    redirect(url('/admin/orders.php'));
}

// ── GET Filters ───────────────────────────────────────────────────────────────
$search       = trim((string)($_GET['search'] ?? ''));
$statusFilter = $_GET['status'] ?? '';
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

// ── Build WHERE ───────────────────────────────────────────────────────────────
$where  = 'WHERE 1=1';
$params = [];

if ($statusFilter !== '') {
    $where   .= ' AND o.order_status = ?';
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where   .= ' AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// ── Count ─────────────────────────────────────────────────────────────────────
$countSql = "SELECT COUNT(DISTINCT o.id)
             FROM orders o
             JOIN users u ON u.id = o.user_id
             $where";
$total = (int)(db_fetch_value($countSql, $params) ?? 0);
$pages = (int)ceil($total / $limit);

// ── Fetch ─────────────────────────────────────────────────────────────────────
$listParams   = array_merge($params, [$limit, $offset]);
$orders = db_fetch_all(
    "SELECT o.id, o.order_number, o.total_amount AS grand_total, o.order_status, o.payment_status, o.created_at,
            u.full_name AS customer_name, u.email AS customer_email,
            COUNT(oi.id) AS item_count
     FROM orders o
     JOIN users u ON u.id = o.user_id
     LEFT JOIN order_items oi ON oi.order_id = o.id
     $where
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);

// ── Badge helpers ─────────────────────────────────────────────────────────────
function orderStatusBadge(string $status): string
{
    $map = [
        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
        'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
        'shipped'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'delivered'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled'  => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ' . $cls . '">'
         . e(ucfirst($status)) . '</span>';
}

function paymentStatusBadge(string $status): string
{
    $map = [
        'paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'failed'   => 'bg-rose-50 text-rose-700 border-rose-200',
        'refunded' => 'bg-blue-50 text-blue-700 border-blue-200',
    ];
    $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ' . $cls . '">'
         . e(ucfirst($status)) . '</span>';
}

// ── Build pagination query string ─────────────────────────────────────────────
function ordersQs(int $page, string $search, string $status): string
{
    $q = ['page' => $page];
    if ($search !== '') $q['search'] = $search;
    if ($status !== '') $q['status'] = $status;
    return '?' . http_build_query($q);
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Customer Orders</h1>
            <p class="mt-1 text-sm text-slate-500">View, filter, and manage all shop orders</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('/admin/orders.php') ?>" class="flex flex-wrap gap-2 items-end">
        <div class="relative">
            <input name="search" value="<?= e($search) ?>" placeholder="Order #, customer name, email…"
                   class="w-72 rounded-xl border border-slate-300 bg-white px-3.5 py-2 pl-9 text-sm shadow-xs focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        </div>
        <select name="status" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm shadow-xs focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <option value="">All Statuses</option>
            <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                <option value="<?= e($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Search</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="<?= url('/admin/orders.php') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
                <tr>
                    <th class="px-4 py-3">Order #</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3 text-center">Items</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($orders as $order): ?>
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-700">
                        <?= e($order['order_number']) ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800"><?= e($order['customer_name']) ?></div>
                        <div class="text-xs text-slate-400"><?= e($order['customer_email']) ?></div>
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600 font-medium">
                        <?= (int)$order['item_count'] ?>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-800">
                        <?= format_currency($order['grand_total']) ?>
                    </td>
                    <td class="px-4 py-3">
                        <?= paymentStatusBadge($order['payment_status']) ?>
                    </td>
                    <td class="px-4 py-3">
                        <?= orderStatusBadge($order['order_status']) ?>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-500">
                        <?= date('d M Y', strtotime($order['created_at'])) ?><br>
                        <span class="text-slate-400"><?= date('h:i A', strtotime($order['created_at'])) ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?= url('/admin/order-detail.php?id=' . (int)$order['id']) ?>"
                           class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline text-xs">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$orders): ?>
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                        <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                        No orders found matching your criteria.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between text-xs text-slate-500">
        <p>Showing <?= count($orders) ?> of <?= (int)$total ?> orders &bull; Page <?= (int)$page ?> of <?= max(1, (int)$pages) ?></p>
        <?php if ($pages > 1): ?>
        <div class="inline-flex gap-1">
            <?php if ($page > 1): ?>
                <a href="<?= url('/admin/orders.php') . ordersQs($page - 1, $search, $statusFilter) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 3); $p <= min($pages, $page + 3); $p++): ?>
                <a href="<?= url('/admin/orders.php') . ordersQs($p, $search, $statusFilter) ?>"
                   class="px-3 py-1.5 rounded-lg border <?= $p === $page ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'border-slate-200 bg-white hover:bg-slate-50' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= url('/admin/orders.php') . ordersQs($page + 1, $search, $statusFilter) ?>"
                   class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
