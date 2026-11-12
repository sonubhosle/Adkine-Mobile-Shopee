<?php
/**
 * MobileShopFlat — Admin Dashboard
 */

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/report-functions.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

$metrics     = report_get_dashboard_metrics();
$dailyTrend  = report_get_daily_sales_trend(30);
$catBreakdown= report_get_category_sales_breakdown();
$topProducts = report_get_top_products(5);
$lowStock    = inventory_get_low_stock_alerts();

// Total products count
$totalProducts = (int)(db_fetch_value("SELECT COUNT(*) FROM products") ?? 0);
$metrics['total_products'] = $totalProducts;

// Recent orders
$recentOrders = db_fetch_all(
    "SELECT o.id, o.order_number, u.full_name AS customer_name, o.total_amount, o.order_status AS status, o.created_at
     FROM orders o JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC LIMIT 8"
);

// Recent repairs
$recentRepairs = db_fetch_all(
    "SELECT r.id, r.repair_ticket_number AS ticket_number, r.device_brand, r.device_model, r.status, r.created_at,
            r.customer_name
     FROM repairs r
     ORDER BY r.created_at DESC LIMIT 5"
);

$recentCustomers = db_fetch_all(
    "SELECT u.id, u.full_name, u.email, u.created_at
     FROM users u
     JOIN roles r ON r.id = u.role_id
     WHERE r.slug = 'customer'
     ORDER BY u.created_at DESC
     LIMIT 6"
);

$recentActivity = db_fetch_all(
    "SELECT al.action, al.module, al.created_at, u.full_name AS user_name
     FROM audit_logs al
     LEFT JOIN users u ON u.id = al.user_id
     ORDER BY al.created_at DESC
     LIMIT 8"
);

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
$dailyTrendJson = json_encode($dailyTrend ?? [], $jsonFlags);
$catBreakdownJson = json_encode($catBreakdown ?? [], $jsonFlags);
?>

<div class="admin-dashboard">
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-blue-700">Business overview</p>
        <h1 class="text-2xl font-extrabold text-slate-950">Dashboard</h1>
        <p class="text-sm text-slate-500 mt-0.5">Welcome back, <?= e(auth()['full_name'] ?? 'Admin') ?> — here's what's happening today.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= url('/admin/reports.php') ?>" class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
            <i data-lucide="bar-chart-3" class="w-4 h-4 text-purple-500"></i> View Reports
        </a>
        <a href="<?= url('/admin/pos.php') ?>" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
            <i data-lucide="calculator" class="w-4 h-4"></i> Open POS
        </a>
    </div>
</div>

<!-- Top Stats Row -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <!-- Today's Revenue -->
    <div class="bg-slate-950 rounded-xl shadow-sm border border-slate-900 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
            <i data-lucide="indian-rupee" class="w-6 h-6 text-sky-300"></i>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Today's Revenue</p>
            <p class="text-2xl font-extrabold text-white mt-0.5"><?= format_currency($metrics['today_revenue'] ?? 0) ?></p>
            <p class="text-xs text-emerald-300 font-medium mt-1 flex items-center gap-1">
                <i data-lucide="trending-up" class="w-3 h-3"></i> Live
            </p>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
            <i data-lucide="shopping-bag" class="w-6 h-6 text-indigo-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Orders</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['total_orders'] ?? 0) ?></p>
            <p class="text-xs text-slate-500 font-medium mt-1">All time</p>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Orders</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['pending_orders'] ?? 0) ?></p>
            <a href="<?= url('/admin/orders.php?status=pending') ?>" class="text-xs text-amber-600 font-medium mt-1 flex items-center gap-1 hover:underline">
                <i data-lucide="arrow-right" class="w-3 h-3"></i> View all
            </a>
        </div>
    </div>

    <!-- Low Stock Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-rose-600"></i>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Low Stock Items</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['low_stock_count'] ?? 0) ?></p>
            <a href="<?= url('/admin/inventory-low-stock.php') ?>" class="text-xs text-rose-600 font-medium mt-1 flex items-center gap-1 hover:underline">
                <i data-lucide="arrow-right" class="w-3 h-3"></i> Restock now
            </a>
        </div>
    </div>
</div>

