<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Handle POST actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title            = trim($_POST['title'] ?? '');
        $category         = $_POST['category'] ?? 'other';
        $amount           = (float)($_POST['amount'] ?? 0);
        $expense_date     = trim($_POST['expense_date'] ?? '');
        $payment_method   = $_POST['payment_method'] ?? 'cash';
        $reference_number = trim($_POST['reference_number'] ?? '');
        $notes            = trim($_POST['notes'] ?? '');

        $errors = [];
        if ($title === '') $errors[] = 'Title is required.';
        if ($amount <= 0)  $errors[] = 'Amount must be greater than 0.';
        if ($expense_date === '' || !strtotime($expense_date)) $errors[] = 'A valid expense date is required.';

        if (empty($errors)) {
            db_query(
                "INSERT INTO expenses (title, category, amount, expense_date, payment_method, reference_number, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$title, $category, $amount, $expense_date, $payment_method,
                 $reference_number ?: null, $notes ?: null, auth_id()]
            );
            set_flash('success', 'Expense recorded successfully.');
        } else {
            set_flash('error', implode(' ', $errors));
        }
        redirect(url('admin/expenses.php'));
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db_query("DELETE FROM expenses WHERE id=?", [$id]);
        set_flash('success', 'Expense deleted.');
        redirect(url('admin/expenses.php'));
    }
}

// ── Filters & Pagination ──────────────────────────────────────────────────────
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;

