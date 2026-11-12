<?php
$pageTitle = 'Analytics & Reports';
require_once __DIR__ . '/../includes/admin-header.php';

// ── Date Range Filter ──────────────────────────────────────────────────────────
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date']   ?? date('Y-m-d');

// Sanitise
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date)) $from_date = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date))   $to_date   = date('Y-m-d');

// ── CSV Export ─────────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = db_fetch_all(
        "SELECT DATE(created_at) AS sale_date, order_number, total_amount AS grand_total, order_status, payment_status
         FROM orders
         WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?
         ORDER BY created_at DESC",
        [$from_date, $to_date]
    );
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="orders-' . $from_date . '-to-' . $to_date . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Order Number', 'Grand Total', 'Order Status', 'Payment Status']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['sale_date'], $r['order_number'], $r['grand_total'], $r['order_status'], $r['payment_status']]);
    }
    fclose($out);
    exit;
}

// ── Key Metrics ────────────────────────────────────────────────────────────────
$revenue = (float)(db_fetch_value(
    "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?",
    [$from_date, $to_date]
) ?? 0);

$total_orders = (int)(db_fetch_value(
    "SELECT COUNT(*) FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?",
    [$from_date, $to_date]
) ?? 0);

$avg_order = $total_orders > 0 ? $revenue / $total_orders : 0;

$total_repairs = (int)(db_fetch_value(
    "SELECT COUNT(*) FROM repairs WHERE DATE(created_at) BETWEEN ? AND ?",
    [$from_date, $to_date]
) ?? 0);

// ── Sales Summary ──────────────────────────────────────────────────────────────
$sales_summary = db_fetch_all(
    "SELECT DATE(created_at) AS sale_date, COUNT(*) AS orders, SUM(total_amount) AS revenue
     FROM orders WHERE order_status != 'cancelled' AND DATE(created_at) BETWEEN ? AND ?
     GROUP BY DATE(created_at) ORDER BY sale_date DESC LIMIT 30",
    [$from_date, $to_date]
);

// ── Top 10 Products ────────────────────────────────────────────────────────────
$top_products = db_fetch_all(
    "SELECT oi.product_name, oi.sku, SUM(oi.quantity) AS qty_sold, SUM(oi.total_price) AS revenue
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE o.order_status != 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY oi.product_name, oi.sku ORDER BY revenue DESC LIMIT 10",
    [$from_date, $to_date]
);

// ── Order Status Breakdown ─────────────────────────────────────────────────────
$order_statuses = db_fetch_all(
    "SELECT order_status, COUNT(*) AS count, SUM(total_amount) AS total
     FROM orders WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY order_status",
    [$from_date, $to_date]
);

// ── Payment Method Breakdown ───────────────────────────────────────────────────
$payment_methods = db_fetch_all(
    "SELECT payment_method, COUNT(*) AS count, SUM(amount) AS total
     FROM payments WHERE status='captured' AND DATE(created_at) BETWEEN ? AND ?
     GROUP BY payment_method",
    [$from_date, $to_date]
);

// ── Repair Stats ───────────────────────────────────────────────────────────────
$repair_stats = db_fetch_all(
    "SELECT status, COUNT(*) AS count FROM repairs
     WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status",
    [$from_date, $to_date]
);

$statusColors = [
    'pending'       => 'bg-amber-50 text-amber-700 border-amber-200',
    'confirmed'     => 'bg-blue-50 text-blue-700 border-blue-200',
    'processing'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
    'shipped'       => 'bg-purple-50 text-purple-700 border-purple-200',
    'delivered'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'cancelled'     => 'bg-rose-50 text-rose-700 border-rose-200',
    'completed'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'refunded'      => 'bg-slate-100 text-slate-600 border-slate-300',
    'in_progress'   => 'bg-blue-50 text-blue-700 border-blue-200',
    'waiting_parts' => 'bg-orange-50 text-orange-700 border-orange-200',
    'ready'         => 'bg-teal-50 text-teal-700 border-teal-200',
];
?>

