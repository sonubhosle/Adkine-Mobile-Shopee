<?php
/**
 * MobileShopFlat — Admin Inventory Levels & Stock Adjustment
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

// Handle Stock Adjustment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $variantId = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
    $type = trim((string)($_POST['type'] ?? 'in'));
    $quantity = (int)($_POST['quantity'] ?? 0);
    $notes = trim((string)($_POST['notes'] ?? ''));

    if ($productId <= 0 || $quantity < 0 || $notes === '') {
        set_flash('error', 'Please provide a valid product, quantity, and reason.');
    } else {
        try {
            if ($type === 'in') {
                inventory_add_stock($productId, $variantId, $quantity, 'manual_in', 'MANUAL-' . time(), 0.00, $notes, auth_id());
                set_flash('success', "Added {$quantity} units to inventory.");
            } elseif ($type === 'out') {
                inventory_deduct_stock($productId, $variantId, $quantity, 'manual_out', 'MANUAL-' . time(), $notes, auth_id());
                set_flash('success', "Deducted {$quantity} units from inventory.");
            } elseif ($type === 'adjustment') {
                inventory_adjust_stock($productId, $variantId, $quantity, $notes, auth_id());
                set_flash('success', "Stock level adjusted to exact count of {$quantity}.");
            }
        } catch (\Throwable $e) {
            set_flash('error', $e->getMessage());
        }
    }
    redirect(url('/admin/inventory.php'));
}

$search = trim((string)($_GET['search'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE (p.name LIKE ? OR p.sku LIKE ? OR pv.sku LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$total = (int)(db_fetch_value(
    "SELECT COUNT(*) FROM inventory i
     JOIN products p ON i.product_id = p.id
     LEFT JOIN product_variants pv ON i.variant_id = pv.id
     $where",
    $params
) ?? 0);
$pages = (int)ceil($total / $limit);

$items = db_fetch_all(
    "SELECT i.*, p.name AS product_name, COALESCE(pv.sku, p.sku) AS sku, pv.variant_name
     FROM inventory i
     JOIN products p ON i.product_id = p.id
     LEFT JOIN product_variants pv ON i.variant_id = pv.id
     $where
     ORDER BY i.current_stock ASC
     LIMIT $limit OFFSET $offset",
    $params
);

// All items for adjustment dropdown
$allItems = db_fetch_all(
    "SELECT i.product_id, i.variant_id, i.current_stock, p.name AS product_name, pv.variant_name
     FROM inventory i
     JOIN products p ON i.product_id = p.id
     LEFT JOIN product_variants pv ON i.variant_id = pv.id
     WHERE p.is_active = 1
     ORDER BY p.name ASC"
);
?>

<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-700">Stock control</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Inventory Levels</h1>
            <p class="mt-1 text-sm text-slate-500">Review stock availability and record inventory adjustments.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= url('/admin/inventory-movements.php') ?>" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <i data-lucide="history" class="h-4 w-4 text-slate-500"></i> Movement History
            </a>
            <a href="<?= url('/admin/inventory-low-stock.php') ?>" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3.5 text-sm font-semibold text-rose-800 hover:bg-rose-100 transition">
                <i data-lucide="alert-triangle" class="h-4 w-4"></i> Low Stock Alerts
            </a>
        </div>
    </header>

    <!-- Adjust Stock Form -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <i data-lucide="package-plus" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Adjust Stock</h2>
                <p class="mt-0.5 text-xs text-slate-500">Receive, deduct, or reconcile physical inventory</p>
            </div>
        </div>
        <form method="POST" action="<?= url('/admin/inventory.php') ?>" class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4 sm:p-6" onsubmit="const parts = this.stock_item.value.split(':'); this.product_id.value = parts[0]; this.variant_id.value = parts[1] || '';">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id">
            <input type="hidden" name="variant_id">

            <label class="block text-sm font-semibold text-slate-700">Product / Variant <span class="text-rose-500">*</span>
                <select name="stock_item" required class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                    <option value="">Select item...</option>
                    <?php foreach ($allItems as $it): ?>
                        <option value="<?= (int)$it['product_id'] ?>:<?= (int)($it['variant_id'] ?? 0) ?>">
                            <?= e($it['product_name']) ?><?= $it['variant_name'] ? ' (' . e($it['variant_name']) . ')' : '' ?> &bull; <?= (int)$it['current_stock'] ?> in stock
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">Operation
                <select name="type" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                    <option value="in">Stock In (+)</option>
                    <option value="out">Stock Out (-)</option>
                    <option value="adjustment">Set Exact Count (=)</option>
                </select>
            </label>
            <label class="block text-sm font-semibold text-slate-700">Quantity <span class="text-rose-500">*</span>
                <input type="number" name="quantity" min="0" required placeholder="Units count" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Reason / Reference <span class="text-rose-500">*</span>
                <input name="notes" maxlength="255" required placeholder="e.g. Audit correction, damage write-off" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
            </label>
            <div class="flex items-end sm:col-span-2 xl:col-span-4">
                <button type="submit" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700">
                    <i data-lucide="arrow-left-right" class="h-4 w-4"></i> Apply Adjustment
                </button>
            </div>
        </form>
    </section>

    <!-- Stock On Hand Table -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Stock on Hand</h2>
                <p class="mt-0.5 text-xs text-slate-500">Showing <?= count($items) ?> of <?= (int)$total ?> records &bull; Page <?= (int)$page ?> of <?= max(1, (int)$pages) ?></p>
            </div>
            <form method="GET" action="<?= url('/admin/inventory.php') ?>" class="flex w-full gap-2 sm:w-auto">
                <div class="relative min-w-0 flex-1 sm:w-72">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="<?= e($search) ?>" placeholder="Product name or SKU..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Product</th>
                        <th class="px-5 py-3.5">SKU</th>
                        <th class="px-5 py-3.5">Variant</th>
                        <th class="px-5 py-3.5">Rack Location</th>
                        <th class="px-5 py-3.5 text-right">Available Units</th>
                        <th class="px-5 py-3.5 text-right">Alert Threshold</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($items as $item): ?>
                        <?php $isLow = (int)$item['current_stock'] <= (int)$item['min_stock_threshold']; ?>
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-900"><?= e($item['product_name']) ?></td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500"><?= e($item['sku']) ?></td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600"><?= e($item['variant_name'] ?? 'Standard') ?></td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600"><?= e($item['location_rack'] ?? 'Main Display') ?></td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="inline-flex min-w-9 justify-center rounded-md px-2.5 py-1 text-xs font-bold <?= $isLow ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-800' ?>">
                                    <?= (int)$item['current_stock'] ?>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right text-slate-600 font-medium"><?= (int)$item['min_stock_threshold'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$items): ?>
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No inventory records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
            <p>Showing <?= count($items) ?> of <?= (int)$total ?> records</p>
            <?php if ($pages > 1): ?>
            <div class="inline-flex gap-1">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <a href="<?= url('/admin/inventory.php?page=' . $p . ($search !== '' ? '&search=' . urlencode($search) : '')) ?>" class="px-2.5 py-1 rounded border <?= $p === $page ? 'bg-indigo-600 text-white font-bold' : 'border-slate-200 bg-white' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

