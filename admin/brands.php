<?php
/**
 * MobileShopFlat — Admin Brands Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'store') {
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            set_flash('error', 'Brand name is required.');
        } else {
            if ($slug === '') {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            }
            $exists = db_fetch_value("SELECT id FROM brands WHERE slug = ?", [$slug]);
            if ($exists) {
                set_flash('error', 'Brand slug already exists.');
            } else {
                db_insert(
                    "INSERT INTO brands (name, slug, is_active) VALUES (?, ?, ?)",
                    [$name, $slug, $isActive]
                );
                set_flash('success', 'Brand added successfully.');
            }
        }
        redirect(url('/admin/brands.php'));
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($id > 0 && $name !== '' && $slug !== '') {
            $exists = db_fetch_value("SELECT id FROM brands WHERE slug = ? AND id != ?", [$slug, $id]);
            if ($exists) {
                set_flash('error', 'Brand slug is already in use.');
            } else {
                db_query("UPDATE brands SET name = ?, slug = ?, is_active = ? WHERE id = ?", [$name, $slug, $isActive, $id]);
                set_flash('success', 'Brand updated.');
            }
        }
        redirect(url('/admin/brands.php'));
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            db_query("UPDATE brands SET is_active = 0 WHERE id = ?", [$id]);
            set_flash('success', 'Brand deactivated.');
        }
        redirect(url('/admin/brands.php'));
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE (b.name LIKE ? OR b.slug LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

$total = (int)(db_fetch_value("SELECT COUNT(*) FROM brands b $where", $params) ?? 0);
$pages = (int)ceil($total / $limit);

$brands = db_fetch_all(
    "SELECT b.*, COUNT(p.id) AS product_count
     FROM brands b
     LEFT JOIN products p ON p.brand_id = b.id
     $where
     GROUP BY b.id
     ORDER BY b.name ASC
     LIMIT $limit OFFSET $offset",
    $params
);
?>

<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-700">Catalog setup</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Brands</h1>
            <p class="mt-1 text-sm text-slate-500">Manage brand identities and manufacturer catalog links.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-xs">
            <i data-lucide="tags" class="h-3.5 w-3.5 text-indigo-600"></i>
            <?= number_format((int)$total) ?> brands
        </span>
    </header>

    <!-- Add Brand Form -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">
                <i data-lucide="tag" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Add Brand</h2>
                <p class="mt-0.5 text-xs text-slate-500">Create a brand for the product catalog</p>
            </div>
        </div>
        <form method="POST" action="<?= url('/admin/brands.php') ?>" class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4 sm:p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="store">

            <label class="block text-sm font-semibold text-slate-700">Name <span class="text-rose-500">*</span>
                <input name="name" required maxlength="150" placeholder="e.g. Apple" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
            </label>
            <label class="block text-sm font-semibold text-slate-700">Slug <span class="text-xs font-normal text-slate-500">(optional)</span>
                <input name="slug" maxlength="150" placeholder="Auto-generated if blank" class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
            </label>
            <div class="flex items-end gap-3 sm:col-span-2">
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded text-indigo-600">
                    <span>Active</span>
                </label>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700">
                    <i data-lucide="plus" class="h-4 w-4"></i> Add Brand
                </button>
            </div>
        </form>
    </section>

    <!-- Brand List -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Brand List</h2>
                <p class="mt-0.5 text-xs text-slate-500">Product counts and brand availability</p>
            </div>
            <form method="GET" action="<?= url('/admin/brands.php') ?>" class="flex w-full gap-2 sm:w-auto">
                <div class="relative min-w-0 flex-1 sm:w-72">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="<?= e($search) ?>" placeholder="Search brands..." class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </div>
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Brand</th>
                        <th class="px-5 py-3.5">Slug</th>
                        <th class="px-5 py-3.5 text-right">Products</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($brands as $brand): ?>
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-900"><?= e($brand['name']) ?></td>
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500"><?= e($brand['slug']) ?></td>
                            <td class="px-5 py-3.5 text-right">
                                <span class="inline-flex min-w-8 justify-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700"><?= (int)$brand['product_count'] ?></span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= $brand['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>">
                                    <span class="h-1.5 w-1.5 rounded-full <?= $brand['is_active'] ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                    <?= $brand['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="min-w-[28rem] px-5 py-3.5">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="<?= url('/admin/brands.php') ?>" class="flex items-center gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= (int)$brand['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= (int)$brand['is_active'] ?>">
                                        <input name="name" required maxlength="150" value="<?= e($brand['name']) ?>" class="w-36 rounded-md border border-slate-300 px-2.5 py-1.5 text-xs outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                                        <input name="slug" required maxlength="150" value="<?= e($brand['slug']) ?>" class="w-36 rounded-md border border-slate-300 px-2.5 py-1.5 font-mono text-xs outline-none focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                            <i data-lucide="check" class="h-3.5 w-3.5"></i> Save
                                        </button>
                                    </form>
                                    <?php if ($brand['is_active']): ?>
                                    <form method="POST" action="<?= url('/admin/brands.php') ?>" onsubmit="return confirm('Deactivate this brand?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$brand['id'] ?>">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                            <i data-lucide="ban" class="h-3.5 w-3.5"></i> Deactivate
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$brands): ?>
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No brands found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>Showing <?= count($brands) ?> of <?= (int)$total ?> brands</p>
            <p>Page <?= (int)$page ?> of <?= max(1, (int)$pages) ?></p>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

