<?php
/**
 * MobileShopFlat — Admin Product Edit & Variants
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

$id = (int)($_GET['id'] ?? 0);
$product = db_fetch_one("SELECT * FROM products WHERE id = ?", [$id]);

if (!$product) {
    set_flash('error', 'Product not found.');
    redirect(url('/admin/products.php'));
}

$errors = [];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $subAction = $_POST['sub_action'] ?? 'update_product';

    if ($subAction === 'update_product') {
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
        $warrantyMonths = max(0, (int)($_POST['warranty_months'] ?? 12));
        $shortDesc = trim((string)($_POST['short_desc'] ?? '')) ?: null;
        $description = trim((string)($_POST['description'] ?? '')) ?: null;
        $specifications = trim((string)($_POST['specifications'] ?? '')) ?: null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $hasVariants = isset($_POST['has_variants']) ? 1 : 0;

        if ($name === '' || mb_strlen($name) > 200) {
            $errors[] = 'A valid product name is required.';
        }
        if ($sku === '' || mb_strlen($sku) > 100) {
            $errors[] = 'A valid unique SKU is required.';
        } else {
            $exists = db_fetch_value("SELECT id FROM products WHERE sku = ? AND id != ?", [$sku, $id]);
            if ($exists) {
                $errors[] = 'SKU is already in use by another product.';
            }
        }
        if ($categoryId <= 0) $errors[] = 'Category is required.';
        if ($brandId <= 0) $errors[] = 'Brand is required.';
        if ($basePrice <= 0) $errors[] = 'Base price must be greater than zero.';
        if ($salePrice !== null && $salePrice >= $basePrice) {
            $errors[] = 'Sale price must be lower than base price.';
        }

        $newThumbnail = null;
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
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $fileName = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $target = $dir . '/' . $fileName;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $newThumbnail = '/uploads/products/' . $fileName;
                }
            }
        }

        if (empty($errors)) {
            $slug = $name === $product['name'] ? $product['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            
            db_query(
                'UPDATE products SET category_id = ?, brand_id = ?, name = ?, slug = ?, sku = ?, barcode = ?, model_number = ?, short_desc = ?, description = ?, specifications = ?, base_price = ?, sale_price = ?, cost_price = ?, has_variants = ?, is_featured = ?, is_active = ?, warranty_months = ?' . ($newThumbnail !== null ? ', thumbnail = ?' : '') . ' WHERE id = ?',
                array_merge([
                    $categoryId, $brandId, $name, $slug, $sku, $barcode,
                    $modelNumber, $shortDesc, $description, $specifications,
                    $basePrice, $salePrice, $costPrice, $hasVariants,
                    $isFeatured, $isActive, $warrantyMonths,
                ], $newThumbnail !== null ? [$newThumbnail] : [], [$id])
            );

            if ($newThumbnail !== null && !empty($product['thumbnail']) && file_exists(ROOT_PATH . $product['thumbnail'])) {
                @unlink(ROOT_PATH . $product['thumbnail']);
            }

            set_flash('success', 'Product updated successfully.');
            redirect(url('/admin/product-edit.php?id=' . $id));
        }
    } elseif ($subAction === 'add_variant') {
        $variantName = trim((string)($_POST['variant_name'] ?? ''));
        $vSku = strtoupper(trim((string)($_POST['sku'] ?? '')));
        $vBarcode = trim((string)($_POST['barcode'] ?? '')) ?: null;
        $addPrice = max(0, (float)($_POST['additional_price'] ?? 0));
        $initStock = max(0, (int)($_POST['initial_stock'] ?? 0));
        $attrRaw = trim((string)($_POST['attributes'] ?? '')) ?: null;

        if ($variantName === '' || $vSku === '') {
            $errors[] = 'Variant name and SKU are required.';
        } else {
            $vExists = db_fetch_value("SELECT id FROM product_variants WHERE sku = ?", [$vSku]);
            if ($vExists) {
                $errors[] = 'Variant SKU already exists.';
            } else {
                $vId = db_insert(
                    "INSERT INTO product_variants (product_id, variant_name, sku, barcode, additional_price, attributes, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)",
                    [$id, $variantName, $vSku, $vBarcode, $addPrice, $attrRaw]
                );
                db_insert(
                    "INSERT INTO inventory (product_id, variant_id, current_stock, min_stock_threshold) VALUES (?, ?, ?, 5)",
                    [$id, $vId, $initStock]
                );
                if ($initStock > 0) {
                    db_query(
                        "INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, cost_per_unit, reference_id, reference_type, notes, created_by) VALUES (?, ?, 'opening_stock', ?, ?, 'OPENING', 'system', 'Opening variant inventory', ?)",
                        [$id, $vId, $initStock, (float)$product['cost_price'], auth_id()]
                    );
                }
                set_flash('success', 'Variant added successfully.');
                redirect(url('/admin/product-edit.php?id=' . $id));
            }
        }
    } elseif ($subAction === 'update_variant') {
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $variantName = trim((string)($_POST['variant_name'] ?? ''));
        $vSku = strtoupper(trim((string)($_POST['sku'] ?? '')));
        $vBarcode = trim((string)($_POST['barcode'] ?? '')) ?: null;
        $addPrice = max(0, (float)($_POST['additional_price'] ?? 0));
        $attrRaw = trim((string)($_POST['attributes'] ?? '')) ?: null;
        $vActive = isset($_POST['is_active']) ? 1 : 0;

        db_query(
            "UPDATE product_variants SET variant_name = ?, sku = ?, barcode = ?, additional_price = ?, attributes = ?, is_active = ? WHERE id = ? AND product_id = ?",
            [$variantName, $vSku, $vBarcode, $addPrice, $attrRaw, $vActive, $variantId, $id]
        );
        set_flash('success', 'Variant updated.');
        redirect(url('/admin/product-edit.php?id=' . $id));
    } elseif ($subAction === 'deactivate_variant') {
        $variantId = (int)($_POST['variant_id'] ?? 0);
        db_query("UPDATE product_variants SET is_active = 0 WHERE id = ? AND product_id = ?", [$variantId, $id]);
        set_flash('success', 'Variant deactivated.');
        redirect(url('/admin/product-edit.php?id=' . $id));
    }

    // Refresh product info
    $product = db_fetch_one("SELECT * FROM products WHERE id = ?", [$id]);
}

$categories = db_fetch_all("SELECT id, name FROM categories WHERE is_active=1 ORDER BY name");
$brands     = db_fetch_all("SELECT id, name FROM brands WHERE is_active=1 ORDER BY name");
$variants   = db_fetch_all(
    "SELECT v.*, COALESCE(i.current_stock, 0) AS stock 
     FROM product_variants v 
     LEFT JOIN inventory i ON i.variant_id = v.id 
     WHERE v.product_id = ? 
     ORDER BY v.id",
    [$id]
);
$stock = (int)(db_fetch_value("SELECT current_stock FROM inventory WHERE product_id = ? AND variant_id IS NULL", [$id]) ?? 0);
?>

<div class="mx-auto max-w-5xl space-y-8">
    <div>
        <a href="<?= url('/admin/products.php') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i> Products List
        </a>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Edit <?= e($product['name']) ?></h1>
        <p class="mt-1 text-sm text-slate-500">Current base stock: <span class="font-bold text-slate-800"><?= (int)$stock ?></span> &bull; Manage live stock levels from <a href="<?= url('/admin/inventory.php') ?>" class="text-indigo-600 hover:underline">Inventory</a>.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800">
            <ul class="list-disc pl-4 space-y-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Product Edit Form -->
    <form method="POST" action="<?= url('/admin/product-edit.php?id=' . $id) ?>" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-6 shadow-xs space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="sub_action" value="update_product">

        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold text-slate-700">Product Name <span class="text-rose-500">*</span>
                <input name="name" required maxlength="200" value="<?= e($product['name']) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">SKU <span class="text-rose-500">*</span>
                <input name="sku" required maxlength="100" value="<?= e($product['sku']) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">Category <span class="text-rose-500">*</span>
                <select name="category_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Brand <span class="text-rose-500">*</span>
                <select name="brand_id" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)$brand['id'] ?>" <?= (int)$product['brand_id'] === (int)$brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Base Price (₹) <span class="text-rose-500">*</span>
                <input type="number" name="base_price" min="0.01" step="0.01" required value="<?= e((string)$product['base_price']) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">Sale Price (₹)
                <input type="number" name="sale_price" min="0.01" step="0.01" value="<?= e((string)($product['sale_price'] ?? '')) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">Cost Price (₹) <span class="text-rose-500">*</span>
                <input type="number" name="cost_price" min="0" step="0.01" required value="<?= e((string)$product['cost_price']) ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">Warranty (Months)
                <input type="number" name="warranty_months" min="0" max="120" value="<?= (int)$product['warranty_months'] ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">Barcode
                <input name="barcode" maxlength="100" value="<?= e($product['barcode'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700">Model Number
                <input name="model_number" maxlength="100" value="<?= e($product['model_number'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Short Description
                <input name="short_desc" maxlength="500" value="<?= e($product['short_desc'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Description
                <textarea name="description" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none"><?= e($product['description'] ?? '') ?></textarea>
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Specifications (JSON)
                <textarea name="specifications" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none"><?= e($product['specifications'] ?? '') ?></textarea>
            </label>

            <!-- Image preview & replace -->
            <div class="md:col-span-2 flex items-center gap-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <?php if ($product['thumbnail']): ?>
                    <img src="<?= asset($product['thumbnail']) ?>" alt="Thumbnail" class="h-20 w-20 rounded-lg border border-slate-200 object-cover shrink-0">
                <?php endif; ?>
                <label class="text-sm font-semibold text-slate-700 flex-1">
                    <span>Replace Thumbnail</span>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700 shadow-xs">
                </label>
            </div>

            <!-- Toggles -->
            <div class="md:col-span-2 flex flex-wrap items-center gap-6">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Featured Product
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="has_variants" value="1" <?= $product['has_variants'] ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Has Variants
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Active in Store
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">Save Changes</button>
            <a href="<?= url('/admin/products.php') ?>" class="text-sm font-medium text-slate-600 hover:underline">Cancel</a>
        </div>
    </form>

    <!-- Product Variants Section -->
    <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
        <div>
            <h2 class="text-base font-bold text-slate-900">Product Variants</h2>
            <p class="mt-1 text-xs text-slate-500">Manage individual SKU variations (RAM, Storage, Color). Adjust stock quantities in Inventory.</p>
        </div>

        <?php if (!empty($variants)): ?>
            <div class="space-y-3">
                <?php foreach ($variants as $variant): ?>
                <form method="POST" action="<?= url('/admin/product-edit.php?id=' . $id) ?>" class="grid gap-3 rounded-lg border border-slate-200 p-3 bg-slate-50/50 md:grid-cols-6 items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="sub_action" value="update_variant">
                    <input type="hidden" name="variant_id" value="<?= (int)$variant['id'] ?>">

                    <label class="text-xs font-semibold text-slate-700 md:col-span-2">Variant Name
                        <input name="variant_name" required maxlength="200" value="<?= e($variant['variant_name']) ?>" class="mt-1 block w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs bg-white">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">SKU
                        <input name="sku" required maxlength="100" value="<?= e($variant['sku']) ?>" class="mt-1 block w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs bg-white">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Extra Price (₹)
                        <input name="additional_price" type="number" min="0" step="0.01" value="<?= e((string)$variant['additional_price']) ?>" class="mt-1 block w-full rounded-md border border-slate-300 px-2 py-1.5 text-xs bg-white">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Stock
                        <input readonly value="<?= (int)$variant['stock'] ?>" class="mt-1 block w-full rounded-md border border-slate-200 bg-slate-100 px-2 py-1.5 text-xs text-slate-600 font-bold">
                    </label>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1.5 text-xs text-slate-700">
                            <input type="checkbox" name="is_active" value="1" <?= $variant['is_active'] ? 'checked' : '' ?> class="h-3.5 w-3.5 rounded">
                            Active
                        </label>
                        <button type="submit" class="rounded-md bg-slate-800 text-white px-2.5 py-1.5 text-xs font-semibold hover:bg-slate-900 transition">Save</button>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-400 py-3">No variants created for this product yet.</p>
        <?php endif; ?>

        <!-- Add Variant Sub-Form -->
        <div class="pt-4 border-t border-slate-100">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Add New Variant</h3>
            <form method="POST" action="<?= url('/admin/product-edit.php?id=' . $id) ?>" class="grid gap-3 md:grid-cols-3">
                <?= csrf_field() ?>
                <input type="hidden" name="sub_action" value="add_variant">

                <label class="text-xs font-semibold text-slate-700">Variant Name <span class="text-rose-500">*</span>
                    <input name="variant_name" required maxlength="200" placeholder="e.g. 256GB Natural Titanium" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-2 text-xs">
                </label>
                <label class="text-xs font-semibold text-slate-700">SKU <span class="text-rose-500">*</span>
                    <input name="sku" required maxlength="100" placeholder="e.g. IP15P-256-NAT" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-2 text-xs">
                </label>
                <label class="text-xs font-semibold text-slate-700">Barcode
                    <input name="barcode" maxlength="100" placeholder="Optional" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-2 text-xs">
                </label>
                <label class="text-xs font-semibold text-slate-700">Additional Price (₹)
                    <input name="additional_price" type="number" min="0" step="0.01" value="0" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-2 text-xs">
                </label>
                <label class="text-xs font-semibold text-slate-700">Opening Stock
                    <input name="initial_stock" type="number" min="0" value="0" class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-2 text-xs">
                </label>
                <label class="text-xs font-semibold text-slate-700">Attributes (JSON)
                    <input name="attributes" placeholder='{"storage":"256GB"}' class="mt-1 block w-full rounded-md border border-slate-300 px-2.5 py-2 text-xs font-mono">
                </label>
                <div class="md:col-span-3">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Variant
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

