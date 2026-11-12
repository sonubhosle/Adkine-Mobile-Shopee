<?php
/**
 * MobileShopFlat — Admin Stock Movements Log
 */

require_once __DIR__ . '/../includes/admin-header.php';

$search = trim((string)($_GET['search'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 25;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE (p.name LIKE ? OR sm.reference_id LIKE ? OR sm.movement_type LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$total = (int)(db_fetch_value(
    "SELECT COUNT(*) FROM stock_movements sm
     JOIN products p ON sm.product_id = p.id
     $where",
    $params
) ?? 0);
$pages = (int)ceil($total / $limit);

$movements = db_fetch_all(
    "SELECT sm.*, p.name AS product_name, p.sku, pv.variant_name, u.full_name AS staff_name
     FROM stock_movements sm
     JOIN products p ON sm.product_id = p.id
     LEFT JOIN product_variants pv ON sm.variant_id = pv.id
     LEFT JOIN users u ON sm.created_by = u.id
     $where
     ORDER BY sm.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);
?>

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="<?= url('/admin/inventory.php') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Inventory
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Stock Movements Audit</h1>
            <p class="mt-1 text-sm text-slate-500">Immutable chronological log of all stock increases, deductions, sales, and corrections.</p>
        </div>
        <form method="GET" action="<?= url('/admin/inventory-movements.php') ?>" class="flex gap-2">
            <input name="search" value="<?= e($search) ?>" placeholder="Product or reference..." class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 shadow-xs">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Search</button>
            <?php if ($search !== ''): ?>
                <a href="<?= url('/admin/inventory-movements.php') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3.5">Timestamp</th>
                    <th class="px-5 py-3.5">Product</th>
                    <th class="px-5 py-3.5">Movement Type</th>
                    <th class="px-5 py-3.5 text-right">Units</th>
                    <th class="px-5 py-3.5">Reference ID</th>
                    <th class="px-5 py-3.5">Staff / Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($movements as $m): ?>
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="whitespace-nowrap px-5 py-3.5 text-xs text-slate-500 font-mono">
                        <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="font-semibold text-slate-900 block"><?= e($m['product_name']) ?></span>
                        <span class="text-xs text-slate-400 font-mono"><?= e($m['sku']) ?><?= !empty($m['variant_name']) ? ' &bull; ' . e($m['variant_name']) : '' ?></span>
                    </td>
                    <td class="px-5 py-3.5 text-xs">
                        <span class="px-2 py-0.5 rounded-md font-semibold <?= (int)$m['quantity'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                            <?= e(str_replace('_', ' ', $m['movement_type'])) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right font-bold <?= (int)$m['quantity'] > 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= (int)$m['quantity'] > 0 ? '+' . (int)$m['quantity'] : (int)$m['quantity'] ?>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-600 font-mono">
                        <?= e($m['reference_id'] ?? '—') ?>
                    </td>
                    <td class="px-5 py-3.5 text-xs text-slate-600">
                        <span class="font-medium text-slate-800"><?= e($m['staff_name'] ?? 'System') ?></span>
                        <?php if (!empty($m['notes'])): ?>
                            <span class="block text-slate-400 mt-0.5 italic"><?= e($m['notes']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($movements)): ?>
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No stock movements recorded.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between text-xs text-slate-500">
        <p>Showing <?= count($movements) ?> of <?= (int)$total ?> records &bull; Page <?= (int)$page ?> of <?= max(1, (int)$pages) ?></p>
        <?php if ($pages > 1): ?>
        <div class="inline-flex gap-1">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a href="<?= url('/admin/inventory-movements.php?page=' . $p . ($search !== '' ? '&search=' . urlencode($search) : '')) ?>" class="px-2.5 py-1 rounded border <?= $p === $page ? 'bg-indigo-600 text-white font-bold' : 'border-slate-200 bg-white' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

