<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name         = trim($_POST['name'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $gstin        = strtoupper(trim($_POST['gstin'] ?? ''));
        $is_active    = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($name === '')         $errors[] = 'Contact person name is required.';
        if ($company_name === '') $errors[] = 'Company name is required.';
        if ($phone === '')        $errors[] = 'Phone number is required.';

        if (empty($errors)) {
            db_query(
                "INSERT INTO suppliers (name, company_name, email, phone, address, gstin, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$name, $company_name, $email ?: null, $phone, $address ?: null, $gstin ?: null, $is_active]
            );
            set_flash('success', 'Supplier created successfully.');
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect(url('/admin/suppliers.php'));
    }

    if ($action === 'update') {
        $id           = (int)($_POST['id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $address      = trim($_POST['address'] ?? '');
        $gstin        = strtoupper(trim($_POST['gstin'] ?? ''));
        $is_active    = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($id <= 0)             $errors[] = 'Invalid supplier ID.';
        if ($name === '')         $errors[] = 'Contact person name is required.';
        if ($company_name === '') $errors[] = 'Company name is required.';
        if ($phone === '')        $errors[] = 'Phone number is required.';

        if (empty($errors)) {
            db_query(
                "UPDATE suppliers SET name = ?, company_name = ?, email = ?, phone = ?, address = ?, gstin = ?, is_active = ? WHERE id = ?",
                [$name, $company_name, $email ?: null, $phone, $address ?: null, $gstin ?: null, $is_active, $id]
            );
            set_flash('success', 'Supplier updated successfully.');
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect(url('/admin/suppliers.php'));
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            db_query("UPDATE suppliers SET is_active = 1 - is_active WHERE id = ?", [$id]);
            set_flash('success', 'Supplier status updated.');
        }
        redirect(url('/admin/suppliers.php'));
    }
}

// ── Search & Filter ───────────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(name LIKE ? OR company_name LIKE ? OR phone LIKE ? OR email LIKE ? OR gstin LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($status === 'active') {
    $where[] = 'is_active = 1';
} elseif ($status === 'inactive') {
    $where[] = 'is_active = 0';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$suppliers = db_fetch_all(
    "SELECT s.*, 
            (SELECT COUNT(*) FROM purchases p WHERE p.supplier_id = s.id) AS total_orders,
            (SELECT COALESCE(SUM(total_amount), 0) FROM purchases p WHERE p.supplier_id = s.id) AS total_purchased
       FROM suppliers s
       $where_sql
   ORDER BY s.is_active DESC, s.company_name ASC",
    $params
);

$total_suppliers = (int)db_fetch_value("SELECT COUNT(*) FROM suppliers");
$active_suppliers = (int)db_fetch_value("SELECT COUNT(*) FROM suppliers WHERE is_active = 1");

$pageTitle = 'Suppliers & Vendors';
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-blue-600 mb-1">
                <i data-lucide="truck" class="w-4 h-4"></i>
                Procurement &amp; Inventory
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Suppliers Directory</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage hardware vendors, parts suppliers, and purchasing relationships.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button onclick="document.getElementById('addSupplierModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Add Supplier</span>
            </button>
            <a href="<?= url('/admin/purchases.php') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition">
                <i data-lucide="shopping-cart" class="w-4 h-4 text-slate-400"></i>
                <span>View Purchases</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="truck" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Suppliers</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($total_suppliers) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Suppliers</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($active_suppliers) ?></p>
            </div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <i data-lucide="package" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Inactive Suppliers</p>
                <p class="text-xl font-black text-slate-900 mt-0.5"><?= number_format($total_suppliers - $active_suppliers) ?></p>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
        <form method="GET" action="<?= url('/admin/suppliers.php') ?>" class="flex flex-col sm:flex-row gap-3 items-center">
            <div class="relative flex-1 w-full">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search company, contact person, phone, email, GSTIN..."
                       class="w-full pl-10 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
            </div>
            <select name="status" class="w-full sm:w-44 px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600 transition">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
            </select>
            <button type="submit" class="w-full sm:w-auto px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                Filter
            </button>
            <?php if ($search !== '' || $status !== ''): ?>
                <a href="<?= url('/admin/suppliers.php') ?>" class="w-full sm:w-auto px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold text-center transition">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Suppliers Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Company &amp; Contact</th>
                        <th class="px-5 py-3.5">Contact Details</th>
                        <th class="px-5 py-3.5">GSTIN / Tax ID</th>
                        <th class="px-5 py-3.5">Purchases</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                <i data-lucide="truck" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                <p class="text-sm font-semibold">No suppliers found.</p>
                                <p class="text-xs text-slate-400 mt-0.5">Try clearing filters or add a new supplier vendor.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $s): ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900 text-sm"><?= e($s['company_name']) ?></div>
                                    <div class="text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
                                        <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <?= e($s['name']) ?>
                                    </div>
                                    <?php if (!empty($s['address'])): ?>
                                        <div class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5" title="<?= e($s['address']) ?>">
                                            <?= e($s['address']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-1.5 font-semibold text-slate-800">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <?= e($s['phone']) ?>
                                    </div>
                                    <?php if (!empty($s['email'])): ?>
                                        <div class="flex items-center gap-1.5 text-slate-500 mt-0.5">
                                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <?= e($s['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php if (!empty($s['gstin'])): ?>
                                        <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            <?= e($s['gstin']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 italic">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-bold text-slate-900"><?= format_currency($s['total_purchased']) ?></div>
                                    <div class="text-[11px] text-slate-400"><?= (int)$s['total_orders'] ?> orders placed</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <?php if ($s['is_active']): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button onclick="editSupplier(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)" 
                                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition" title="Edit Supplier">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </button>
                                        <form method="POST" action="<?= url('/admin/suppliers.php') ?>" class="inline" onsubmit="return confirm('Change status for this supplier?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition" title="Toggle Active">
                                                <i data-lucide="<?= $s['is_active'] ? 'power-off' : 'check' ?>" class="w-3.5 h-3.5 text-<?= $s['is_active'] ? 'amber' : 'emerald' ?>-600"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Add New Supplier</h3>
                    <p class="text-xs text-slate-400">Register a hardware vendor or parts distributor</p>
                </div>
            </div>
            <button onclick="document.getElementById('addSupplierModal').classList.add('hidden')" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('/admin/suppliers.php') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Business Name *</label>
                    <input type="text" name="company_name" required placeholder="e.g. Apex Tech Wholesale"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Contact Person Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Rajesh Kumar"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number *</label>
                    <input type="tel" name="phone" required placeholder="e.g. +91 98765 43210"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" placeholder="vendor@domain.com"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">GSTIN / Tax ID</label>
                    <input type="text" name="gstin" placeholder="22AAAAA0000A1Z5"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 uppercase focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div class="flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700">Active Vendor</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Office / Warehouse Address</label>
                <textarea name="address" rows="2" placeholder="Full billing or warehouse address..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('addSupplierModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition">
                    Save Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div id="editSupplierModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i data-lucide="pencil" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Edit Supplier</h3>
                    <p class="text-xs text-slate-400">Update supplier contact &amp; tax details</p>
                </div>
            </div>
            <button onclick="document.getElementById('editSupplierModal').classList.add('hidden')" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('/admin/suppliers.php') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company / Business Name *</label>
                    <input type="text" name="company_name" id="edit_company_name" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Contact Person Name *</label>
                    <input type="text" name="name" id="edit_name" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number *</label>
                    <input type="tel" name="phone" id="edit_phone" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" id="edit_email"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">GSTIN / Tax ID</label>
                    <input type="text" name="gstin" id="edit_gstin"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 uppercase focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
                <div class="flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700">Active Vendor</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Office / Warehouse Address</label>
                <textarea name="address" id="edit_address" rows="2"
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('editSupplierModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition">
                    Update Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editSupplier(s) {
    document.getElementById('edit_id').value = s.id;
    document.getElementById('edit_company_name').value = s.company_name;
    document.getElementById('edit_name').value = s.name;
    document.getElementById('edit_phone').value = s.phone;
    document.getElementById('edit_email').value = s.email || '';
    document.getElementById('edit_gstin').value = s.gstin || '';
    document.getElementById('edit_address').value = s.address || '';
    document.getElementById('edit_is_active').checked = parseInt(s.is_active) === 1;
    document.getElementById('editSupplierModal').classList.remove('hidden');
    lucide.createIcons();
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

