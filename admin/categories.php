<?php
/**
 * MobileShopFlat — Admin Categories Management
 * Fully featured Category Management with Image Uploads, Real-time Previews, Edit Modal, and Deletion
 */

require_once __DIR__ . '/../includes/admin-header.php';

/**
 * Handle category photo upload.
 * Validates mime-types, file size, generates unique filename, and saves to /uploads/categories.
 */
function handle_category_image_upload(array $file, array &$errors): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Image upload failed with error code ' . $file['error'];
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        $errors[] = 'Only JPG, PNG, WebP, and GIF images are allowed for category photo.';
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'Category photo must not exceed 5 MB.';
        return null;
    }

    $ext = $allowed[$mime];
    $dir = ROOT_PATH . '/uploads/categories';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fileName = 'cat_' . bin2hex(random_bytes(6)) . '_' . time() . '.' . $ext;
    $target = $dir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        $errors[] = 'Failed to save the uploaded image file.';
        return null;
    }

    return $fileName;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $errors = [];

    if ($action === 'store') {
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? '')) ?: null;
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        if ($slug === '' && $name !== '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        }

        // Generate unique slug
        if ($slug !== '') {
            $baseSlug = $slug;
            $counter = 1;
            while (db_fetch_value("SELECT id FROM categories WHERE slug = ?", [$slug])) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        // Process image upload
        $imageName = null;
        if (isset($_FILES['image'])) {
            $imageName = handle_category_image_upload($_FILES['image'], $errors);
        }

        if (!empty($errors)) {
            set_flash('error', implode(' ', $errors));
        } else {
            db_insert(
                "INSERT INTO categories (name, slug, description, image, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                [$name, $slug, $desc, $imageName, $displayOrder, $isActive]
            );
            set_flash('success', "Category '{$name}' created successfully.");
        }
        redirect(url('/admin/categories.php'));

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? '')) ?: null;
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $removeImage = isset($_POST['remove_image']) && (int)$_POST['remove_image'] === 1;

        $category = db_fetch_one("SELECT * FROM categories WHERE id = ?", [$id]);
        if (!$category) {
            set_flash('error', 'Category not found.');
            redirect(url('/admin/categories.php'));
        }

        if ($name === '') {
            $errors[] = 'Category name cannot be empty.';
        }
        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        }

        $exists = db_fetch_value("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($exists) {
            $errors[] = "Slug '{$slug}' is already in use by another category.";
        }

        $newImageName = null;
        if (isset($_FILES['image'])) {
            $newImageName = handle_category_image_upload($_FILES['image'], $errors);
        }

        if (!empty($errors)) {
            set_flash('error', implode(' ', $errors));
        } else {
            $finalImage = $category['image'];

            if ($newImageName !== null) {
                // Delete old image if it exists and differs
                if (!empty($category['image'])) {
                    $oldPath = ROOT_PATH . '/uploads/categories/' . basename($category['image']);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $finalImage = $newImageName;
            } elseif ($removeImage) {
                if (!empty($category['image'])) {
                    $oldPath = ROOT_PATH . '/uploads/categories/' . basename($category['image']);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $finalImage = null;
            }

            db_query(
                "UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, display_order = ?, is_active = ? WHERE id = ?",
                [$name, $slug, $desc, $finalImage, $displayOrder, $isActive, $id]
            );
            set_flash('success', "Category '{$name}' updated successfully.");
        }
        redirect(url('/admin/categories.php'));

    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['is_active'] ?? 0);
        if ($id > 0) {
            db_query("UPDATE categories SET is_active = ? WHERE id = ?", [$status, $id]);
            set_flash('success', 'Category status updated.');
        }
        redirect(url('/admin/categories.php'));

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $prodCount = (int)db_fetch_value("SELECT COUNT(*) FROM products WHERE category_id = ?", [$id]);
            $childCount = (int)db_fetch_value("SELECT COUNT(*) FROM categories WHERE parent_id = ?", [$id]);

            if ($prodCount > 0 || $childCount > 0) {
                // Safe deactivation to preserve foreign key integrity
                db_query("UPDATE categories SET is_active = 0 WHERE id = ?", [$id]);
                $reasons = [];
                if ($prodCount > 0) $reasons[] = "{$prodCount} assigned product(s)";
                if ($childCount > 0) $reasons[] = "{$childCount} sub-category(ies)";
                set_flash('warning', "Cannot permanently delete category: it has " . implode(' and ', $reasons) . ". It has been deactivated instead.");
            } else {
                $cat = db_fetch_one("SELECT name, image FROM categories WHERE id = ?", [$id]);
                if ($cat && !empty($cat['image'])) {
                    $imgFile = ROOT_PATH . '/uploads/categories/' . basename($cat['image']);
                    if (is_file($imgFile)) {
                        @unlink($imgFile);
                    }
                }
                db_query("DELETE FROM categories WHERE id = ?", [$id]);
                set_flash('success', "Category '" . ($cat['name'] ?? 'Record') . "' permanently deleted.");
            }
        }
        redirect(url('/admin/categories.php'));
    }
}

