<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Filters & Pagination ──────────────────────────────────────────────────────
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset   = ($page - 1) * $per_page;

$status    = trim($_GET['status'] ?? '');
$payment   = trim($_GET['payment'] ?? '');
$supplier  = (int)($_GET['supplier'] ?? 0);
$search    = trim($_GET['search'] ?? '');

$where = [];
$params = [];

if ($status !== '' && in_array($status, ['draft', 'received', 'cancelled'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
if ($payment !== '' && in_array($payment, ['pending', 'partial', 'paid'], true)) {
    $where[] = 'p.payment_status = ?';
    $params[] = $payment;
}
if ($supplier > 0) {
    $where[] = 'p.supplier_id = ?';
    $params[] = $supplier;
}
if ($search !== '') {
    $where[] = '(p.purchase_number LIKE ? OR p.invoice_number LIKE ? OR s.company_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_rows  = (int)db_fetch_value(
    "SELECT COUNT(*) FROM purchases p JOIN suppliers s ON s.id = p.supplier_id $where_sql",
    $params
);
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$purchases = db_fetch_all(
    "SELECT p.*, s.company_name, s.name AS supplier_contact, u.full_name AS creator_name,
            (SELECT COUNT(*) FROM purchase_items pi WHERE pi.purchase_id = p.id) AS items_count
       FROM purchases p
       JOIN suppliers s ON s.id = p.supplier_id
  LEFT JOIN users u ON u.id = p.created_by
       $where_sql
   ORDER BY p.purchase_date DESC, p.created_at DESC
      LIMIT $per_page OFFSET $offset",
    $params
);

$suppliers_list = db_fetch_all("SELECT id, company_name FROM suppliers ORDER BY company_name ASC");

// Quick Stats
$total_spent = (float)db_fetch_value("SELECT COALESCE(SUM(total_amount), 0) FROM purchases WHERE status = 'received'");
$pending_drafts = (int)db_fetch_value("SELECT COUNT(*) FROM purchases WHERE status = 'draft'");
$received_count = (int)db_fetch_value("SELECT COUNT(*) FROM purchases WHERE status = 'received'");

$pageTitle = 'Purchases & Stock-In';
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                Procurement
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Purchase Orders</h1>
            <p class="text-sm text-slate-500 mt-0.5">Supplier orders, receiving stock-in batches, and invoice tracking.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="<?= url('/admin/purchase-create.php') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Purchase Draft</span>
            </a>
            <a href="<?= url('/admin/suppliers.php') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i data-lucide="truck" class="w-4 h-4 text-slate-400"></i>
                <span>Suppliers</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Received Stock</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= format_currency($total_spent) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Drafts (Pending Delivery)</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($pending_drafts) ?> POs</p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="boxes" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Completed Orders</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($received_count) ?></p>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form method="GET" action="<?= url('/admin/purchases.php') ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            <div class="relative lg:col-span-2">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search PO #, invoice #, supplier..."
                       class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
            </div>

            <select name="status" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All PO Statuses</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft (Pending Delivery)</option>
                <option value="received" <?= $status === 'received' ? 'selected' : '' ?>>Received (In Stock)</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <select name="supplier" class="px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers_list as $sl): ?>
                    <option value="<?= (int)$sl['id'] ?>" <?= $supplier === (int)$sl['id'] ? 'selected' : '' ?>><?= e($sl['company_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                    Filter
                </button>
                <?php if ($search !== '' || $status !== '' || $supplier > 0 || $payment !== ''): ?>
                    <a href="<?= url('/admin/purchases.php') ?>" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition" title="Clear">
                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Purchases Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Purchase Order</th>
                        <th class="px-5 py-3.5">Supplier</th>
                        <th class="px-5 py-3.5">Purchase Date</th>
                        <th class="px-5 py-3.5">Items</th>
                        <th class="px-5 py-3.5">Total Amount</th>
                        <th class="px-5 py-3.5">Receipt State</th>
                        <th class="px-5 py-3.5">Payment</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                <i data-lucide="shopping-cart" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p class="text-sm font-semibold">No purchase orders found.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Create a draft purchase order to restock your inventory.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $p): ?>
                            <?php
                            $status_class = match ($p['status']) {
                                'received' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100',
                                default => 'bg-amber-50 text-amber-800 border-amber-100',
                            };
                            $payment_class = match ($p['payment_status']) {
                                'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'partial' => 'bg-amber-50 text-amber-700 border-amber-100',
                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                            ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-5 py-3.5">
                                    <a href="<?= url('/admin/purchase-detail.php?id=' . (int)$p['id']) ?>" class="font-bold text-blue-600 hover:text-blue-800">
                                        <?= e($p['purchase_number']) ?>
                                    </a>
                                    <?php if (!empty($p['invoice_number'])): ?>
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                            Inv: <?= e($p['invoice_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= e($p['company_name']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= e($p['supplier_contact']) ?></div>
                                </td>
                                <td class="px-5 py-3.5 font-medium text-slate-600">
                                    <?= date('d M Y', strtotime($p['purchase_date'])) ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="font-semibold text-slate-800"><?= (int)$p['items_count'] ?> products</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= format_currency($p['total_amount']) ?></div>
                                    <div class="text-[10px] text-slate-400">Tax: <?= format_currency($p['tax_amount']) ?></div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $status_class ?>">
                                        <?= ucfirst($p['status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $payment_class ?>">
                                        <?= ucfirst($p['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <a href="<?= url('/admin/purchase-detail.php?id=' . (int)$p['id']) ?>"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition">
                                        <span>View</span>
                                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
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
                <span>Showing page <?= $page ?> of <?= $total_pages ?> (<?= $total_rows ?> total purchases)</span>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&supplier=<?= $supplier ?>&search=<?= urlencode($search) ?>"
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