$filter_category  = trim($_GET['category'] ?? '');
$filter_date_from = trim($_GET['date_from'] ?? '');
$filter_date_to   = trim($_GET['date_to'] ?? '');
$filter_search    = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($filter_category !== '') {
    $where[]  = 'category = ?';
    $params[] = $filter_category;
}
if ($filter_date_from !== '') {
    $where[]  = 'expense_date >= ?';
    $params[] = $filter_date_from;
}
if ($filter_date_to !== '') {
    $where[]  = 'expense_date <= ?';
    $params[] = $filter_date_to;
}
if ($filter_search !== '') {
    $where[]  = 'title LIKE ?';
    $params[] = '%' . $filter_search . '%';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total_rows  = (int)db_fetch_value("SELECT COUNT(*) FROM expenses $where_sql", $params);
$total_pages = max(1, (int)ceil($total_rows / $per_page));

$expenses = db_fetch_all(
    "SELECT e.*, u.full_name AS creator_name
       FROM expenses e
  LEFT JOIN users u ON u.id = e.created_by
     $where_sql
  ORDER BY e.expense_date DESC, e.created_at DESC
     LIMIT $per_page OFFSET $offset",
    $params
);

// ── Summary Stats ─────────────────────────────────────────────────────────────
$month_total = (float)db_fetch_value(
    "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=MONTH(NOW()) AND YEAR(expense_date)=YEAR(NOW())"
);
$year_total = (float)db_fetch_value(
    "SELECT COALESCE(SUM(amount),0) FROM expenses WHERE YEAR(expense_date)=YEAR(NOW())"
);
$all_time_total = (float)db_fetch_value("SELECT COALESCE(SUM(amount),0) FROM expenses");

// ── Category badge helper ─────────────────────────────────────────────────────
function expense_category_badge(string $cat): string {
    $map = [
        'operations'  => 'border-blue-200 bg-blue-50 text-blue-700',
        'marketing'   => 'border-purple-200 bg-purple-50 text-purple-700',
        'utilities'   => 'border-amber-200 bg-amber-50 text-amber-700',
        'maintenance' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'repairs'     => 'border-orange-200 bg-orange-50 text-orange-700',
        'salary'      => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        'other'       => 'border-slate-200 bg-slate-50 text-slate-700',
    ];
    $cls = $map[$cat] ?? $map['other'];
    return '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ' . $cls . '">' . htmlspecialchars(ucfirst($cat), ENT_QUOTES) . '</span>';
}

$categories = ['operations', 'marketing', 'utilities', 'maintenance', 'repairs', 'salary', 'other'];
$payment_methods = ['cash', 'bank_transfer', 'upi', 'card'];
?>

<div class="space-y-6">

  <!-- Page header -->
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-bold text-slate-900">Expenses</h1>
    <span class="text-sm text-slate-500"><?= $total_rows ?> total records</span>
  </div>

  <!-- ── SUMMARY STATS ──────────────────────────────────────────────────────── -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50">
          <i data-lucide="calendar" class="w-5 h-5 text-indigo-600"></i>
        </span>
        <div>
          <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">This Month</p>
          <p class="text-lg font-bold text-slate-900"><?= format_currency($month_total) ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50">
          <i data-lucide="trending-up" class="w-5 h-5 text-emerald-600"></i>
        </span>
        <div>
          <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">This Year</p>
          <p class="text-lg font-bold text-slate-900"><?= format_currency($year_total) ?></p>
        </div>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5">
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-amber-50">
          <i data-lucide="wallet" class="w-5 h-5 text-amber-600"></i>
        </span>
        <div>
          <p class="text-xs text-slate-500 font-semibold uppercase tracking-wide">All Time</p>
          <p class="text-lg font-bold text-slate-900"><?= format_currency($all_time_total) ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- ── CREATE FORM ────────────────────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
      <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-600"></i>
      <span class="font-bold text-slate-800 text-sm">Record New Expense</span>
    </div>
    <form method="POST" action="<?= url('admin/expenses.php') ?>" class="p-5">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        <!-- Title -->
        <div class="lg:col-span-2">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Title <span class="text-rose-500">*</span></label>
          <input type="text" name="title" required placeholder="e.g. Office rent payment"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Category -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Category <span class="text-rose-500">*</span></label>
          <select name="category" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Amount <span class="text-rose-500">*</span></label>
          <input type="number" name="amount" required min="0.01" step="0.01" placeholder="0.00"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Expense Date -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Expense Date <span class="text-rose-500">*</span></label>
          <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Payment Method</label>
          <select name="payment_method" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="upi">UPI</option>
            <option value="card">Card</option>
          </select>
        </div>

        <!-- Reference Number -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1">Reference # <span class="text-slate-400 font-normal">(optional)</span></label>
          <input type="text" name="reference_number" placeholder="Receipt / Transaction ID"
                 class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        </div>

        <!-- Notes -->
        <div class="lg:col-span-2">
          <label class="block text-xs font-semibold text-slate-600 mb-1">Notes <span class="text-slate-400 font-normal">(optional)</span></label>
          <textarea name="notes" rows="2" placeholder="Additional details…"
                    class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none resize-none"></textarea>
        </div>

      </div>

      <div class="mt-4 flex justify-end">
        <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
          <i data-lucide="plus" class="w-4 h-4"></i>
          Record Expense
        </button>
      </div>
    </form>
  </div>

  <!-- ── FILTERS ────────────────────────────────────────────────────────────── -->
  <form method="GET" action="<?= url('admin/expenses.php') ?>"
        class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Search Title</label>
      <input type="text" name="search" value="<?= e($filter_search) ?>" placeholder="Search…"
             class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Category</label>
      <select name="category" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat ?>" <?= $filter_category === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Date From</label>
      <input type="date" name="date_from" value="<?= e($filter_date_from) ?>"
             class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
    </div>
    <div>
      <label class="block text-xs font-semibold text-slate-600 mb-1">Date To</label>
      <input type="date" name="date_to" value="<?= e($filter_date_to) ?>"
             class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
    </div>
    <div class="flex gap-2">
      <button type="submit"
              class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
        <i data-lucide="search" class="w-4 h-4"></i> Filter
      </button>
      <a href="<?= url('admin/expenses.php') ?>"
         class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">
        <i data-lucide="x" class="w-4 h-4"></i> Clear
      </a>
    </div>
  </form>

  <!-- ── EXPENSES TABLE ─────────────────────────────────────────────────────── -->
  <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
          <th class="px-4 py-3 text-left">Title</th>
          <th class="px-4 py-3 text-left">Category</th>
          <th class="px-4 py-3 text-right">Amount</th>
          <th class="px-4 py-3 text-left">Date</th>
          <th class="px-4 py-3 text-left">Payment Method</th>
          <th class="px-4 py-3 text-left">Reference #</th>
          <th class="px-4 py-3 text-left">Created By</th>
          <th class="px-4 py-3 text-left">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($expenses)): ?>
          <tr>
            <td colspan="8" class="px-4 py-10 text-center text-slate-400 text-sm">No expenses found.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($expenses as $exp): ?>
            <tr class="hover:bg-slate-50 transition-colors">
              <td class="px-4 py-3 font-medium text-slate-800">
                <?= e($exp['title']) ?>
                <?php if ($exp['notes']): ?>
                  <p class="text-xs text-slate-400 mt-0.5 truncate max-w-xs"><?= e($exp['notes']) ?></p>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3"><?= expense_category_badge($exp['category']) ?></td>
              <td class="px-4 py-3 text-right font-semibold text-slate-900"><?= format_currency($exp['amount']) ?></td>
              <td class="px-4 py-3 text-slate-600"><?= e(date('d M Y', strtotime($exp['expense_date']))) ?></td>
              <td class="px-4 py-3 capitalize text-slate-600"><?= e(str_replace('_', ' ', $exp['payment_method'])) ?></td>
              <td class="px-4 py-3 text-slate-500 font-mono text-xs"><?= !empty($exp['reference_number']) ? e($exp['reference_number']) : '—' ?></td>
              <td class="px-4 py-3 text-slate-600"><?= e($exp['creator_name'] ?? '—') ?></td>
              <td class="px-4 py-3">
                <form method="POST" action="<?= url('admin/expenses.php') ?>"
                      onsubmit="return confirm('Delete this expense permanently?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$exp['id'] ?>">
                  <button type="submit"
                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition">
                    <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── PAGINATION ─────────────────────────────────────────────────────────── -->
  <?php if ($total_pages > 1): ?>
    <div class="flex items-center justify-between">
      <p class="text-sm text-slate-500">
        Page <?= $page ?> of <?= $total_pages ?> &nbsp;·&nbsp; <?= $total_rows ?> records
      </p>
      <div class="flex gap-1">
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
          <?php
            $q = http_build_query(array_merge($_GET, ['page' => $p]));
          ?>
          <a href="<?= url('admin/expenses.php') ?>?<?= $q ?>"
             class="px-3 py-1 rounded-lg text-sm font-semibold transition
                    <?= $p === $page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