// Fetch categories with search and pagination
$search = trim((string)($_GET['search'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE (c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$total = (int)(db_fetch_value("SELECT COUNT(*) FROM categories c $where", $params) ?? 0);
$pages = (int)ceil($total / $limit);

$categories = db_fetch_all(
    "SELECT c.*, COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     $where
     GROUP BY c.id
     ORDER BY c.display_order ASC, c.name ASC
     LIMIT $limit OFFSET $offset",
    $params
);
?>

<div class="mx-auto max-w-7xl space-y-6">
    <!-- Page Header (Matching Brands section) -->
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-indigo-700">Catalog setup</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Categories</h1>
            <p class="mt-1 text-sm text-slate-500">Create, organize, and manage product catalog categories and imagery.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-xs">
            <i data-lucide="layers-3" class="h-3.5 w-3.5 text-indigo-600"></i>
            <?= number_format((int)$total) ?> categories
        </span>
    </header>

    <!-- Add Category Form Section -->
    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 sm:px-6 bg-slate-50/50">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 shadow-2xs">
                <i data-lucide="folder-plus" class="h-4.5 w-4.5"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Add New Category</h2>
                <p class="mt-0.5 text-xs text-slate-500">Create a catalog grouping with name, photo, and sort order</p>
            </div>
        </div>

        <form method="POST" action="<?= url('/admin/categories.php') ?>" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="store">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Category Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="add_category_name" required maxlength="150"
                           oninput="autoGenerateSlug(this.value, 'add_category_slug')"
                           placeholder="e.g. Smartwatches"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>

                <!-- Slug -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        URL Slug <span class="text-[11px] font-normal text-slate-400">(optional)</span>
                    </label>
                    <input type="text" name="slug" id="add_category_slug" maxlength="150"
                           placeholder="auto-generated-slug"
                           class="w-full rounded-xl border border-slate-300 font-mono px-3.5 py-2.5 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>

                <!-- Category Image / Photo Upload with Live Preview -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Category Photo / Icon
                    </label>
                    <div class="flex items-center gap-3">
                        <div id="add_image_preview_box" class="w-11 h-11 rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center shrink-0 overflow-hidden shadow-2xs">
                            <i data-lucide="image" class="w-5 h-5 text-slate-400" id="add_image_placeholder"></i>
                            <img id="add_image_preview" src="" alt="Preview" class="w-full h-full object-cover hidden">
                        </div>
                        <label class="flex-1 cursor-pointer">
                            <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition shadow-2xs w-full justify-center">
                                <i data-lucide="upload" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span id="add_file_label" class="truncate">Choose Photo</span>
                            </span>
                            <input type="file" name="image" id="add_image_input" accept="image/jpeg,image/png,image/webp,image/gif"
                                   onchange="previewCategoryPhoto(this, 'add_image_preview', 'add_image_placeholder', 'add_file_label')"
                                   class="sr-only">
                        </label>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Description <span class="text-[11px] font-normal text-slate-400">(optional)</span>
                    </label>
                    <input type="text" name="description" placeholder="Brief summary"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
            </div>

            <!-- Footer Bar of Add Form -->
            <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-slate-100">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Display Order -->
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-slate-700">Display Order:</label>
                        <input type="number" name="display_order" value="0" min="0" max="999"
                               class="w-20 rounded-xl border border-slate-300 px-3 py-1.5 text-xs outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10 text-center font-bold">
                    </div>

                    <!-- Active Checkbox -->
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500">
                        <span>Active in Store</span>
                    </label>
                </div>

                <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95">
                    <i data-lucide="plus" class="h-4 w-4"></i> Add Category
                </button>
            </div>
        </form>
    </section>

    <!-- Category List Section (Matching Brands section layout & search) -->
    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xs">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Category Catalog List</h2>
                <p class="mt-0.5 text-xs text-slate-500">Manage categories, photo badges, and live catalog availability</p>
            </div>
            <form method="GET" action="<?= url('/admin/categories.php') ?>" class="flex w-full gap-2 sm:w-auto">
                <div class="relative min-w-0 flex-1 sm:w-72">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="<?= e($search) ?>" placeholder="Search by name, slug, description..." 
                           class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-xs sm:text-sm outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs sm:text-sm font-bold text-slate-700 hover:bg-slate-50 transition">
                    Search
                </button>
                <?php if ($search !== ''): ?>
                    <a href="<?= url('/admin/categories.php') ?>" class="rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-200 transition flex items-center">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Category &amp; Photo</th>
                        <th class="px-5 py-3.5">Slug</th>
                        <th class="px-5 py-3.5 text-center">Order</th>
                        <th class="px-5 py-3.5 text-right">Products</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $imageFilename = !empty($cat['image']) ? basename($cat['image']) : '';
                        $hasImage = $imageFilename && file_exists(ROOT_PATH . '/uploads/categories/' . $imageFilename);
                        $imageUrl = $hasImage ? url('/uploads/categories/' . $imageFilename) : '';
                        ?>
                        <tr class="transition hover:bg-slate-50/70">
                            <!-- Category Image & Name -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden shrink-0 flex items-center justify-center p-0.5 shadow-2xs">
                                        <?php if ($hasImage): ?>
                                            <img src="<?= e($imageUrl) ?>" alt="<?= e($cat['name']) ?>" class="w-full h-full object-cover rounded-lg">
                                        <?php else: ?>
                                            <i data-lucide="layers-3" class="w-5 h-5 text-slate-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 text-sm truncate"><?= e($cat['name']) ?></div>
                                        <?php if (!empty($cat['description'])): ?>
                                            <div class="text-[11px] text-slate-400 truncate max-w-xs"><?= e($cat['description']) ?></div>
                                        <?php else: ?>
                                            <div class="text-[10px] text-slate-400 italic">No description</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Slug -->
                            <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500">
                                <span class="bg-slate-50 border border-slate-200/70 px-2 py-0.5 rounded-md">
                                    <?= e($cat['slug']) ?>
                                </span>
                            </td>

                            <!-- Order -->
                            <td class="whitespace-nowrap px-5 py-3.5 text-center text-xs font-bold text-slate-600">
                                <?= (int)$cat['display_order'] ?>
                            </td>

                            <!-- Products Count -->
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                    <?= (int)$cat['product_count'] ?>
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold <?= $cat['is_active'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                                    <span class="h-1.5 w-1.5 rounded-full <?= $cat['is_active'] ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                                    <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>

                            <!-- Manage Actions: Edit Modal, Status Toggle, Delete -->
                            <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Edit Button (Opens Modal with image management) -->
                                    <button type="button" 
                                            onclick='openEditCategoryModal(<?= json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($imageUrl) ?>)'
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-indigo-50 hover:border-indigo-200 text-indigo-700 text-xs font-bold transition shadow-2xs"
                                            title="Edit Category & Photo">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        <span>Edit</span>
                                    </button>

                                    <!-- Status Toggle Button -->
                                    <form method="POST" action="<?= url('/admin/categories.php') ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $cat['is_active'] ? '0' : '1' ?>">
                                        <?php if ($cat['is_active']): ?>
                                            <button type="submit" 
                                                    title="Deactivate Category"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-200 transition shadow-2xs">
                                                <i data-lucide="ban" class="h-3.5 w-3.5 text-amber-500"></i>
                                                <span class="hidden sm:inline">Deactivate</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" 
                                                    title="Activate Category"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 hover:border-emerald-200 transition shadow-2xs">
                                                <i data-lucide="check-circle" class="h-3.5 w-3.5 text-emerald-600"></i>
                                                <span class="hidden sm:inline">Activate</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Delete Button -->
                                    <form method="POST" action="<?= url('/admin/categories.php') ?>" 
                                          onsubmit="return confirm('Are you sure you want to delete category \'<?= e($cat['name']) ?>\'? <?= (int)$cat['product_count'] > 0 ? 'It has ' . (int)$cat['product_count'] . ' products and will be safely deactivated.' : 'This action cannot be undone.' ?>')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                        <button type="submit" 
                                                title="Delete Category"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 hover:border-rose-200 transition shadow-2xs">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            <span class="hidden sm:inline">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$categories): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="layers" class="w-10 h-10 text-slate-300 mb-2"></i>
                                    <p class="font-bold text-slate-700">No categories found.</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Try a different search query or add a new category above.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Counts -->
        <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 bg-slate-50/40">
            <p>Showing <?= count($categories) ?> of <?= (int)$total ?> categories</p>
            <?php if ($pages > 1): ?>
                <div class="flex items-center gap-1">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <a href="<?= url('/admin/categories.php?page=' . $p . ($search ? '&search=' . urlencode($search) : '')) ?>" 
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
<!-- EDIT CATEGORY MODAL                                                      -->
<!-- ========================================================================= -->
<div id="editCategoryModal" 
     class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4 hidden"
     onclick="if(event.target === this) closeEditCategoryModal()">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl relative border border-slate-100">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-2xs">
                    <i data-lucide="pencil" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Edit Category</h3>
                    <p class="text-xs text-slate-400">Update category details, URL slug, and photo</p>
                </div>
            </div>
            <button type="button" onclick="closeEditCategoryModal()" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form method="POST" action="<?= url('/admin/categories.php') ?>" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_cat_id">

            <!-- Category Photo Management in Modal -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                <label class="block text-xs font-bold text-slate-700 mb-2">Category Photo</label>
                <div class="flex items-center gap-4">
                    <!-- Photo Display / Preview -->
                    <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center p-0.5 shadow-xs">
                        <img id="edit_image_preview" src="" alt="Category Photo" class="w-full h-full object-cover rounded-xl hidden">
                        <i data-lucide="image" id="edit_image_placeholder" class="w-6 h-6 text-slate-300"></i>
                    </div>

                    <div class="flex-1 space-y-2">
                        <label class="cursor-pointer inline-block">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs shadow-2xs transition">
                                <i data-lucide="upload" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span id="edit_file_label">Change Photo</span>
                            </span>
                            <input type="file" name="image" id="edit_image_input" accept="image/jpeg,image/png,image/webp,image/gif"
                                   onchange="previewCategoryPhoto(this, 'edit_image_preview', 'edit_image_placeholder', 'edit_file_label')"
                                   class="sr-only">
                        </label>
                        
                        <div id="edit_remove_photo_wrap" class="hidden">
                            <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 cursor-pointer hover:underline">
                                <input type="checkbox" name="remove_image" id="edit_remove_image" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                <span>Remove current photo</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Name and Slug Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Category Name *</label>
                    <input type="text" name="name" id="edit_cat_name" required maxlength="150"
                           class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">URL Slug *</label>
                    <input type="text" name="slug" id="edit_cat_slug" required maxlength="150"
                           class="w-full px-3.5 py-2 text-xs sm:text-sm font-mono rounded-xl border border-slate-300 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
                <textarea name="description" id="edit_cat_description" rows="2" placeholder="Brief summary of category contents..."
                          class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10"></textarea>
            </div>

            <!-- Display Order & Active Checkbox -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Display Order</label>
                    <input type="number" name="display_order" id="edit_cat_order" min="0" max="999"
                           class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500/10">
                </div>
                <div class="flex items-center pt-5">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_cat_active" value="1" class="rounded h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-700">Active in Store Catalog</span>
                    </label>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditCategoryModal()"
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

<!-- Category JavaScript Helpers -->
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
 * Client-side Instant Preview of Category Image
 */
function previewCategoryPhoto(input, imgElementId, placeholderId, labelId) {
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
 * Open and populate Edit Category Modal
 */
function openEditCategoryModal(category, imageUrl) {
    document.getElementById('edit_cat_id').value = category.id;
    document.getElementById('edit_cat_name').value = category.name || '';
    document.getElementById('edit_cat_slug').value = category.slug || '';
    document.getElementById('edit_cat_description').value = category.description || '';
    document.getElementById('edit_cat_order').value = category.display_order || 0;
    document.getElementById('edit_cat_active').checked = parseInt(category.is_active) === 1;

    // Reset file input and label
    const fileInput = document.getElementById('edit_image_input');
    if (fileInput) fileInput.value = '';
    const fileLabel = document.getElementById('edit_file_label');
    if (fileLabel) fileLabel.textContent = 'Change Photo';

    const removeCheckbox = document.getElementById('edit_remove_image');
    if (removeCheckbox) removeCheckbox.checked = false;

    // Handle existing image display
    const previewImg = document.getElementById('edit_image_preview');
    const placeholder = document.getElementById('edit_image_placeholder');
    const removeWrap = document.getElementById('edit_remove_photo_wrap');

    if (imageUrl && imageUrl.trim() !== '') {
        previewImg.src = imageUrl;
        previewImg.classList.remove('hidden');
        placeholder.classList.add('hidden');
        if (removeWrap) removeWrap.classList.remove('hidden');
    } else {
        previewImg.src = '';
        previewImg.classList.add('hidden');
        placeholder.classList.remove('hidden');
        if (removeWrap) removeWrap.classList.add('hidden');
    }

    document.getElementById('editCategoryModal').classList.remove('hidden');
    if (window.lucide) {
        lucide.createIcons();
    }
}

/**
 * Close Edit Category Modal
 */
function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').classList.add('hidden');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
        const modal = document.getElementById('editCategoryModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeEditCategoryModal();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
