<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Handle Refund Processing ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $order_id = (int)($_POST['order_id'] ?? 0);
    $amount   = (float)($_POST['amount'] ?? 0);
    $reason   = trim($_POST['reason'] ?? '');

    $order = db_fetch_one("SELECT * FROM orders WHERE id = ?", [$order_id]);
    if (!$order) {
        set_flash('error', 'Order not found.');
        redirect(url('/admin/refunds.php'));
    }

    $payment = db_fetch_one("SELECT * FROM payments WHERE order_id = ? AND status = 'captured' LIMIT 1", [$order_id]);
    $payment_id = $payment['id'] ?? null;

    if ($amount <= 0 || $amount > (float)$order['total_amount']) {
        set_flash('error', 'Invalid refund amount requested.');
        redirect(url('/admin/refunds.php'));
    }

    if (empty($reason)) {
        set_flash('error', 'Please provide a valid refund reason.');
        redirect(url('/admin/refunds.php'));
    }

    db_begin();
    try {
        $refund_ref = 'REF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        db_insert(
            "INSERT INTO refunds (payment_id, order_id, amount, reason, gateway_refund_id, status, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, 'processed', ?, NOW())",
            [$payment_id, $order_id, $amount, $reason, $refund_ref, auth_id()]
        );

        // Update order status if full refund
        if ($amount >= (float)$order['total_amount']) {
            db_query("UPDATE orders SET payment_status = 'refunded', order_status = 'cancelled' WHERE id = ?", [$order_id]);
        }

        db_commit();
        set_flash('success', "Refund {$refund_ref} of " . format_currency($amount) . " processed successfully.");
    } catch (\Throwable $e) {
        db_rollback();
        set_flash('error', 'Refund failed: ' . $e->getMessage());
    }

    redirect(url('/admin/refunds.php'));
}

// Eligible orders for refund
$eligible_orders = db_fetch_all(
    "SELECT o.id, o.order_number, o.total_amount, u.full_name AS customer_name, o.created_at,
            (SELECT COALESCE(SUM(amount), 0) FROM refunds r WHERE r.order_id = o.id AND r.status != 'failed') AS already_refunded
       FROM orders o
       JOIN users u ON u.id = o.user_id
      WHERE o.payment_status = 'paid'
     HAVING (total_amount - already_refunded) > 0
   ORDER BY o.created_at DESC
      LIMIT 50"
);

// Refund history
$refunds = db_fetch_all(
    "SELECT r.*, o.order_number, u_cust.full_name AS customer_name, u.full_name AS creator_name
       FROM refunds r
  LEFT JOIN orders o ON o.id = r.order_id
  LEFT JOIN users u_cust ON u_cust.id = o.user_id
  LEFT JOIN users u ON u.id = r.created_by
   ORDER BY r.created_at DESC
      LIMIT 100"
);

$total_refunded = (float)db_fetch_value("SELECT COALESCE(SUM(amount), 0) FROM refunds WHERE status = 'processed'");

$pageTitle = 'Refunds Management';
?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-rose-600 mb-1">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                Returns &amp; Settlements
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Refunds Management</h1>
            <p class="text-sm text-slate-500 mt-0.5">Process order returns, manage refund balances, and review gateway settlements.</p>
        </div>
        <div class="p-3 rounded-2xl bg-white border border-slate-200 flex items-center gap-3">
            <span class="text-xs font-bold text-slate-400 uppercase">Total Refunded:</span>
            <span class="text-base font-black text-rose-600"><?= format_currency($total_refunded) ?></span>
        </div>
    </div>

    <!-- Eligible Orders for Refund -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-alert" class="w-4 h-4 text-rose-600"></i>
                <h2 class="text-sm font-bold text-slate-900">Orders Eligible for Refund</h2>
            </div>
            <span class="text-xs text-slate-400"><?= count($eligible_orders) ?> refundable orders</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Order Number</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Order Date</th>
                        <th class="px-5 py-3 text-right">Order Total</th>
                        <th class="px-5 py-3 text-right">Available Refund</th>
                        <th class="px-5 py-3 text-right">Process Refund</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($eligible_orders)): ?>
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No refundable orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($eligible_orders as $eo): ?>
                            <?php $avail = (float)$eo['total_amount'] - (float)$eo['already_refunded']; ?>
                            <tr>
                                <td class="px-5 py-3.5 font-bold text-blue-600">
                                    <a href="<?= url('/admin/order-detail.php?id=' . (int)$eo['id']) ?>" class="hover:underline">
                                        <?= e($eo['order_number']) ?>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($eo['customer_name']) ?></td>
                                <td class="px-5 py-3.5 text-slate-500"><?= date('d M Y', strtotime($eo['created_at'])) ?></td>
                                <td class="px-5 py-3.5 text-right font-bold text-slate-900"><?= format_currency($eo['total_amount']) ?></td>
                                <td class="px-5 py-3.5 text-right font-bold text-emerald-600"><?= format_currency($avail) ?></td>
                                <td class="px-5 py-3.5 text-right">
                                    <form method="POST" action="<?= url('/admin/refunds.php') ?>" class="inline-flex items-center gap-2 justify-end" onsubmit="return confirm('Process this refund?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="order_id" value="<?= (int)$eo['id'] ?>">
                                        <input type="number" name="amount" min="1" max="<?= $avail ?>" step="0.01" value="<?= $avail ?>" placeholder="Amount"
                                               class="w-24 px-2 py-1 text-xs rounded-lg border border-slate-200 text-right font-bold">
                                        <input type="text" name="reason" placeholder="Reason..." required
                                               class="w-32 px-2 py-1 text-xs rounded-lg border border-slate-200">
                                        <button type="submit" class="px-3 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition">
                                            Refund
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Refund History -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Refund Settlement History</h2>
            <span class="text-xs text-slate-400"><?= count($refunds) ?> recorded</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Order Ref</th>
                        <th class="px-5 py-3">Reason</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3 text-right">Processed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php if (empty($refunds)): ?>
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No refunds recorded in history.</td></tr>
                    <?php else: ?>
                        <?php foreach ($refunds as $rf): ?>
                            <tr>
                                <td class="px-5 py-3.5 text-slate-500 font-medium"><?= date('d M Y, h:i A', strtotime($rf['created_at'])) ?></td>
                                <td class="px-5 py-3.5 font-bold text-slate-900"><?= e($rf['order_number'] ?? '-') ?></td>
                                <td class="px-5 py-3.5 text-slate-700"><?= e($rf['reason']) ?></td>
                                <td class="px-5 py-3.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase">
                                        <?= e($rf['status']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right font-black text-rose-600"><?= format_currency($rf['amount']) ?></td>
                                <td class="px-5 py-3.5 text-right text-slate-500"><?= e($rf['creator_name'] ?? 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

