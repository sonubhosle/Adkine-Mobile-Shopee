<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $code           = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type  = $_POST['discount_type'] ?? 'percentage';
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $min_order      = (float)($_POST['min_order_amount'] ?? 0);
        $max_discount   = trim($_POST['max_discount_amount'] ?? '') !== '' ? (float)$_POST['max_discount_amount'] : null;
        $usage_limit    = trim($_POST['usage_limit'] ?? '') !== '' ? (int)$_POST['usage_limit'] : null;
        $valid_from     = trim($_POST['valid_from'] ?? '') !== '' ? $_POST['valid_from'] : null;
        $valid_until    = trim($_POST['valid_until'] ?? '') !== '' ? $_POST['valid_until'] : null;
        $is_active      = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($code === '') $errors[] = 'Code is required.';
        if ($discount_value <= 0) $errors[] = 'Discount value must be greater than 0.';
        if ($valid_from && $valid_until && strtotime($valid_from) >= strtotime($valid_until)) {
            $errors[] = 'Valid From must be before Valid Until.';
        }

        if (empty($errors)) {
            // Format datetimes for MySQL
            $valid_from_db  = $valid_from  ? date('Y-m-d H:i:s', strtotime($valid_from))  : null;
            $valid_until_db = $valid_until ? date('Y-m-d H:i:s', strtotime($valid_until)) : null;

            db_query(
                "INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, usage_count, valid_from, valid_until, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW())",
                [$code, $discount_type, $discount_value, $min_order, $max_discount, $usage_limit, $valid_from_db, $valid_until_db, $is_active]
            );
            set_flash('success', 'Coupon created successfully.');
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect(url('/admin/coupons.php'));
    }

    if ($action === 'update') {
        $id             = (int)($_POST['id'] ?? 0);
        $code           = strtoupper(trim($_POST['code'] ?? ''));
        $discount_type  = $_POST['discount_type'] ?? 'percentage';
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $min_order      = (float)($_POST['min_order_amount'] ?? 0);
        $max_discount   = trim($_POST['max_discount_amount'] ?? '') !== '' ? (float)$_POST['max_discount_amount'] : null;
        $usage_limit    = trim($_POST['usage_limit'] ?? '') !== '' ? (int)$_POST['usage_limit'] : null;
        $valid_from     = trim($_POST['valid_from'] ?? '') !== '' ? $_POST['valid_from'] : null;
        $valid_until    = trim($_POST['valid_until'] ?? '') !== '' ? $_POST['valid_until'] : null;
        $is_active      = isset($_POST['is_active']) ? 1 : 0;

        $errors = [];
        if ($code === '') $errors[] = 'Code is required.';
        if ($discount_value <= 0) $errors[] = 'Discount value must be greater than 0.';
        if ($valid_from && $valid_until && strtotime($valid_from) >= strtotime($valid_until)) {
            $errors[] = 'Valid From must be before Valid Until.';
        }

        if (empty($errors)) {
            $valid_from_db  = $valid_from  ? date('Y-m-d H:i:s', strtotime($valid_from))  : null;
            $valid_until_db = $valid_until ? date('Y-m-d H:i:s', strtotime($valid_until)) : null;

            db_query(
                "UPDATE coupons SET code=?, discount_type=?, discount_value=?, min_order_amount=?, max_discount_amount=?, usage_limit=?, valid_from=?, valid_until=?, is_active=? WHERE id=?",
                [$code, $discount_type, $discount_value, $min_order, $max_discount, $usage_limit, $valid_from_db, $valid_until_db, $is_active, $id]
            );
            set_flash('success', 'Coupon updated successfully.');
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect(url('/admin/coupons.php'));
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_query("UPDATE coupons SET is_active = NOT is_active WHERE id=?", [$id]);
        set_flash('success', 'Coupon status toggled.');
        redirect(url('/admin/coupons.php'));
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db_query("UPDATE coupons SET is_active = 0 WHERE id=?", [$id]);
        set_flash('success', 'Coupon deactivated.');
        redirect(url('/admin/coupons.php'));
    }
}

// ── Load edit coupon ──────────────────────────────────────────────────────────
$editing  = null;
$edit_id  = (int)($_GET['edit'] ?? 0);
if ($edit_id > 0) {
    $editing = db_fetch_one("SELECT * FROM coupons WHERE id=?", [$edit_id]);
}

// ── Load all coupons ──────────────────────────────────────────────────────────
$coupons = db_fetch_all("SELECT * FROM coupons ORDER BY created_at DESC");

// Helper to format datetime-local value
function fmt_dtl(?string $dt): string {
    if (!$dt) return '';
    return date('Y-m-d\TH:i', strtotime($dt));
}
?>

