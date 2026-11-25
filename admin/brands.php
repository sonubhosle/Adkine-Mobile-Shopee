<?php
/**
 * MobileShopFlat — Admin Brands Management
 * Fully featured Brand Management with Logo/Photo Uploads, Real-time Previews, Edit Modal, and Deletion
 */

require_once __DIR__ . '/../includes/admin-header.php';

/**
 * Handle brand logo / photo upload.
 * Validates mime-types, file size, generates unique filename, and saves to /uploads/brands.
 */
function handle_brand_logo_upload(array $file, array &$errors): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Logo upload failed with error code ' . $file['error'];
        return null;
    }

    $allowed = [
        'image/jpeg'    => 'jpg',
        'image/jpg'     => 'jpg',
        'image/png'     => 'png',
        'image/webp'    => 'webp',
        'image/gif'     => 'gif',
        'image/svg+xml' => 'svg',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        $errors[] = 'Only JPG, PNG, WebP, SVG, and GIF files are allowed for brand logo.';
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Brand logo file size must not exceed 5 MB.';
        return null;
    }

    $ext = $allowed[$mime];
    $dir = ROOT_PATH . '/uploads/brands';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fileName = 'brand_' . bin2hex(random_bytes(6)) . '_' . time() . '.' . $ext;
    $target = $dir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        $errors[] = 'Failed to save the uploaded brand logo file.';
        return null;
    }

    return $fileName;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    $errors = [];

    if ($action === 'store') {
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            $errors[] = 'Brand name is required.';
        }

        if ($slug === '' && $name !== '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        }

        // Generate unique slug
        if ($slug !== '') {
            $baseSlug = $slug;
            $counter = 1;
            while (db_fetch_value("SELECT id FROM brands WHERE slug = ?", [$slug])) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        // Process logo upload
        $logoName = null;
        if (isset($_FILES['logo'])) {
            $logoName = handle_brand_logo_upload($_FILES['logo'], $errors);
        }

        if (!empty($errors)) {
            set_flash('error', implode(' ', $errors));
        } else {
            db_insert(
                "INSERT INTO brands (name, slug, logo, is_active) VALUES (?, ?, ?, ?)",
                [$name, $slug, $logoName, $isActive]
            );
            set_flash('success', "Brand '{$name}' added successfully.");
        }
        redirect(url('/admin/brands.php'));

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $removeLogo = isset($_POST['remove_logo']) && (int)$_POST['remove_logo'] === 1;

        $brand = db_fetch_one("SELECT * FROM brands WHERE id = ?", [$id]);
        if (!$brand) {
            set_flash('error', 'Brand not found.');
            redirect(url('/admin/brands.php'));
        }

        if ($name === '') {
            $errors[] = 'Brand name cannot be empty.';
        }
        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        }

        $exists = db_fetch_value("SELECT id FROM brands WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($exists) {
            $errors[] = "Slug '{$slug}' is already in use by another brand.";
        }

        $newLogoName = null;
        if (isset($_FILES['logo'])) {
            $newLogoName = handle_brand_logo_upload($_FILES['logo'], $errors);
        }

        if (!empty($errors)) {
            set_flash('error', implode(' ', $errors));
        } else {
            $finalLogo = $brand['logo'];

            if ($newLogoName !== null) {
                // Delete old logo if it exists on disk and differs
                if (!empty($brand['logo'])) {
                    $oldPath = ROOT_PATH . '/uploads/brands/' . basename($brand['logo']);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $finalLogo = $newLogoName;
            } elseif ($removeLogo) {
                if (!empty($brand['logo'])) {
                    $oldPath = ROOT_PATH . '/uploads/brands/' . basename($brand['logo']);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $finalLogo = null;
            }

            db_query(
                "UPDATE brands SET name = ?, slug = ?, logo = ?, is_active = ? WHERE id = ?",
                [$name, $slug, $finalLogo, $isActive, $id]
            );
            set_flash('success', "Brand '{$name}' updated successfully.");
        }
        redirect(url('/admin/brands.php'));

    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['is_active'] ?? 0);
        if ($id > 0) {
            db_query("UPDATE brands SET is_active = ? WHERE id = ?", [$status, $id]);
            set_flash('success', 'Brand status updated.');
        }
        redirect(url('/admin/brands.php'));

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $prodCount = (int)db_fetch_value("SELECT COUNT(*) FROM products WHERE brand_id = ?", [$id]);

            if ($prodCount > 0) {
                // Safe deactivation to preserve foreign key integrity
                db_query("UPDATE brands SET is_active = 0 WHERE id = ?", [$id]);
                set_flash('warning', "Cannot permanently delete brand: {$prodCount} assigned product(s) are linked to it. The brand has been deactivated instead.");
            } else {
                $brand = db_fetch_one("SELECT name, logo FROM brands WHERE id = ?", [$id]);
                if ($brand && !empty($brand['logo'])) {
                    $logoFile = ROOT_PATH . '/uploads/brands/' . basename($brand['logo']);
                    if (is_file($logoFile)) {
                        @unlink($logoFile);
                    }
                }
                db_query("DELETE FROM brands WHERE id = ?", [$id]);
                set_flash('success', "Brand '" . ($brand['name'] ?? 'Record') . "' permanently deleted.");
            }
        }
        redirect(url('/admin/brands.php'));
    }
}