<div class="space-y-6">
  <!-- Page header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-slate-800">Analytics &amp; Reports</h1>
      <p class="text-sm text-slate-500 mt-0.5">Business performance overview for selected period</p>
    </div>
    <a href="reports.php?export=csv&from_date=<?= e($from_date) ?>&to_date=<?= e($to_date) ?>"
       class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-xs">
      <i data-lucide="download" class="w-4 h-4"></i> Export CSV
    </a>
  </div>

  <!-- Date range filter -->
  <form method="GET" class="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
    <div class="flex flex-wrap gap-4 items-end">
      <div class="flex flex-col gap-1">
        <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">From Date</label>
        <input type="date" name="from_date" value="<?= e($from_date) ?>"
               class="rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">To Date</label>
        <input type="date" name="to_date" value="<?= e($to_date) ?>"
               class="rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
      </div>
      <button type="submit"
              class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition">
        <i data-lucide="filter" class="w-4 h-4"></i> Apply Filter
      </button>
      <a href="reports.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
        <i data-lucide="x" class="w-4 h-4"></i> Reset
      </a>
    </div>
  </form>

  <!-- Key Metric Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
        <i data-lucide="indian-rupee" class="w-6 h-6 text-indigo-600"></i>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Revenue</p>
        <p class="text-2xl font-bold text-slate-800 mt-0.5"><?= format_currency($revenue) ?></p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
        <i data-lucide="shopping-bag" class="w-6 h-6 text-emerald-600"></i>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</p>
        <p class="text-2xl font-bold text-slate-800 mt-0.5"><?= number_format($total_orders) ?></p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
        <i data-lucide="trending-up" class="w-6 h-6 text-amber-600"></i>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Avg Order Value</p>
        <p class="text-2xl font-bold text-slate-800 mt-0.5"><?= format_currency($avg_order) ?></p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 px-5 py-4 flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
        <i data-lucide="wrench" class="w-6 h-6 text-purple-600"></i>
      </div>
      <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Repairs</p>
        <p class="text-2xl font-bold text-slate-800 mt-0.5"><?= number_format($total_repairs) ?></p>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    <!-- Sales Summary Table -->
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 flex items-center gap-2">
          <i data-lucide="bar-chart-2" class="w-4 h-4 text-indigo-500"></i> Daily Sales Summary
        </h2>
        <span class="text-xs text-slate-500">Last 30 days</span>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-slate-500 font-bold">Date</th>
              <th class="px-5 py-3 text-right text-xs uppercase tracking-wider text-slate-500 font-bold">Orders</th>
              <th class="px-5 py-3 text-right text-xs uppercase tracking-wider text-slate-500 font-bold">Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php if (empty($sales_summary)): ?>
            <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-slate-400">No sales data for this period.</td></tr>
            <?php else: ?>
            <?php foreach ($sales_summary as $row): ?>
            <tr class="hover:bg-slate-50 transition">
              <td class="px-5 py-3 text-sm text-slate-700 font-medium"><?= e(date('d M Y', strtotime($row['sale_date']))) ?></td>
              <td class="px-5 py-3 text-sm text-slate-600 text-right"><?= number_format($row['orders']) ?></td>
              <td class="px-5 py-3 text-sm font-semibold text-slate-800 text-right"><?= format_currency($row['revenue']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Right column stacked cards -->
    <div class="space-y-5">

      <!-- Order Status Breakdown -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
          <h2 class="font-semibold text-slate-800 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-500"></i> Order Status
          </h2>
        </div>
        <div class="divide-y divide-slate-100">
          <?php if (empty($order_statuses)): ?>
          <p class="px-5 py-6 text-sm text-slate-400 text-center">No data.</p>
          <?php else: ?>
          <?php foreach ($order_statuses as $row):
            $color = $statusColors[$row['order_status']] ?? 'bg-slate-100 text-slate-600 border-slate-200';
          ?>
          <div class="px-5 py-3 flex items-center justify-between">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border <?= $color ?>">
              <?= ucfirst(str_replace('_', ' ', e($row['order_status']))) ?>
            </span>
            <div class="text-right">
              <span class="text-sm font-semibold text-slate-700"><?= number_format($row['count']) ?></span>
              <span class="text-xs text-slate-400 ml-2"><?= format_currency($row['total'] ?? 0) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Repair Stats -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
          <h2 class="font-semibold text-slate-800 flex items-center gap-2">
            <i data-lucide="wrench" class="w-4 h-4 text-purple-500"></i> Repair Status
          </h2>
        </div>
        <div class="divide-y divide-slate-100">
          <?php if (empty($repair_stats)): ?>
          <p class="px-5 py-6 text-sm text-slate-400 text-center">No repairs in period.</p>
          <?php else: ?>
          <?php foreach ($repair_stats as $row):
            $color = $statusColors[$row['status']] ?? 'bg-slate-100 text-slate-600 border-slate-200';
          ?>
          <div class="px-5 py-3 flex items-center justify-between">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border <?= $color ?>">
              <?= ucfirst(str_replace('_', ' ', e($row['status']))) ?>
            </span>
            <span class="text-sm font-semibold text-slate-700"><?= number_format($row['count']) ?></span>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Payment Method Breakdown -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
          <h2 class="font-semibold text-slate-800 flex items-center gap-2">
            <i data-lucide="credit-card" class="w-4 h-4 text-emerald-500"></i> Payment Methods
          </h2>
        </div>
        <div class="divide-y divide-slate-100">
          <?php if (empty($payment_methods)): ?>
          <p class="px-5 py-6 text-sm text-slate-400 text-center">No captured payments in period.</p>
          <?php else: ?>
          <?php foreach ($payment_methods as $row):
            $pmIcon = match(strtolower($row['payment_method'] ?? '')) {
                'upi'           => 'smartphone',
                'card'          => 'credit-card',
                'bank_transfer' => 'landmark',
                default         => 'banknote',
            };
          ?>
          <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <i data-lucide="<?= $pmIcon ?>" class="w-4 h-4 text-slate-400"></i>
              <span class="text-sm text-slate-700 capitalize"><?= e(str_replace('_', ' ', $row['payment_method'])) ?></span>
            </div>
            <div class="text-right">
              <span class="text-sm font-semibold text-slate-700"><?= number_format($row['count']) ?></span>
              <span class="text-xs text-slate-400 ml-2"><?= format_currency($row['total'] ?? 0) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- Top 10 Products -->
  <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="font-semibold text-slate-800 flex items-center gap-2">
        <i data-lucide="package" class="w-4 h-4 text-indigo-500"></i> Top 10 Products by Revenue
      </h2>
      <span class="text-xs text-slate-500"><?= e($from_date) ?> &rarr; <?= e($to_date) ?></span>
    </div>
    <table class="min-w-full divide-y divide-slate-100">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-slate-500 font-bold">#</th>
          <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-slate-500 font-bold">Product</th>
          <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-slate-500 font-bold">SKU</th>
          <th class="px-5 py-3 text-right text-xs uppercase tracking-wider text-slate-500 font-bold">Qty Sold</th>
          <th class="px-5 py-3 text-right text-xs uppercase tracking-wider text-slate-500 font-bold">Revenue</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (empty($top_products)): ?>
        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">No product sales in this period.</td></tr>
        <?php else: ?>
        <?php foreach ($top_products as $i => $p): ?>
        <tr class="hover:bg-slate-50 transition">
          <td class="px-5 py-3 text-sm text-slate-400 font-medium"><?= $i + 1 ?></td>
          <td class="px-5 py-3 text-sm font-semibold text-slate-800"><?= e($p['product_name']) ?></td>
          <td class="px-5 py-3 text-sm text-slate-500 font-mono"><?= e($p['sku'] ?? '—') ?></td>
          <td class="px-5 py-3 text-sm text-slate-700 text-right"><?= number_format($p['qty_sold']) ?></td>
          <td class="px-5 py-3 text-sm font-bold text-indigo-700 text-right"><?= format_currency($p['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>lucide.createIcons();</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
