<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure admin authentication (placeholder, adjust as needed)
if (!is_admin()) {
    header('Location: /login.php');
    exit;
}

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $categoryId = intval($_POST['id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Basic validation
        if ($name === '') $errors[] = 'Category name is required.';
        if ($slug === '') $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));

        // Process image upload if present
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowed)) {
                $errors[] = 'Unsupported image type.';
            } else {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = uniqid('cat_', true) . '.' . $ext;
                $dest = __DIR__ . '/../uploads/categories/' . $imageName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $errors[] = 'Failed to move uploaded image.';
                }
            }
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, image, is_active) VALUES (:name, :slug, :image, :is_active)");
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':image' => $imageName,
                    ':is_active' => $isActive,
                ]);
                $success = 'Category created successfully.';
            } elseif ($action === 'edit' && $categoryId > 0) {
                // If a new image was uploaded, delete old one
                if ($imageName !== null) {
                    $old = $pdo->prepare("SELECT image FROM categories WHERE id = :id")->execute([':id' => $categoryId]);
                    $oldImg = $pdo->query("SELECT image FROM categories WHERE id = $categoryId")->fetchColumn();
                    if ($oldImg && file_exists(__DIR__ . '/../uploads/categories/' . $oldImg)) {
                        @unlink(__DIR__ . '/../uploads/categories/' . $oldImg);
                    }
                } else {
                    // Keep existing image
                    $imageName = $pdo->query("SELECT image FROM categories WHERE id = $categoryId")->fetchColumn();
                }
                $stmt = $pdo->prepare("UPDATE categories SET name = :name, slug = :slug, image = :image, is_active = :is_active WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':image' => $imageName,
                    ':is_active' => $isActive,
                    ':id' => $categoryId,
                ]);
                $success = 'Category updated successfully.';
            }
        }
    }
}

// Handle deletions via GET (simple approach – you may replace with POST + CSRF)
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    // Check for related products to avoid FK errors
    $cnt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_id = $delId")->fetchColumn();
    if ($cnt > 0) {
        $errors[] = 'Cannot delete category with associated products. Deactivate instead.';
    } else {
        // Delete image file
        $oldImg = $pdo->query("SELECT image FROM categories WHERE id = $delId")->fetchColumn();
        if ($oldImg && file_exists(__DIR__ . '/../uploads/categories/' . $oldImg)) {
            @unlink(__DIR__ . '/../uploads/categories/' . $oldImg);
        }
        $pdo->exec("DELETE FROM categories WHERE id = $delId");
        $success = 'Category deleted.';
    }
}

// Fetch all categories for list view
$categories = db_fetch_all("SELECT * FROM categories ORDER BY name ASC");

// Helper to repopulate form when editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editCategory = $pdo->prepare("SELECT * FROM categories WHERE id = :id")->execute([':id' => $editId]) ? $pdo->query("SELECT * FROM categories WHERE id = $editId")->fetch(PDO::FETCH_ASSOC) : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/meta.php'; ?>
    <title>Manage Categories – <?= e(setting('site_name')) ?></title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body class="bg-gray-100">
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Category Management</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <!-- Category Form -->
    <div class="bg-white rounded shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4"><?= $editCategory ? 'Edit' : 'Add' ?> Category</h2>
        <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editCategory ? 'edit' : 'create' ?>">
            <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>
            <div>
                <label class="block font-medium mb-1" for="name">Name</label>
                <input type="text" id="name" name="name" required class="w-full border rounded px-2 py-1" value="<?= e($editCategory['name'] ?? '') ?>">
            </div>
            <div>
                <label class="block font-medium mb-1" for="slug">Slug (optional)</label>
                <input type="text" id="slug" name="slug" class="w-full border rounded px-2 py-1" placeholder="auto-generated" value="<?= e($editCategory['slug'] ?? '') ?>">
            </div>
            <div>
                <label class="block font-medium mb-1" for="image">Image <?= $editCategory && $editCategory['image'] ? '(leave blank to keep existing)' : '' ?></label>
                <input type="file" id="image" name="image" accept="image/*" class="w-full">
                <?php if ($editCategory && $editCategory['image'] && file_exists(__DIR__ . '/../uploads/categories/' . $editCategory['image'])): ?>
                    <img src="<?= url('/uploads/categories/' . $editCategory['image']) ?>" alt="Current" class="mt-2 h-16 rounded">
                <?php endif; ?>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" <?= ($editCategory['is_active'] ?? 1) ? 'checked' : '' ?> class="mr-2">
                <label for="is_active" class="font-medium">Active</label>
            </div>
            <div class="col-span-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= $editCategory ? 'Update' : 'Create' ?> Category
                </button>
                <?php if ($editCategory): ?>
                    <a href="categories.php" class="ml-4 text-gray-600 hover:underline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category List -->
    <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Existing Categories</h2>
        <table class="w-full table-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Image</th>
                    <th class="px-4 py-2">Active</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2 text-center"><?= e($cat['id']) ?></td>
                        <td class="px-4 py-2"><?= e($cat['name']) ?></td>
                        <td class="px-4 py-2">
                            <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/../uploads/categories/' . $cat['image'])): ?>
                                <img src="<?= url('/uploads/categories/' . $cat['image']) ?>" alt="<?= e($cat['name']) ?>" class="h-12 rounded">
                            <?php else: ?>
                                <span class="text-gray-500">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 text-center"><?= $cat['is_active'] ? 'Yes' : 'No' ?></td>
                        <td class="px-4 py-2 text-center">
                            <a href="?edit=<?= $cat['id'] ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
                            <a href="?delete=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?');" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