<!-- Second Stats Row -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">
    <!-- Total Customers -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <i data-lucide="users" class="w-6 h-6 text-purple-600"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Customers</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['total_customers'] ?? 0) ?></p>
        </div>
    </div>

    <!-- Active Repairs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <i data-lucide="wrench" class="w-6 h-6 text-emerald-600"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Repairs</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['active_repairs'] ?? 0) ?></p>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center shrink-0">
            <i data-lucide="trending-up" class="w-6 h-6 text-teal-600"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monthly Revenue</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= format_currency($metrics['monthly_revenue'] ?? 0) ?></p>
        </div>
    </div>

    <!-- Total Products -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center shrink-0">
            <i data-lucide="package" class="w-6 h-6 text-sky-600"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Products</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['total_products'] ?? 0) ?></p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
            <i data-lucide="circle-check" class="w-6 h-6 text-green-600"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Completed Orders</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-0.5"><?= number_format($metrics['completed_orders'] ?? 0) ?></p>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
    <!-- Daily Sales Trend -->
    <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-bold text-slate-800">Daily Sales Trend</h2>
                <p class="text-xs text-slate-400">Last 30 days revenue</p>
            </div>
            <span class="text-xs bg-indigo-50 text-blue-700 font-semibold px-2.5 py-1 rounded-full">30 Days</span>
        </div>
        <div class="relative h-64">
            <canvas id="salesTrendChart"></canvas>
        </div>
    </div>

    <!-- Sales by Category Doughnut -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-bold text-slate-800">Sales by Category</h2>
                <p class="text-xs text-slate-400">Revenue breakdown</p>
            </div>
        </div>
        <div class="relative h-48">
            <canvas id="catDoughnutChart"></canvas>
        </div>
        <div id="catLegend" class="mt-3 space-y-1.5"></div>
    </div>
</div>

