<?php
/**
 * MobileShopFlat — Admin Order Detail
 */

require_once __DIR__ . '/../includes/admin-header.php';

$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid order ID.');
    redirect(url('/admin/orders.php'));
}

// ── POST: update_status ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    if (!verify_csrf()) {
        set_flash('error', 'Invalid CSRF token.');
        redirect(url('/admin/order-detail.php?id=' . $id));
    }

    $newStatus = $_POST['order_status'] ?? '';
    $comment   = trim($_POST['comment'] ?? '');

    if (!in_array($newStatus, $validStatuses, true)) {
        set_flash('error', 'Invalid order status.');
        redirect(url('/admin/order-detail.php?id=' . $id));
    }

    $order = db_fetch_one("SELECT id, order_status FROM orders WHERE id = ?", [$id]);
    if (!$order) {
        set_flash('error', 'Order not found.');
        redirect(url('/admin/orders.php'));
    }

    if ($newStatus === 'cancelled' && $order['order_status'] !== 'cancelled') {
        require_once __DIR__ . '/../includes/inventory-functions.php';
        $items = db_fetch_all("SELECT product_id, variant_id, quantity, unit_price FROM order_items WHERE order_id = ?", [$id]);
        foreach ($items as $item) {
            inventory_add_stock(
                (int)$item['product_id'],
                $item['variant_id'] ? (int)$item['variant_id'] : null,
                (int)$item['quantity'],
                'order_cancellation',
                'ORD-' . $id,
                (float)$item['unit_price'],
                'Restocked on order cancellation by admin',
                auth_id()
            );
        }
    }

    db_query("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $id]);
    db_insert(
        "INSERT INTO order_status_history (order_id, old_status, new_status, comment, changed_by, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$id, $order['order_status'], $newStatus, $comment, auth_id()]
    );

    set_flash('success', 'Order status updated to ' . ucfirst($newStatus) . '.');
    redirect(url('/admin/order-detail.php?id=' . $id));
}

// ── Load order ────────────────────────────────────────────────────────────────
$order = db_fetch_one(
    "SELECT o.*, o.total_amount AS grand_total,
            u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
            a.full_name AS addr_name, a.phone AS addr_phone,
            a.address_line1, a.address_line2, a.city, a.state, a.postal_code AS pincode
     FROM orders o
     JOIN users u ON u.id = o.user_id
     LEFT JOIN addresses a ON a.id = o.shipping_address_id
     WHERE o.id = ?",
    [$id]
);

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect(url('/admin/orders.php'));
}

$items   = db_fetch_all(
    "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC",
    [$id]
);
$history = db_fetch_all(
    "SELECT osh.*, u.full_name AS changed_by_name
     FROM order_status_history osh
     LEFT JOIN users u ON u.id = osh.changed_by
     WHERE osh.order_id = ?
     ORDER BY osh.id ASC",
    [$id]
);