<div class="space-y-6">

  <!-- Page header -->
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-bold text-slate-900">Coupons</h1>
    <span class="text-sm text-slate-500"><?= count($coupons) ?> total</span>
  </div>

  <!-- ── CREATE / EDIT FORM ─────────────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
      <i data-lucide="<?= $editing ? 'pencil' : 'plus-circle' ?>" class="w-4 h-4 text-indigo-600"></i>
      <span class="font-bold text-slate-800 text-sm"><?= $editing ? 'Edit Coupon' : 'Create New Coupon' ?></span>
      <?php if ($editing): ?>
        <a href="<?= url('admin/coupons.php') ?>" class="ml-auto text-xs text-slate-500 hover:text-slate-700">Cancel edit</a>
      <?php endif; ?>
    </div>
    <form method="POST" action="<?= url('admin/coupons.php') ?>" class="p-5">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        <!-- Code -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Coupon Code <span class="text-rose-500">*</span></label>
          <input type="text" name="code" required
                 value="<?= e($editing['code'] ?? '') ?>"
                 placeholder="SAVE20"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none uppercase">
        </div>

        <!-- Discount Type -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Type <span class="text-rose-500">*</span></label>
          <select name="discount_type"
                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <option value="percentage" <?= ($editing['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
            <option value="fixed"      <?= ($editing['discount_type'] ?? '') === 'fixed'      ? 'selected' : '' ?>>Fixed Amount</option>
          </select>
        </div>

        <!-- Discount Value -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Value <span class="text-rose-500">*</span></label>
          <input type="number" name="discount_value" required min="0.01" step="0.01"
                 value="<?= e($editing['discount_value'] ?? '') ?>"
                 placeholder="10.00"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Min Order Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Min Order Amount</label>
          <input type="number" name="min_order_amount" min="0" step="0.01"
                 value="<?= e($editing['min_order_amount'] ?? '0') ?>"
                 placeholder="0.00"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Max Discount Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Max Discount Amount <span class="text-slate-400 font-normal">(optional)</span></label>
          <input type="number" name="max_discount_amount" min="0" step="0.01"
                 value="<?= e($editing['max_discount_amount'] ?? '') ?>"
                 placeholder="—"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Usage Limit -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Usage Limit <span class="text-slate-400 font-normal">(optional)</span></label>
          <input type="number" name="usage_limit" min="1" step="1"
                 value="<?= e($editing['usage_limit'] ?? '') ?>"
                 placeholder="Unlimited"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Valid From -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Valid From</label>
          <input type="datetime-local" name="valid_from"
                 value="<?= e(fmt_dtl($editing['valid_from'] ?? null)) ?>"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Valid Until -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Valid Until</label>
          <input type="datetime-local" name="valid_until"
                 value="<?= e(fmt_dtl($editing['valid_until'] ?? null)) ?>"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Active -->
        <div class="flex items-end pb-1">
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" name="is_active" value="1"
                   <?= ($editing ? (int)$editing['is_active'] : 1) ? 'checked' : '' ?>
                   class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
            <span class="text-sm font-semibold text-slate-700">Active</span>
          </label>
        </div>

      </div>

      <div class="mt-4 flex justify-end">
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
          <i data-lucide="<?= $editing ? 'save' : 'plus' ?>" class="w-4 h-4"></i>
          <?= $editing ? 'Update Coupon' : 'Create Coupon' ?>
        </button>
      </div>
    </form>
  </div>

  <!-- ── COUPONS LIST ───────────────────────────────────────────────────────── -->
  <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
          <th class="px-4 py-3 text-left">Code</th>
          <th class="px-4 py-3 text-left">Type</th>
          <th class="px-4 py-3 text-right">Value</th>
          <th class="px-4 py-3 text-right">Min Order</th>
          <th class="px-4 py-3 text-right">Max Disc.</th>
          <th class="px-4 py-3 text-right">Limit</th>
          <th class="px-4 py-3 text-right">Used</th>
          <th class="px-4 py-3 text-left">Valid From</th>
          <th class="px-4 py-3 text-left">Valid Until</th>
          <th class="px-4 py-3 text-left">Status</th>
          <th class="px-4 py-3 text-left">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($coupons)): ?>
          <tr>
            <td colspan="11" class="px-4 py-10 text-center text-slate-400 text-sm">No coupons found. Create one above.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($coupons as $c): ?>
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="px-4 py-3 font-mono font-semibold text-indigo-700 tracking-wide"><?= e($c['code']) ?></td>
              <td class="px-4 py-3 capitalize"><?= e($c['discount_type']) ?></td>
              <td class="px-4 py-3 text-right font-semibold">
                <?php if ($c['discount_type'] === 'percentage'): ?>
                  <?= e($c['discount_value']) ?>%
                <?php else: ?>
                  <?= format_currency($c['discount_value']) ?>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-right"><?= format_currency($c['min_order_amount']) ?></td>
              <td class="px-4 py-3 text-right"><?= $c['max_discount_amount'] !== null ? format_currency($c['max_discount_amount']) : '—' ?></td>
              <td class="px-4 py-3 text-right"><?= $c['usage_limit'] !== null ? (int)$c['usage_limit'] : '∞' ?></td>
              <td class="px-4 py-3 text-right"><?= (int)($c['usage_count'] ?? $c['used_count'] ?? 0) ?></td>
              <td class="px-4 py-3 text-xs text-slate-600"><?= $c['valid_from'] ? e(date('d M Y H:i', strtotime($c['valid_from']))) : '—' ?></td>
              <td class="px-4 py-3 text-xs text-slate-600"><?= $c['valid_until'] ? e(date('d M Y H:i', strtotime($c['valid_until']))) : '—' ?></td>
              <td class="px-4 py-3">
                <?php if ($c['is_active']): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border border-emerald-200 bg-emerald-50 text-emerald-700">Active</span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border border-rose-200 bg-rose-50 text-rose-700">Inactive</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <!-- Edit -->
                  <a href="<?= url('admin/coupons.php') ?>?edit=<?= (int)$c['id'] ?>"
                     class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    <i data-lucide="pencil" class="w-3 h-3"></i> Edit
                  </a>
                  <!-- Toggle -->
                  <form method="POST" action="<?= url('admin/coupons.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold transition">
                      <i data-lucide="toggle-left" class="w-3 h-3"></i> Toggle
                    </button>
                  </form>
                  <!-- Delete (soft) -->
                  <form method="POST" action="<?= url('admin/coupons.php') ?>"
                        onsubmit="return confirm('Deactivate this coupon?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition">
                      <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
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

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
