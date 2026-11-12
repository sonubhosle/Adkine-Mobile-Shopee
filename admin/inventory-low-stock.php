<?php
/**
 * MobileShopFlat — Admin Low Stock Alerts
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

$items = inventory_get_low_stock_alerts();
?>

<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="<?= url('/admin/inventory.php') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Back to Inventory
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
                Low-Stock Alerts
            </h1>
            <p class="mt-1 text-sm text-slate-500">Products currently at or below their configured minimum safety stock threshold.</p>
        </div>
        <a href="<?= url('/admin/purchases.php') ?>" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-700 transition">
            <i data-lucide="shopping-cart" class="w-4 h-4"></i> Create Purchase Order
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3.5">Product</th>
                    <th class="px-5 py-3.5">SKU</th>
                    <th class="px-5 py-3.5">Variant</th>
                    <th class="px-5 py-3.5 text-right">Current Stock</th>
                    <th class="px-5 py-3.5 text-right">Reorder Threshold</th>
                    <th class="px-5 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-5 py-3.5 font-semibold text-slate-900"><?= e($item['product_name']) ?></td>
                    <td class="px-5 py-3.5 font-mono text-xs text-slate-500"><?= e($item['product_sku'] ?? '') ?></td>
                    <td class="px-5 py-3.5 text-slate-600"><?= e($item['variant_name'] ?? 'Standard') ?></td>
                    <td class="px-5 py-3.5 text-right font-bold text-rose-600"><?= (int)$item['current_stock'] ?></td>
                    <td class="px-5 py-3.5 text-right text-slate-600 font-medium"><?= (int)$item['min_stock_threshold'] ?></td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="<?= url('/admin/inventory.php') ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline">Restock</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-sm text-emerald-600">
                        <i data-lucide="check-circle" class="w-8 h-8 mx-auto text-emerald-500 mb-2"></i>
                        All inventory levels are healthy! No products are currently below safety thresholds.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