<!-- Bottom Three-Column Grid -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

    <!-- Top Products -->
    <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800">Top Products</h2>
            <a href="<?= url('/admin/products.php') ?>" class="text-xs text-blue-600 hover:underline font-medium">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (!empty($topProducts)): ?>
                        <?php foreach ($topProducts as $idx => $prod): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-3 py-3 flex items-center gap-3">
                                <?php if (!empty($prod['image'])): ?>
                                    <img src="<?= e($prod['image']) ?>" alt="" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                <?php else: ?>
                                    <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                        <i data-lucide="package" class="w-4 h-4 text-slate-400"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="text-sm font-medium text-slate-800 truncate max-w-[160px]"><?= e($prod['name']) ?></span>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-500"><?= e($prod['category_name'] ?? '—') ?></td>
                            <td class="px-3 py-3 text-right text-sm font-semibold text-slate-700"><?= number_format($prod['order_count']) ?></td>
                            <td class="px-3 py-3 text-right text-sm font-bold text-blue-700"><?= format_currency($prod['revenue']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-3 py-8 text-center text-sm text-slate-400">No product data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500"></i> Low Stock
            </h2>
            <a href="<?= url('/admin/inventory-low-stock.php') ?>" class="text-xs text-rose-600 hover:underline font-medium">View all &rarr;</a>
        </div>
        <div class="space-y-2">
            <?php if (!empty($lowStock)): ?>
                <?php foreach (array_slice($lowStock, 0, 8) as $item): ?>
                <div class="flex items-center justify-between p-2.5 bg-rose-50 rounded-lg border border-rose-100">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-slate-800 truncate"><?= e($item['product_name']) ?></p>
                        <p class="text-[10px] text-slate-500 font-mono"><?= e($item['product_sku'] ?? '') ?></p>
                    </div>
                    <div class="text-right shrink-0 ml-2">
                        <span class="text-sm font-black text-rose-600"><?= (int)$item['current_stock'] ?></span>
                        <span class="text-[10px] text-slate-400 block">/ <?= (int)($item['min_stock_threshold'] ?? 5) ?> min</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="py-8 text-center text-sm text-emerald-600 flex flex-col items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-400"></i>
                    All stock levels OK!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Orders + Recent Repairs -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

    <!-- Recent Orders -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800">Recent Orders</h2>
            <a href="<?= url('/admin/orders.php') ?>" class="text-xs text-blue-600 hover:underline font-medium">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Order#</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                        <?php
                            $statusColors = [
                                'pending'    => 'bg-yellow-100 text-yellow-700',
                                'processing' => 'bg-indigo-100 text-blue-700',
                                'shipped'    => 'bg-indigo-100 text-indigo-700',
                                'delivered'  => 'bg-green-100 text-green-700',
                                'cancelled'  => 'bg-red-100 text-red-700',
                            ];
                            $sc = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-700';
                        ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-3 py-2.5">
                                <a href="<?= url('/admin/order-detail.php?id=' . $order['id']) ?>" class="text-xs font-bold text-blue-600 hover:underline"><?= e($order['order_number']) ?></a>
                                <p class="text-[10px] text-slate-400"><?= date('d M, h:i A', strtotime($order['created_at'])) ?></p>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-slate-700 font-medium"><?= e($order['customer_name']) ?></td>
                            <td class="px-3 py-2.5 text-right text-xs font-bold text-slate-900"><?= format_currency($order['total_amount']) ?></td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block rounded-full px-2 py-1 text-xs font-medium <?= $sc ?>"><?= ucfirst($order['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-3 py-8 text-center text-sm text-slate-400">No recent orders.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Repairs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800">Recent Repairs</h2>
            <a href="<?= url('/admin/repairs.php') ?>" class="text-xs text-blue-600 hover:underline font-medium">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Ticket</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Device</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (!empty($recentRepairs)): ?>
                        <?php foreach ($recentRepairs as $repair): ?>
                        <?php
                            $rColors = [
                                'received'      => 'bg-indigo-100 text-blue-700',
                                'diagnosing'    => 'bg-purple-100 text-purple-700',
                                'repairing'     => 'bg-amber-100 text-amber-700',
                                'waiting_parts' => 'bg-yellow-100 text-yellow-700',
                                'completed'     => 'bg-green-100 text-green-700',
                                'delivered'     => 'bg-emerald-100 text-emerald-700',
                                'cancelled'     => 'bg-red-100 text-red-700',
                            ];
                            $rc = $rColors[$repair['status']] ?? 'bg-gray-100 text-gray-700';
                        ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-3 py-2.5">
                                <a href="<?= url('/admin/repair-detail.php?id=' . $repair['id']) ?>" class="text-xs font-bold text-blue-600 hover:underline"><?= e($repair['ticket_number']) ?></a>
                                <p class="text-[10px] text-slate-400"><?= e($repair['customer_name']) ?></p>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-slate-700"><?= e($repair['device_brand']) ?> <?= e($repair['device_model']) ?></td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-block rounded-full px-2 py-1 text-xs font-medium <?= $rc ?>"><?= ucfirst(str_replace('_', ' ', $repair['status'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="px-3 py-8 text-center text-sm text-slate-400">No recent repairs.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800">Recent Customer Registrations</h2>
            <a href="<?= url('/admin/customers.php') ?>" class="text-xs text-blue-600 hover:underline font-medium">View customers</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (!empty($recentCustomers)): ?>
                <?php foreach ($recentCustomers as $customer): ?>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= e($customer['full_name']) ?></p>
                            <p class="text-xs text-slate-500 truncate"><?= e($customer['email']) ?></p>
                        </div>
                        <time class="shrink-0 text-xs text-slate-400" datetime="<?= e($customer['created_at']) ?>"><?= date('d M, Y', strtotime($customer['created_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="py-8 text-center text-sm text-slate-400">No customer registrations yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-800">Recent Admin Activity</h2>
            <a href="<?= url('/admin/audit-logs.php') ?>" class="text-xs text-blue-600 hover:underline font-medium">Audit log</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (!empty($recentActivity)): ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate"><?= e(str_replace('_', ' ', $activity['action'])) ?></p>
                            <p class="text-xs text-slate-500 truncate"><?= e($activity['user_name'] ?? 'System') ?> &bull; <?= e($activity['module']) ?></p>
                        </div>
                        <time class="shrink-0 text-xs text-slate-400" datetime="<?= e($activity['created_at']) ?>"><?= date('d M, H:i', strtotime($activity['created_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="py-8 text-center text-sm text-slate-400">No activity recorded yet.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Chart.js Scripts -->
<script>
(function() {
    const trend = <?= $dailyTrendJson ?>;
    const cats  = <?= $catBreakdownJson ?>;

    // Daily Sales Trend Line Chart
    const tCtx = document.getElementById('salesTrendChart');
    if (tCtx && Array.isArray(trend.labels) && trend.labels.length) {
        new Chart(tCtx, {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: trend.data,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.08)',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: '#2563eb',
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: {
                    callbacks: { label: ctx => ' ₹' + ctx.parsed.y.toLocaleString('en-IN') }
                }},
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => '₹' + (v/1000).toFixed(0) + 'k', font:{size:10} }, grid:{ color:'rgba(0,0,0,0.04)' }},
                    x: { ticks: { font:{size:10}, maxTicksLimit: 10 }, grid:{ display:false }}
                }
            }
        });
    }

    // Category Doughnut
    const cCtx = document.getElementById('catDoughnutChart');
    if (cCtx && Array.isArray(cats.labels) && cats.labels.length) {
        const palette = ['#2563eb','#7c3aed','#0891b2','#059669','#d97706','#dc2626','#db2777','#65a30d'];
        new Chart(cCtx, {
            type: 'doughnut',
            data: {
                labels: cats.labels,
                datasets: [{
                    data: cats.data,
                    backgroundColor: palette.slice(0, cats.labels.length),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });

        const legend = document.getElementById('catLegend');
        cats.labels.forEach((categoryName, i) => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between text-xs';
            const labelGroup = document.createElement('span');
            labelGroup.className = 'flex items-center gap-1.5 min-w-0';
            const swatch = document.createElement('span');
            swatch.className = 'w-2.5 h-2.5 rounded-full shrink-0';
            swatch.style.backgroundColor = palette[i % palette.length];
            const label = document.createElement('span');
            label.className = 'text-slate-600 truncate';
            label.textContent = categoryName;
            const value = document.createElement('span');
            value.className = 'font-semibold text-slate-800';
            value.textContent = '₹' + Number(cats.data[i] || 0).toLocaleString('en-IN');
            labelGroup.append(swatch, label);
            div.append(labelGroup, value);
            legend.appendChild(div);
        });
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
})();
</script>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

