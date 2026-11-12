<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

session_init();
require_auth();

$userId = auth_id();
$orderId = (int)($_GET['id'] ?? 0);

$order = db_fetch_one(
    "SELECT o.*,
            sa.full_name as ship_name, sa.phone as ship_phone, sa.address_line1 as ship_line1,
            sa.address_line2 as ship_line2, sa.city as ship_city, sa.state as ship_state, sa.postal_code as ship_pin
     FROM orders o
     LEFT JOIN addresses sa ON o.shipping_address_id = sa.id AND sa.user_id = o.user_id
     WHERE o.id = ? AND o.user_id = ?
     LIMIT 1",
    [$orderId, $userId]
);

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('/orders.php');
}

$items = db_fetch_all(
    "SELECT oi.*, p.slug as product_slug 
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    [$orderId]
);

$timeline = db_fetch_all(
    "SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC",
    [$orderId]
);

$pageTitle = 'Order #' . $order['order_number'];
$robotsMeta = 'noindex, nofollow';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Order #<?= e($order['order_number']) ?></h1>
                <?php
                $statusColors = [
                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'processing' => 'bg-indigo-50 text-blue-700 border-blue-200',
                    'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                ];
                $badgeClass = $statusColors[$order['order_status']] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                ?>
                <span class="px-3 py-1 rounded-full border text-xs font-black uppercase <?= $badgeClass ?>">
                    <?= e($order['order_status']) ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-sm transition flex items-center gap-2">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Invoice</span>
            </button>

            <?php if ($order['order_status'] === 'pending'): ?>
                <form action="<?= url('/actions/order-cancel.php') ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="px-4 py-2 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs transition">
                        Cancel Order
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fulfillment Status -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
        <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider">Fulfillment Status</h3>
        
        <?php
        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
        $currentIdx = array_search($order['order_status'], $statuses);
        $isCancelled = ($order['order_status'] === 'cancelled');
        ?>

        <?php if ($isCancelled): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold flex items-center gap-2">
                <i data-lucide="x-circle" class="w-5 h-5 text-rose-600"></i>
                <span>This order was cancelled.</span>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-4 gap-2 text-center text-xs">
                <?php foreach ($statuses as $idx => $st): ?>
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs mb-2 transition <?= $idx <= $currentIdx ? 'bg-indigo-600 text-white shadow-md shadow-blue-500/25 ring-4 ring-blue-50' : 'bg-slate-100 text-slate-400' ?>">
                            <?= $idx + 1 ?>
                        </div>
                        <span class="font-bold uppercase tracking-wider text-[10px] <?= $idx <= $currentIdx ? 'text-blue-600' : 'text-slate-400' ?>">
                            <?= $st ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($timeline)): ?>
            <div class="border-t border-slate-100 pt-6">
                <h4 class="text-xs font-bold text-slate-800 mb-3">Activity Updates</h4>
                <div class="space-y-3">
                    <?php foreach ($timeline as $t): ?>
                        <div class="flex items-start gap-3 text-xs">
                            <span class="w-2 h-2 rounded-full bg-indigo-600 mt-1.5 shrink-0"></span>
                            <div>
                                <span class="font-bold text-slate-900"><?= e($t['comment'] ?? "Status changed to {$t['new_status']}") ?></span>
                                <span class="text-slate-400 text-[11px] block"><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Items & Shipping Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-4">
            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3">Purchased Items</h3>

            <div class="divide-y divide-slate-100">
                <?php foreach ($items as $it): ?>
                    <div class="py-4 flex items-center justify-between gap-4 text-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-blue-600 shrink-0">
                                <i data-lucide="smartphone" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <a href="<?= url('/product.php?slug=' . ($it['product_slug'] ?? '')) ?>" class="font-bold text-slate-900 hover:text-blue-600 transition block">
                                    <?= e($it['product_name']) ?>
                                </a>
                                <?php if (!empty($it['variant_name'])): ?>
                                    <span class="text-[11px] text-blue-600 font-semibold"><?= e($it['variant_name']) ?></span>
                                <?php endif; ?>
                                <span class="block text-[10px] text-slate-400 font-mono">SKU: <?= e($it['sku']) ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-extrabold text-slate-900"><?= format_currency($it['total_price']) ?></div>
                            <span class="text-slate-400 text-[11px]"><?= $it['quantity'] ?> &times; <?= format_currency($it['unit_price']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-3">
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">Delivery Address</h3>
                <div class="text-xs text-slate-600 leading-relaxed">
                    <strong class="text-slate-900 font-bold block"><?= e($order['ship_name'] ?? '') ?></strong>
                    <?= e($order['ship_line1'] ?? '') ?><br>
                    <?= !empty($order['ship_line2']) ? e($order['ship_line2']) . '<br>' : '' ?>
                    <?= e($order['ship_city'] ?? '') ?>, <?= e($order['ship_state'] ?? '') ?> - <?= e($order['ship_pin'] ?? '') ?><br>
                    <strong>Phone:</strong> <?= e($order['ship_phone'] ?? '') ?>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-3">
                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">Financial Breakdown</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span class="font-bold text-slate-900"><?= format_currency($order['subtotal']) ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-emerald-600 font-bold">
                            <span>Discount</span>
                            <span>-<?= format_currency($order['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-slate-600">
                        <span>GST Tax</span>
                        <span class="font-bold text-slate-900"><?= format_currency($order['tax_amount']) ?></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Shipping</span>
                        <span class="font-bold text-slate-900"><?= $order['shipping_fee'] > 0 ? format_currency($order['shipping_fee']) : 'FREE' ?></span>
                    </div>
                    <div class="border-t border-slate-100 pt-3 flex justify-between items-baseline font-black">
                        <span class="text-slate-900">Total Paid</span>
                        <span class="text-lg text-blue-600"><?= format_currency($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