// ── Badge helpers ─────────────────────────────────────────────────────────────
function orderStatusBadgeDetail(string $status): string
{
    $map = [
        'pending'    => 'bg-amber-50 text-amber-700 border-amber-200',
        'processing' => 'bg-blue-50 text-blue-700 border-blue-200',
        'shipped'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'delivered'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled'  => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ' . $cls . '">'
         . e(ucfirst($status)) . '</span>';
}

function paymentStatusBadgeDetail(string $status): string
{
    $map = [
        'paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'failed'   => 'bg-rose-50 text-rose-700 border-rose-200',
        'refunded' => 'bg-blue-50 text-blue-700 border-blue-200',
    ];
    $cls = $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border ' . $cls . '">'
         . e(ucfirst($status)) . '</span>';
}
?>

<div class="space-y-6">
    <!-- Back + Title -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/orders.php') ?>"
               class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition text-slate-500">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Order <?= e($order['order_number']) ?></h1>
                <p class="mt-0.5 text-sm text-slate-500">Placed on <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?= paymentStatusBadgeDetail($order['payment_status']) ?>
            <?= orderStatusBadgeDetail($order['order_status']) ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="space-y-5">
            <!-- Customer Info Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Customer
                    </h2>
                </div>
                <div class="px-5 py-4 space-y-2.5 text-sm text-slate-700">
                    <div class="flex items-center gap-2">
                        <i data-lucide="user-circle" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <span class="font-semibold"><?= e($order['customer_name']) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <span><?= e($order['customer_email']) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="phone" class="w-4 h-4 text-slate-400 shrink-0"></i>
                        <span><?= e($order['customer_phone'] ?? '—') ?></span>
                    </div>
                </div>
            </div>

            <!-- Shipping Address Card -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-slate-400"></i> Shipping Address
                    </h2>
                </div>
                <div class="px-5 py-4 text-sm text-slate-700 leading-relaxed">
                    <?php if (!empty($order['addr_name'])): ?>
                        <div class="font-semibold text-slate-800"><?= e($order['addr_name']) ?></div>
                        <div class="text-slate-500 text-xs mt-1">
                            <?= e($order['address_line1']) ?>
                            <?php if (!empty($order['address_line2'])): ?>, <?= e($order['address_line2']) ?><?php endif; ?><br>
                            <?= e($order['city']) ?>, <?= e($order['state']) ?> — <?= e($order['pincode']) ?><br>
                            <span class="mt-1 block"><?= e($order['addr_phone']) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="text-slate-400 text-xs">No shipping address recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Notes -->
            <?php if (!empty($order['notes'])): ?>
            <div class="bg-amber-50 rounded-xl border border-amber-200 px-5 py-4 text-sm text-amber-800">
                <div class="font-semibold mb-1 flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Order Notes
                </div>
                <?= e($order['notes']) ?>
            </div>
            <?php endif; ?>

            <!-- Update Status Form -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-400"></i> Update Status
                    </h2>
                </div>
                <form method="POST" action="<?= url('/admin/order-detail.php?id=' . $id) ?>" class="px-5 py-4 space-y-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_status">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">New Status</label>
                        <select name="order_status"
                                class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none">
                            <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                                <option value="<?= e($s) ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Comment <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea name="comment" rows="2"
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 outline-none resize-none"
                                  placeholder="Add a note about this status change…"></textarea>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                        <i data-lucide="check" class="w-4 h-4"></i> Update Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Order Items -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="package" class="w-4 h-4 text-slate-400"></i> Order Items
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-bold">
                            <tr>
                                <th class="px-5 py-3 text-left">Product</th>
                                <th class="px-5 py-3 text-left">SKU</th>
                                <th class="px-5 py-3 text-center">Qty</th>
                                <th class="px-5 py-3 text-right">Unit Price</th>
                                <th class="px-5 py-3 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-5 py-3 font-medium text-slate-800"><?= e($item['product_name']) ?></td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-500"><?= e($item['sku'] ?? '—') ?></td>
                                <td class="px-5 py-3 text-center text-slate-700"><?= (int)$item['quantity'] ?></td>
                                <td class="px-5 py-3 text-right text-slate-700"><?= format_currency($item['unit_price']) ?></td>
                                <td class="px-5 py-3 text-right font-semibold text-slate-800"><?= format_currency($item['total_price']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-slate-50 text-sm">
                            <tr>
                                <td colspan="4" class="px-5 py-2.5 text-right text-slate-500">Subtotal</td>
                                <td class="px-5 py-2.5 text-right font-semibold text-slate-800"><?= format_currency($order['subtotal']) ?></td>
                            </tr>
                            <?php if ((float)$order['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="4" class="px-5 py-2 text-right text-emerald-600">Discount</td>
                                <td class="px-5 py-2 text-right font-semibold text-emerald-600">−<?= format_currency($order['discount_amount']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="4" class="px-5 py-2 text-right text-slate-500">Tax</td>
                                <td class="px-5 py-2 text-right font-semibold text-slate-800"><?= format_currency($order['tax_amount']) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-5 py-2 text-right text-slate-500">Shipping</td>
                                <td class="px-5 py-2 text-right font-semibold text-slate-800"><?= format_currency($order['shipping_fee']) ?></td>
                            </tr>
                            <tr class="border-t border-slate-200">
                                <td colspan="4" class="px-5 py-3 text-right font-bold text-slate-900 text-base">Grand Total</td>
                                <td class="px-5 py-3 text-right font-bold text-indigo-700 text-base"><?= format_currency($order['grand_total']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i> Status Timeline
                    </h2>
                </div>
                <div class="px-5 py-4">
                    <?php if ($history): ?>
                    <ol class="relative border-l border-slate-200 space-y-6 ml-2">
                        <?php foreach ($history as $h): ?>
                        <li class="ml-6">
                            <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 ring-4 ring-white">
                                <i data-lucide="git-commit-horizontal" class="w-3 h-3 text-indigo-600"></i>
                            </span>
                            <div class="text-xs text-slate-400 mb-0.5">
                                <?= date('d M Y, h:i A', strtotime($h['created_at'])) ?>
                                <?php if (!empty($h['changed_by_name'])): ?>&bull; <span class="font-medium text-slate-600"><?= e($h['changed_by_name']) ?></span><?php endif; ?>
                            </div>
                            <div class="text-sm font-semibold text-slate-700">
                                <?= e(ucfirst($h['status_from'] ?? '—')) ?> → <?= e(ucfirst($h['status_to'])) ?>
                            </div>
                            <?php if (!empty($h['comment'])): ?>
                            <div class="mt-1 text-xs text-slate-500 italic"><?= e($h['comment']) ?></div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else: ?>
                    <p class="text-sm text-slate-400 text-center py-4">No status history yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
