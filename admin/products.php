<?php
/**
 * MobileShopFlat — Admin Products List
 */

require_once __DIR__ . '/../includes/admin-header.php';

// Handle Delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        // Begin transaction
        db_begin();
        try {
            // Delete related order_items if any
            $orderCount = (int)db_fetch_value("SELECT COUNT(*) FROM order_items WHERE product_id = ?", [$id]);
            if ($orderCount > 0) {
                db_query("DELETE FROM order_items WHERE product_id = ?", [$id]);
            }
            // Delete related pos_sale_items if any
            $posCount = (int)db_fetch_value("SELECT COUNT(*) FROM pos_sale_items WHERE product_id = ?", [$id]);
            if ($posCount > 0) {
                db_query("DELETE FROM pos_sale_items WHERE product_id = ?", [$id]);
            }
            // Remove product thumbnail if exists
            $product = db_fetch_one("SELECT thumbnail FROM products WHERE id = ?", [$id]);
            if ($product && !empty($product['thumbnail'])) {
                $thumbPath = ROOT_PATH . '/' . ltrim($product['thumbnail'], '/');
                if (is_file($thumbPath)) {
                    @unlink($thumbPath);
                }
            }
            // Delete the product
            db_query("DELETE FROM products WHERE id = ?", [$id]);
            db_commit();
            set_flash('success', 'Product deleted permanently.');
        } catch (Throwable $e) {
            db_rollback();
            set_flash('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }
    redirect(url('/admin/products.php'));
}

// Handle Deactivation via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deactivate') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_query("UPDATE products SET is_active = 0 WHERE id = ?", [$id]);
        set_flash('success', 'Product deactivated successfully.');
    }
    redirect(url('/admin/products.php'));
}

$search = trim((string)($_GET['search'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE (p.name LIKE ? OR p.sku LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

$total = (int)(db_fetch_value("SELECT COUNT(*) FROM products p $where", $params) ?? 0);
$pages = (int)ceil($total / $limit);

$products = db_fetch_all(
    "SELECT p.*, c.name AS category_name, b.name AS brand_name,
            COALESCE(SUM(i.current_stock), 0) AS total_stock
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     LEFT JOIN inventory i ON i.product_id = p.id
     $where
     GROUP BY p.id
     ORDER BY p.created_at DESC
     LIMIT $limit OFFSET $offset",
    $params
);
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Products</h1>
            <p class="mt-1 text-sm text-slate-500">Catalog, pricing, and published inventory</p>
        </div>
        <a href="<?= url('/admin/product-create.php') ?>" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
            <i data-lucide="plus" class="w-4 h-4"></i> Add product
        </a>
    </div>

    <form method="GET" action="<?= url('/admin/products.php') ?>" class="flex gap-2">
        <label class="sr-only" for="product-search">Search products</label>
        <div class="relative w-72">
            <input id="product-search" name="search" value="<?= e($search) ?>" placeholder="Name or SKU..." class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 pl-9 text-sm shadow-xs focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        </div>
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?= url('/admin/products.php') ?>" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">Clear</a>
        <?php endif; ?>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
                <tr>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Category / Brand</th>
                    <th class="px-4 py-3 text-right">Price</th>
                    <th class="px-4 py-3 text-right">Stock</th>
                    <th class="px-4 py-3">State</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <?php foreach ($products as $product): ?>
                <tr class="hover:bg-slate-50/70 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <?php if (!empty($product['thumbnail'])): ?>
                                <img src="<?= asset($product['thumbnail']) ?>" alt="" class="w-10 h-10 rounded-lg object-cover border border-slate-100 shrink-0">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 shrink-0">
                                    <i data-lucide="package" class="w-5 h-5"></i>
                                </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <a href="<?= url('/admin/product-edit.php?id=' . (int)$product['id']) ?>" class="font-semibold text-slate-900 hover:text-indigo-600 truncate block">
                                    <?= e($product['name']) ?>
                                </a>
                                <div class="text-xs text-slate-500 font-mono"><?= e($product['sku']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        <div class="font-medium text-slate-800"><?= e($product['category_name'] ?? '—') ?></div>
                        <span class="block text-xs text-slate-400"><?= e($product['brand_name'] ?? '—') ?></span>
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-slate-800">
                        <?php if ($product['sale_price'] !== null && $product['sale_price'] > 0): ?>
                            <span class="text-rose-600 font-bold"><?= format_currency($product['sale_price']) ?></span>
                            <span class="block text-xs text-slate-400 line-through"><?= format_currency($product['base_price']) ?></span>
                        <?php else: ?>
                            <span class="font-bold"><?= format_currency($product['base_price']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right <?= (int)$product['total_stock'] <= 5 ? 'font-bold text-rose-600' : 'text-slate-700 font-semibold' ?>">
                        <?= (int)$product['total_stock'] ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $product['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                            <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-3">
                            <a class="font-semibold text-indigo-600 hover:text-indigo-800 hover:underline text-xs" href="<?= url('/admin/product-edit.php?id=' . (int)$product['id']) ?>">Edit</a>
                                                        <button type="button" class="delete-btn font-semibold text-rose-600 hover:text-rose-800 hover:underline text-xs" data-id="<?= (int)$product['id'] ?>">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                        <i data-lucide="package-x" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                        No products found matching your criteria.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between text-xs text-slate-500">
        <p>Showing <?= count($products) ?> of <?= (int)$total ?> products &bull; Page <?= (int)$page ?> of <?= max(1, (int)$pages) ?></p>
        <?php if ($pages > 1): ?>
        <div class="inline-flex gap-1">
            <?php if ($page > 1): ?>
                <a href="<?= url('/admin/products.php?page=' . ($page - 1) . ($search !== '' ? '&search=' . urlencode($search) : '')) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a href="<?= url('/admin/products.php?page=' . $p . ($search !== '' ? '&search=' . urlencode($search) : '')) ?>" class="px-3 py-1.5 rounded-lg border <?= $p === $page ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'border-slate-200 bg-white hover:bg-slate-50' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= url('/admin/products.php?page=' . ($page + 1) . ($search !== '' ? '&search=' . urlencode($search) : '')) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Next</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-sm w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Delete this product?</h3>
            <p class="text-sm text-gray-600 mb-6">This action cannot be undone. Are you sure you want to continue?</p>
            <div class="flex justify-end space-x-3">
                <button id="cancel-delete" type="button" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                <button id="confirm-delete" type="button" class="px-4 py-2 text-sm text-white bg-rose-600 rounded hover:bg-rose-700">Delete</button>
            </div>
        </div>
    </div>

    <!-- Hidden delete form -->
    <form id="delete-form" method="POST" action="<?= url('/admin/products.php') ?>" class="hidden">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete-id" value="">
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('delete-modal');
            const confirmBtn = document.getElementById('confirm-delete');
            const cancelBtn = document.getElementById('cancel-delete');
            const deleteForm = document.getElementById('delete-form');
            const deleteIdInput = document.getElementById('delete-id');

            // Open modal on delete button click
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    deleteIdInput.value = id;
                    modal.classList.remove('hidden');
                });
            });

            // Cancel button hides modal
            cancelBtn.addEventListener('click', function () {
                modal.classList.add('hidden');
            });

            // Confirm button submits hidden form
            confirmBtn.addEventListener('click', function () {
                modal.classList.add('hidden');
                // Submit the form programmatically
                deleteForm.submit();
            });
        });
    </script>


