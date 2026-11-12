<?php
/**
 * MobileShopFlat — Admin Product Create
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string)($_POST['name'] ?? ''));
    $sku = strtoupper(trim((string)($_POST['sku'] ?? '')));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $modelNumber = trim((string)($_POST['model_number'] ?? '')) ?: null;
    $barcode = trim((string)($_POST['barcode'] ?? '')) ?: null;
    $basePrice = (float)($_POST['base_price'] ?? 0);
    $salePriceInput = trim((string)($_POST['sale_price'] ?? ''));
    $salePrice = $salePriceInput !== '' ? (float)$salePriceInput : null;
    $costPrice = (float)($_POST['cost_price'] ?? 0);
    $initialStock = max(0, (int)($_POST['initial_stock'] ?? 0));
    $warrantyMonths = max(0, (int)($_POST['warranty_months'] ?? 12));
    $shortDesc = trim((string)($_POST['short_desc'] ?? '')) ?: null;
    $description = trim((string)($_POST['description'] ?? '')) ?: null;
    $specifications = trim((string)($_POST['specifications'] ?? '')) ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $hasVariants = isset($_POST['has_variants']) ? 1 : 0;

    $old = $_POST;

    // Validation
    if ($name === '' || mb_strlen($name) > 200) {
        $errors[] = 'A valid product name is required (max 200 chars).';
    }
    if ($sku === '' || mb_strlen($sku) > 100) {
        $errors[] = 'A valid unique SKU is required (max 100 chars).';
    } else {
        $exists = db_fetch_value("SELECT id FROM products WHERE sku = ?", [$sku]);
        if ($exists) {
            $errors[] = 'SKU already exists in the catalog.';
        }
    }
    if ($categoryId <= 0) {
        $errors[] = 'Please select a valid product category.';
    }
    if ($brandId <= 0) {
        $errors[] = 'Please select a valid brand.';
    }
    if ($basePrice <= 0) {
        $errors[] = 'Base price must be greater than zero.';
    }
    if ($salePrice !== null && $salePrice >= $basePrice) {
        $errors[] = 'Sale price must be lower than base price.';
    }

    // Image Upload
    $thumbnailPath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            $errors[] = 'Only JPG, PNG, and WebP images are allowed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Product image must not exceed 5 MB.';
        } else {
            $ext = $allowed[$mime];
            $dir = ROOT_PATH . '/uploads/products';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $fileName = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target = $dir . '/' . $fileName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $thumbnailPath = '/uploads/products/' . $fileName;
            } else {
                $errors[] = 'Failed to upload image file.';
            }
        }
    }

    if (empty($errors)) {
        // Generate slug
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = $baseSlug;
        $counter = 1;
        while (db_fetch_value("SELECT id FROM products WHERE slug = ?", [$slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        db_begin();
        try {
            $productId = db_insert(
                'INSERT INTO products (category_id, brand_id, name, slug, sku, barcode, model_number, short_desc, description, specifications, thumbnail, base_price, sale_price, cost_price, has_variants, is_featured, is_active, warranty_months) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $categoryId, $brandId, $name, $slug, $sku, $barcode, $modelNumber,
                    $shortDesc, $description, $specifications, $thumbnailPath,
                    $basePrice, $salePrice, $costPrice, $hasVariants, $isFeatured, $isActive, $warrantyMonths
                ]
            );

            // Add opening inventory if any
            if ($initialStock > 0) {
                db_insert(
                    "INSERT INTO inventory (product_id, variant_id, current_stock, min_stock_threshold) VALUES (?, NULL, ?, 5)",
                    [$productId, $initialStock]
                );
                db_query(
                    "INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, cost_per_unit, reference_id, reference_type, notes, created_by) VALUES (?, NULL, 'opening_stock', ?, ?, 'OPENING', 'system', 'Opening inventory', ?)",
                    [$productId, $initialStock, $costPrice, auth_id()]
                );
            } else {
                db_insert(
                    "INSERT INTO inventory (product_id, variant_id, current_stock, min_stock_threshold) VALUES (?, NULL, 0, 5)",
                    [$productId]
                );
            }

            db_commit();
            set_flash('success', 'Product created successfully.');
            redirect(url('/admin/products.php'));
        } catch (\Throwable $e) {
            db_rollback();
            if ($thumbnailPath && file_exists(ROOT_PATH . $thumbnailPath)) {
                @unlink(ROOT_PATH . $thumbnailPath);
            }
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$categories = db_fetch_all("SELECT id, name FROM categories WHERE is_active=1 ORDER BY name");
$brands     = db_fetch_all("SELECT id, name FROM brands WHERE is_active=1 ORDER BY name");
?>

<div class="mx-auto max-w-6xl">
    <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a href="<?= url('/admin/products.php') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-indigo-600">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                Back to Products
            </a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Add Product</h1>
            <p class="mt-1 text-sm text-slate-500">Add a catalog item, set its pricing, and publish it to the store.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Catalog Entry
        </span>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800">
            <ul class="list-disc pl-4 space-y-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/product-create.php') ?>" enctype="multipart/form-data" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
        <?= csrf_field() ?>

        <section class="border-b border-slate-200 px-5 py-6 sm:px-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">
                    <i data-lucide="package" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Product Identity</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Name, code, and catalog placement</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block text-sm font-semibold text-slate-700">Product Name <span class="text-rose-500">*</span>
                    <input name="name" required maxlength="200" value="<?= e($old['name'] ?? '') ?>" placeholder="e.g. Apple iPhone 15 Pro" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">SKU <span class="text-rose-500">*</span>
                    <input name="sku" required maxlength="100" value="<?= e($old['sku'] ?? '') ?>" placeholder="e.g. IP15P-256-BLK" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Category <span class="text-rose-500">*</span>
                    <select name="category_id" required class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category['id'] ?>" <?= (int)($old['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block text-sm font-semibold text-slate-700">Brand <span class="text-rose-500">*</span>
                    <select name="brand_id" required class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                        <option value="">Select brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= (int)$brand['id'] ?>" <?= (int)($old['brand_id'] ?? 0) === (int)$brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block text-sm font-semibold text-slate-700">Model Number
                    <input name="model_number" maxlength="100" value="<?= e($old['model_number'] ?? '') ?>" placeholder="Optional" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Barcode
                    <input name="barcode" maxlength="100" value="<?= e($old['barcode'] ?? '') ?>" placeholder="Optional" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
            </div>
        </section>

        <section class="border-b border-slate-200 px-5 py-6 sm:px-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i data-lucide="indian-rupee" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Pricing & Inventory</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Set sale prices, opening quantity, and warranty</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block text-sm font-semibold text-slate-700">Base Price (₹) <span class="text-rose-500">*</span>
                    <input type="number" name="base_price" min="0.01" step="0.01" required value="<?= e((string)($old['base_price'] ?? '')) ?>" placeholder="0.00" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Sale Price (₹)
                    <input type="number" name="sale_price" min="0.01" step="0.01" value="<?= e((string)($old['sale_price'] ?? '')) ?>" placeholder="Optional" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Cost Price (₹) <span class="text-rose-500">*</span>
                    <input type="number" name="cost_price" min="0" step="0.01" required value="<?= e((string)($old['cost_price'] ?? '0')) ?>" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Initial Stock
                    <input type="number" name="initial_stock" min="0" value="<?= e((string)($old['initial_stock'] ?? '0')) ?>" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Warranty (Months)
                    <input type="number" name="warranty_months" min="0" max="120" value="<?= e((string)($old['warranty_months'] ?? '12')) ?>" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
            </div>
        </section>

        <section class="border-b border-slate-200 px-5 py-6 sm:px-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-700">
                    <i data-lucide="align-left" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Product Details</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Descriptions and technical specifications</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4">
                <label class="block text-sm font-semibold text-slate-700">Short Description
                    <input name="short_desc" maxlength="500" value="<?= e($old['short_desc'] ?? '') ?>" placeholder="A concise summary for product listings" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600">
                </label>
                <label class="block text-sm font-semibold text-slate-700">Full Description
                    <textarea name="description" rows="4" placeholder="Describe the product and its key benefits" class="mt-1.5 block w-full resize-y rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"><?= e($old['description'] ?? '') ?></textarea>
                </label>
                <label class="block text-sm font-semibold text-slate-700">Specifications (JSON)
                    <textarea name="specifications" rows="3" placeholder='{"display":"6.1-inch","ram":"8GB"}' class="mt-1.5 block w-full resize-y rounded-lg border border-slate-300 bg-slate-50 px-3.5 py-3 font-mono text-sm font-normal text-slate-900 shadow-xs outline-none transition focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"><?= e($old['specifications'] ?? '') ?></textarea>
                </label>
            </div>
        </section>

        <section class="px-5 py-6 sm:px-7">
            <div class="mb-5 flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i data-lucide="image" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Publishing</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Choose a thumbnail and store visibility</p>
                </div>
            </div>
            <label class="block text-sm font-semibold text-slate-700">Product Thumbnail
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-1.5 block w-full rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3.5 py-3 text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-100">
                <span class="mt-1 block text-xs font-normal text-slate-500">JPG, PNG, or WebP; maximum 5 MB.</span>
            </label>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="flex min-h-12 items-center gap-3 rounded-lg border border-slate-200 px-3.5 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50/40">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Active in store</span>
                </label>
                <label class="flex min-h-12 items-center gap-3 rounded-lg border border-slate-200 px-3.5 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50/40">
                    <input type="checkbox" name="is_featured" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Featured product</span>
                </label>
                <label class="flex min-h-12 items-center gap-3 rounded-lg border border-slate-200 px-3.5 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50/40">
                    <input type="checkbox" name="has_variants" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Has variants</span>
                </label>
            </div>
        </section>

        <footer class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <a href="<?= url('/admin/products.php') ?>" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</a>
            <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-xs transition hover:bg-indigo-700 focus:outline-none">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Create Product
            </button>
        </footer>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