// Fetch brands with search and pagination
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
    <!-- Page Header -->
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-700">Catalog setup</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Brands</h1>
            <p class="mt-1 text-sm text-slate-500">Manage brand identities, manufacturer logos, and catalog links.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-xs">
            <i data-lucide="tags" class="h-3.5 w-3.5 text-indigo-600"></i>
            <?= number_format((int)$total) ?> brands
        </span>
    </header>

    <!-- Add Brand Form Section -->
    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6 bg-slate-50/50">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 shadow-2xs">
                <i data-lucide="tag" class="h-4.5 w-4.5"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Add New Brand</h2>
                <p class="mt-0.5 text-xs text-slate-500">Create a brand identity with name, logo photo, and store availability</p>
            </div>
        </div>

        <form method="POST" action="<?= url('/admin/brands.php') ?>" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="store">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Brand Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="add_brand_name" required maxlength="150"
                           oninput="autoGenerateSlug(this.value, 'add_brand_slug')"
                           placeholder="e.g. Sony, Nothing, Asus"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        URL Slug <span class="text-[11px] font-normal text-slate-400">(optional)</span>
                    </label>
                    <input type="text" name="slug" id="add_brand_slug" maxlength="150"
                           placeholder="auto-generated-slug"
                           class="w-full rounded-xl border border-slate-300 font-mono px-3.5 py-2.5 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>

                <!-- Brand Photo / Logo Upload with Live Preview -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Brand Logo / Photo
                    </label>
                    <div class="flex items-center gap-3">
                        <div id="add_logo_preview_box" class="w-11 h-11 rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center shrink-0 overflow-hidden shadow-2xs p-1">
                            <i data-lucide="image" class="w-5 h-5 text-slate-400" id="add_logo_placeholder"></i>
                            <img id="add_logo_preview" src="" alt="Preview" class="w-full h-full object-contain hidden">
                        </div>
                        <label class="flex-1 cursor-pointer">
                            <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition shadow-2xs w-full justify-center">
                                <i data-lucide="upload" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span id="add_logo_label" class="truncate">Choose Photo / Logo</span>
                            </span>
                            <input type="file" name="logo" id="add_logo_input" accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif"
                                   onchange="previewBrandPhoto(this, 'add_logo_preview', 'add_logo_placeholder', 'add_logo_label')"
                                   class="sr-only">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Footer Bar of Add Form -->
            <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-100">
                <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                    <span>Active in Store Catalog</span>
                </label>

                <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95">
                    <i data-lucide="plus" class="h-4 w-4"></i> Add Brand
                </button>
            </div>
        </form>
    </section>

    <!-- Brand List Section -->
    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Brand Catalog List</h2>
                <p class="mt-0.5 text-xs text-slate-500">Manufacturer logos, product counts, and active catalog links</p>
            </div>
            <form method="GET" action="<?= url('/admin/brands.php') ?>" class="flex w-full gap-2 sm:w-auto">
                <div class="relative min-w-0 flex-1 sm:w-72">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="<?= e($search) ?>" placeholder="Search by name or slug..." 
                           class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                    Search
                </button>
                <?php if ($search !== ''): ?>
                    <a href="<?= url('/admin/brands.php') ?>" class="rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 transition flex items-center">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Brand &amp; Logo</th>
                        <th class="px-5 py-3.5">Slug</th>
                        <th class="px-5 py-3.5 text-right">Products</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($brands as $brand): ?>
                        <?php
                        $logoFilename = !empty($brand['logo']) ? basename($brand['logo']) : '';
                        $hasLogo = $logoFilename && file_exists(ROOT_PATH . '/uploads/brands/' . $logoFilename);
                        $logoUrl = $hasLogo ? url('/uploads/brands/' . $logoFilename) : '';
                        ?>
                        <tr class="transition hover:bg-slate-50/70">
                            <!-- Brand Logo & Name -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-white border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center p-1.5 shadow-2xs">
                                        <?php if ($hasLogo): ?>
                                            <img src="<?= e($logoUrl) ?>" alt="<?= e($brand['name']) ?>" class="w-full h-full object-contain">
                                        <?php else: ?>
                                            <div class="w-full h-full rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                                                <i data-lucide="tag" class="w-5 h-5"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 text-sm truncate"><?= e($brand['name']) ?></div>
                                        <div class="text-[11px] text-slate-400 font-mono"><?= e($brand['slug']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Slug Badge -->
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500">
                                <span class="bg-slate-50 border border-slate-200/70 px-2.5 py-1 rounded-md">
                                    <?= e($brand['slug']) ?>
                                </span>
                            </td>

                            <!-- Products Count -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                    <?= (int)$brand['product_count'] ?>
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold <?= $brand['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                                    <span class="h-1.5 w-1.5 rounded-full <?= $brand['is_active'] ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                    <?= $brand['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>

                            <!-- Actions: Edit Modal, Status Toggle, Delete -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit Button (Opens Modal with photo upload) -->
                                    <button type="button" 
                                            onclick='openEditBrandModal(<?= json_encode($brand, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($logoUrl) ?>)'
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-200 text-indigo-700 text-xs font-bold transition shadow-2xs"
                                            title="Edit Brand & Photo">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        <span>Edit</span>
                                    </button>

                                    <!-- Status Toggle Button -->
                                    <form method="POST" action="<?= url('/admin/brands.php') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= (int)$brand['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $brand['is_active'] ? '0' : '1' ?>">
                                        <?php if ($brand['is_active']): ?>
                                            <button type="submit" 
                                                    title="Deactivate Brand"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-200 transition shadow-2xs">
                                                <i data-lucide="ban" class="h-3.5 w-3.5 text-amber-500"></i>
                                                <span class="hidden sm:inline">Deactivate</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" 
                                                    title="Activate Brand"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 hover:border-emerald-200 transition shadow-2xs">
                                                <i data-lucide="check-circle" class="h-3.5 w-3.5 text-emerald-600"></i>
                                                <span class="hidden sm:inline">Activate</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Delete Button -->
                                    <form method="POST" action="<?= url('/admin/brands.php') ?>" 
                                          onsubmit="return confirm('Are you sure you want to delete brand \'<?= e($brand['name']) ?>\'? <?= (int)$brand['product_count'] > 0 ? 'It has ' . (int)$brand['product_count'] . ' products and will be safely deactivated.' : 'This action cannot be undone.' ?>')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$brand['id'] ?>">
                                        <button type="submit" 
                                                title="Delete Brand"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 hover:border-rose-200 transition shadow-2xs">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            <span class="hidden sm:inline">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$brands): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="tags" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-bold text-slate-700">No brands found.</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Try a different search query or add a new brand above.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Counts -->
        <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 bg-slate-50/40">
            <p>Showing <?= count($brands) ?> of <?= (int)$total ?> brands</p>
            <?php if ($pages > 1): ?>
                <div class="flex items-center gap-1">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <a href="<?= url('/admin/brands.php?page=' . $p . ($search ? '&search=' . urlencode($search) : '')) ?>" 
                           class="px-2.5 py-1 rounded-md text-xs font-bold <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php else: ?>
                <p>Page 1 of 1</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- ========================================================================= -->
<!-- EDIT BRAND MODAL                                                         -->
<!-- ========================================================================= -->
<div id="editBrandModal" 
     class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 hidden"
     onclick="if(event.target === this) closeEditBrandModal()">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl relative border border-slate-100">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-2xs">
                    <i data-lucide="pencil" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Edit Brand</h3>
                    <p class="text-xs text-slate-400">Update brand identity, URL slug, and photo/logo</p>
                </div>
            </div>
            <button type="button" onclick="closeEditBrandModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form method="POST" action="<?= url('/admin/brands.php') ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_brand_id">

            <!-- Brand Photo/Logo Management in Modal -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                <label class="block text-xs font-bold text-slate-700 mb-2">Brand Photo / Logo</label>
                <div class="flex items-center gap-4">
                    <!-- Photo Display / Preview -->
                    <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center p-1 shadow-xs">
                        <img id="edit_logo_preview" src="" alt="Brand Logo" class="w-full h-full object-contain rounded-xl hidden">
                        <i data-lucide="image" id="edit_logo_placeholder" class="w-6 h-6 text-slate-300"></i>
                    </div>

                    <div class="flex-1 space-y-2">
                        <label class="cursor-pointer inline-block">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition">
                                <i data-lucide="upload" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span id="edit_logo_label">Change Photo</span>
                            </span>
                            <input type="file" name="logo" id="edit_logo_input" accept="image/jpeg,image/png,image/webp,image/svg+xml,image/gif"
                                   onchange="previewBrandPhoto(this, 'edit_logo_preview', 'edit_logo_placeholder', 'edit_logo_label')"
                                   class="sr-only">
                        </label>
                        
                        <div id="edit_remove_logo_wrap" class="hidden">
                            <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer hover:underline">
                                <input type="checkbox" name="remove_logo" id="edit_remove_logo" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                <span>Remove current photo/logo</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Name and Slug Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Brand Name *</label>
                    <input type="text" name="name" id="edit_brand_name" required maxlength="150"
                           class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">URL Slug *</label>
                    <input type="text" name="slug" id="edit_brand_slug" required maxlength="150"
                           class="w-full px-3.5 py-2 text-xs sm:text-sm font-mono rounded-xl border border-slate-300 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
            </div>

            <!-- Active Checkbox -->
            <div class="pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" id="edit_brand_active" value="1" class="rounded h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-xs font-bold text-slate-700">Active in Store Catalog</span>
                </label>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditBrandModal()"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Brand JavaScript Helpers -->
<script>
/**
 * Auto-generate a clean URL slug from input text if target is blank or user is typing name
 */
function autoGenerateSlug(text, targetInputId) {
    const target = document.getElementById(targetInputId);
    if (!target) return;
    target.value = text
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-');
}

/**
 * Client-side Instant Preview of Brand Photo/Logo
 */
function previewBrandPhoto(input, imgElementId, placeholderId, labelId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById(imgElementId);
            const placeholder = document.getElementById(placeholderId);
            const label = document.getElementById(labelId);
            
            if (img) {
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
            if (label) {
                label.textContent = file.name;
            }
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Open and populate Edit Brand Modal
 */
function openEditBrandModal(brand, logoUrl) {
    document.getElementById('edit_brand_id').value = brand.id;
    document.getElementById('edit_brand_name').value = brand.name || '';
    document.getElementById('edit_brand_slug').value = brand.slug || '';
    document.getElementById('edit_brand_active').checked = parseInt(brand.is_active) === 1;

    // Reset file input and label
    const fileInput = document.getElementById('edit_logo_input');
    if (fileInput) fileInput.value = '';
    const fileLabel = document.getElementById('edit_logo_label');
    if (fileLabel) fileLabel.textContent = 'Change Photo';

    const removeCheckbox = document.getElementById('edit_remove_logo');
    if (removeCheckbox) removeCheckbox.checked = false;

    // Handle existing logo display
    const previewImg = document.getElementById('edit_logo_preview');
    const placeholder = document.getElementById('edit_logo_placeholder');
    const removeWrap = document.getElementById('edit_remove_logo_wrap');

    if (logoUrl && logoUrl.trim() !== '') {
        previewImg.src = logoUrl;
        previewImg.classList.remove('hidden');
        placeholder.classList.add('hidden');
        if (removeWrap) removeWrap.classList.remove('hidden');
    } else {
        previewImg.src = '';
        previewImg.classList.add('hidden');
        placeholder.classList.remove('hidden');
        if (removeWrap) removeWrap.classList.add('hidden');
    }

    document.getElementById('editBrandModal').classList.remove('hidden');
    if (window.lucide) {
        lucide.createIcons();
    }
}

/**
 * Close Edit Brand Modal
 */
function closeEditBrandModal() {
    document.getElementById('editBrandModal').classList.add('hidden');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        const modal = document.getElementById('editBrandModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeEditBrandModal();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
