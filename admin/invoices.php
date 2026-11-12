<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Filters & Pagination ──────────────────────────────────────────────────────
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$type   = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($type !== '' && in_array($type, ['order', 'repair', 'pos'], true)) {
    $where[] = 'i.invoice_type = ?';
    $params[] = $type;
}
if ($status !== '' && in_array($status, ['issued', 'paid', 'cancelled'], true)) {
    $where[] = 'i.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(i.invoice_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_rows  = (int)db_fetch_value(
    "SELECT COUNT(*) FROM invoices i LEFT JOIN users u ON u.id = i.user_id $where_sql",
    $params
);
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$invoices = db_fetch_all(
    "SELECT i.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
       FROM invoices i
  LEFT JOIN users u ON u.id = i.user_id
       $where_sql
   ORDER BY i.issued_at DESC
      LIMIT $per_page OFFSET $offset",
    $params
);

// Summary Stats
$total_invoiced = (float)db_fetch_value("SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE status != 'cancelled'");
$order_total    = (float)db_fetch_value("SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE invoice_type = 'order' AND status != 'cancelled'");
$pos_total      = (float)db_fetch_value("SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE invoice_type = 'pos' AND status != 'cancelled'");
$repair_total   = (float)db_fetch_value("SELECT COALESCE(SUM(grand_total), 0) FROM invoices WHERE invoice_type = 'repair' AND status != 'cancelled'");

$pageTitle = 'Invoices & Billing';
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Finance &amp; Accounts
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Invoices &amp; Billing</h1>
            <p class="text-sm text-slate-500 mt-0.5">Master billing records across Online Orders, In-Store POS, and Mobile Repairs.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="<?= url('/admin/pos.php') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                <i data-lucide="calculator" class="w-4 h-4"></i>
                <span>POS Cash Register</span>
            </a>
            <a href="<?= url('/admin/reports.php') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i data-lucide="bar-chart-3" class="w-4 h-4 text-slate-400"></i>
                <span>Sales Reports</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="receipt" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Invoiced</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= format_currency($total_invoiced) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <i data-lucide="shopping-bag" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Online Store Orders</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= format_currency($order_total) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="calculator" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Counter POS Sales</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= format_currency($pos_total) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Repairs Service</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= format_currency($repair_total) ?></p>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form method="GET" action="<?= url('/admin/invoices.php') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            <div class="relative lg:col-span-2">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search invoice #, customer name, email..."
                       class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
            </div>

            <select name="type" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All Invoice Types</option>
                <option value="order" <?= $type === 'order' ? 'selected' : '' ?>>Online Store Orders</option>
                <option value="pos" <?= $type === 'pos' ? 'selected' : '' ?>>Counter POS Sales</option>
                <option value="repair" <?= $type === 'repair' ? 'selected' : '' ?>>Device Repairs</option>
            </select>

            <select name="status" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All Statuses</option>
                <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued (Pending)</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                    Filter
                </button>
                <?php if ($search !== '' || $type !== '' || $status !== ''): ?>
                    <a href="<?= url('/admin/invoices.php') ?>" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition" title="Clear">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Invoice #</th>
                        <th class="px-5 py-3.5">Type &amp; Ref</th>
                        <th class="px-5 py-3.5">Customer</th>
                        <th class="px-5 py-3.5">Date Issued</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Grand Total</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                <i data-lucide="file-text" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p class="text-sm font-semibold">No invoices found.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Invoices are automatically generated for orders, repairs, and POS sales.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                            <?php
                            $type_badge = match ($inv['invoice_type']) {
                                'order'  => 'bg-blue-50 text-blue-700 border-blue-100',
                                'pos'    => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'repair' => 'bg-amber-50 text-amber-800 border-amber-100',
                                default  => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                            $status_badge = match ($inv['status']) {
                                'paid'      => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100',
                                default     => 'bg-amber-50 text-amber-800 border-amber-100',
                            };
                            ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-5 py-3.5">
                                    <a href="<?= url('/admin/invoice-view.php?id=' . (int)$inv['id']) ?>" class="font-bold text-blue-600 hover:text-blue-800">
                                        <?= e($inv['invoice_number']) ?>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase <?= $type_badge ?>">
                                        <?= e($inv['invoice_type']) ?>
                                    </span>
                                    <span class="block text-[11px] text-slate-400 font-mono mt-0.5">Ref #<?= (int)$inv['reference_id'] ?></span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= e($inv['customer_name'] ?? 'Walk-in Customer') ?></div>
                                    <?php if (!empty($inv['customer_phone'])): ?>
                                        <div class="text-[11px] text-slate-400"><?= e($inv['customer_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 font-medium text-slate-600">
                                    <?= date('d M Y, h:i A', strtotime($inv['issued_at'])) ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $status_badge ?>">
                                        <?= ucfirst($inv['status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= format_currency($inv['grand_total']) ?></div>
                                    <?php if ($inv['tax_amount'] > 0): ?>
                                        <div class="text-[10px] text-slate-400">Tax: <?= format_currency($inv['tax_amount']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="<?= url('/admin/invoice-view.php?id=' . (int)$inv['id']) ?>"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        <span>Print / View</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Showing page <?= $page ?> of <?= $total_pages ?> (<?= $total_rows ?> total invoices)</span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>"
                           class="px-2.5 py-1 rounded-lg border <?= $page === $i ? 'bg-blue-600 text-white border-blue-600 font-bold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

