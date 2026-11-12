<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();
require_auth();

$userId = auth_id();

$sql = "SELECT o.*,
               COUNT(oi.id) as item_count,
               (SELECT product_name FROM order_items WHERE order_id = o.id LIMIT 1) as first_item_name
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.id DESC";
$orders = db_fetch_all($sql, [$userId]);

$pageTitle = 'My Orders';
$robotsMeta = 'noindex, nofollow';
require_once __DIR__ . '/includes/header.php';
?>

<div class="px-6 py-8">
    <div class="border-b mb-10 border-slate-200 pb-5">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">My Orders</h1>
        <p class="text-xs text-slate-500 mt-0.5">Track and view your recent purchases and download invoices</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-16 text-center text-slate-400 space-y-4 max-w-lg mx-auto">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto">
                <i data-lucide="package" class="w-8 h-8"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800">No orders placed yet</h2>
            <p class="text-xs text-slate-500 leading-relaxed">Browse our flagship smartphones, accessories, and chargers to place your first order.</p>
            <div class="pt-2">
                <a href="<?= url('/shop.php') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 text-white font-bold text-xs hover:bg-indigo-700 transition">
                    <span>Explore Products</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $ord): ?>
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs hover:border-slate-300 transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <span class="font-extrabold text-sm text-slate-900">#<?= e($ord['order_number']) ?></span>
                            <?php
                            $statusColors = [
                                'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'processing' => 'bg-indigo-50 text-blue-700 border-blue-200',
                                'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                            ];
                            $badgeClass = $statusColors[$ord['order_status']] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                            ?>
                            <span class="px-2.5 py-0.5 rounded-full border text-[10px] font-extrabold uppercase <?= $badgeClass ?>">
                                <?= e($ord['order_status']) ?>
                            </span>
                            <span class="text-xs text-slate-400">&bull; <?= date('d M Y, h:i A', strtotime($ord['created_at'])) ?></span>
                        </div>
                        <p class="text-xs text-slate-600">
                            <?= $ord['item_count'] ?> item(s): <strong class="text-slate-800"><?= e($ord['first_item_name'] ?? 'Mobile Product') ?></strong> <?= $ord['item_count'] > 1 ? 'and more' : '' ?>
                        </p>
                    </div>

                    <div class="flex items-center gap-4 self-end sm:self-center">
                        <div class="text-right">
                            <div class="text-sm font-black text-slate-900"><?= format_currency($ord['total_amount']) ?></div>
                            <span class="text-[10px] font-bold uppercase text-slate-400"><?= strtoupper($ord['payment_method']) ?> (<?= strtoupper($ord['payment_status']) ?>)</span>
                        </div>
                        <a href="<?= url('/order-detail.php?id=' . $ord['id']) ?>" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition flex items-center gap-1.5">
                            <span>Details</span>
                            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

