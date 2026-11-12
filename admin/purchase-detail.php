<?php
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid Purchase Order ID.');
    redirect(url('/admin/purchases.php'));
}

$purchase = db_fetch_one(
    "SELECT p.*, s.company_name, s.name AS supplier_contact, s.email AS supplier_email, s.phone AS supplier_phone,
            s.gstin AS supplier_gstin, s.address AS supplier_address,
            u_creator.full_name AS creator_name, u_receiver.full_name AS receiver_name
       FROM purchases p
       JOIN suppliers s ON s.id = p.supplier_id
  LEFT JOIN users u_creator ON u_creator.id = p.created_by
  LEFT JOIN users u_receiver ON u_receiver.id = p.received_by
      WHERE p.id = ?",
    [$id]
);

if (!$purchase) {
    set_flash('error', 'Purchase order not found.');
    redirect(url('/admin/purchases.php'));
}

// ── Handle Status Actions ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'receive_stock' && $purchase['status'] === 'draft') {
        $items = db_fetch_all("SELECT * FROM purchase_items WHERE purchase_id = ?", [$id]);

        db_begin();
        try {
            foreach ($items as $item) {
                // Add stock via standard inventory service
                inventory_add_stock(
                    (int)$item['product_id'],
                    !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    (int)$item['quantity'],
                    'purchase',
                    (string)$purchase['purchase_number'],
                    (float)$item['unit_cost'],
                    'Received via PO #' . $purchase['purchase_number'],
                    auth_id()
                );
            }

            db_query(
                "UPDATE purchases SET status = 'received', received_at = NOW(), received_by = ? WHERE id = ?",
                [auth_id(), $id]
            );

            db_commit();
            set_flash('success', 'Purchase order received successfully! Stock has been added to inventory.');
        } catch (\Throwable $e) {
            db_rollback();
            set_flash('error', 'Failed to receive stock: ' . $e->getMessage());
        }
        redirect(url('/admin/purchase-detail.php?id=' . $id));
    }

    if ($action === 'cancel_order' && $purchase['status'] === 'draft') {
        db_query("UPDATE purchases SET status = 'cancelled' WHERE id = ?", [$id]);
        set_flash('success', 'Purchase order has been cancelled.');
        redirect(url('/admin/purchase-detail.php?id=' . $id));
    }

    if ($action === 'update_payment') {
        $payment_status = $_POST['payment_status'] ?? 'pending';
        if (in_array($payment_status, ['pending', 'partial', 'paid'], true)) {
            db_query("UPDATE purchases SET payment_status = ? WHERE id = ?", [$payment_status, $id]);
            set_flash('success', 'Payment status updated.');
        }
        redirect(url('/admin/purchase-detail.php?id=' . $id));
    }
}

// Fetch line items
$items = db_fetch_all(
    "SELECT pi.*, p.name AS product_name, p.sku AS base_sku,
            pv.variant_name, pv.sku AS variant_sku
       FROM purchase_items pi
       JOIN products p ON p.id = pi.product_id
  LEFT JOIN product_variants pv ON pv.id = pi.variant_id
      WHERE pi.purchase_id = ?
   ORDER BY pi.id ASC",
    [$id]
);

$pageTitle = 'PO #' . $purchase['purchase_number'];
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/purchases.php') ?>" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight"><?= e($purchase['purchase_number']) ?></h1>
                    <?php
                    $status_class = match ($purchase['status']) {
                        'received' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-100',
                        default => 'bg-amber-50 text-amber-800 border-amber-100',
                    };
                    ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $status_class ?>">
                        <?= ucfirst($purchase['status']) ?>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Created on <?= date('d M Y, h:i A', strtotime($purchase['created_at'])) ?> by <?= e($purchase['creator_name'] ?? 'Staff') ?></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <?php if ($purchase['status'] === 'draft'): ?>
                <form method="POST" action="<?= url('/admin/purchase-detail.php?id=' . $id) ?>" onsubmit="return confirm('Confirm receipt of stock? Quantities will be immediately added to inventory.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="receive_stock">
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                        <i data-lucide="package-check" class="w-4 h-4"></i>
                        <span>Receive Stock Into Inventory</span>
                    </button>
                </form>

                <form method="POST" action="<?= url('/admin/purchase-detail.php?id=' . $id) ?>" onsubmit="return confirm('Are you sure you want to cancel this purchase order?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel_order">
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 font-bold text-xs transition">
                        <span>Cancel PO</span>
                    </button>
                </form>
            <?php elseif ($purchase['status'] === 'received'): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                    Received on <?= date('d M Y', strtotime($purchase['received_at'])) ?>
                </span>
            <?php endif; ?>

            <button onclick="window.print()" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 transition" title="Print PO">
                <i data-lucide="printer" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- PO Details & Supplier Info Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Supplier Details -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Supplier / Vendor</h3>
            <div>
                <p class="font-bold text-slate-900 text-base"><?= e($purchase['company_name']) ?></p>
                <p class="text-xs text-slate-600 mt-0.5">Contact: <?= e($purchase['supplier_contact']) ?></p>
                <p class="text-xs text-slate-600">Phone: <?= e($purchase['supplier_phone']) ?></p>
                <?php if (!empty($purchase['supplier_email'])): ?>
                    <p class="text-xs text-slate-600">Email: <?= e($purchase['supplier_email']) ?></p>
                <?php endif; ?>
                <?php if (!empty($purchase['supplier_gstin'])): ?>
                    <p class="text-xs text-slate-600 font-mono mt-1">GSTIN: <?= e($purchase['supplier_gstin']) ?></p>
                <?php endif; ?>
                <?php if (!empty($purchase['supplier_address'])): ?>
                    <p class="text-xs text-slate-400 mt-1"><?= e($purchase['supplier_address']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- PO Meta -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Order Information</h3>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <span class="text-slate-400 block">Purchase Date</span>
                    <span class="font-bold text-slate-800"><?= date('d F Y', strtotime($purchase['purchase_date'])) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block">Supplier Invoice</span>
                    <span class="font-bold text-slate-800 font-mono"><?= e($purchase['invoice_number'] ?: 'None specified') ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block">Payment Status</span>
                    <form method="POST" action="<?= url('/admin/purchase-detail.php?id=' . $id) ?>" class="inline-flex items-center gap-1.5 mt-0.5">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_payment">
                        <select name="payment_status" onchange="this.form.submit()" class="px-2 py-1 text-xs rounded-lg border border-slate-200 bg-white font-bold">
                            <option value="pending" <?= $purchase['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="partial" <?= $purchase['payment_status'] === 'partial' ? 'selected' : '' ?>>Partial</option>
                            <option value="paid" <?= $purchase['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                        </select>
                    </form>
                </div>
                <div>
                    <span class="text-slate-400 block">Stock In Status</span>
                    <span class="font-bold text-slate-800"><?= ucfirst($purchase['status']) ?></span>
                </div>
            </div>

            <?php if (!empty($purchase['notes'])): ?>
                <div class="pt-2 border-t border-slate-100 text-xs">
                    <span class="text-slate-400 block">Notes:</span>
                    <span class="text-slate-600"><?= e($purchase['notes']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="rounded-2xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Line Items &amp; Stock Quantities</h3>
            <span class="text-xs text-slate-400"><?= count($items) ?> items</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Item Description</th>
                        <th class="px-5 py-3">SKU</th>
                        <th class="px-5 py-3 text-right">Quantity</th>
                        <th class="px-5 py-3 text-right">Unit Cost</th>
                        <th class="px-5 py-3 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-900"><?= e($item['product_name']) ?></span>
                                <?php if (!empty($item['variant_name'])): ?>
                                    <span class="block text-[11px] text-blue-600"><?= e($item['variant_name']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-slate-500">
                                <?= e($item['variant_sku'] ?: $item['base_sku']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-900">
                                <?= number_format($item['quantity']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-right font-medium text-slate-600">
                                <?= format_currency($item['unit_cost']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-900">
                                <?= format_currency($item['total_cost']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-50/70 border-t border-slate-200 text-xs">
                    <tr>
                        <td colspan="4" class="px-5 py-2.5 text-right font-semibold text-slate-500">Subtotal:</td>
                        <td class="px-5 py-2.5 text-right font-bold text-slate-800"><?= format_currency($purchase['subtotal']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-5 py-2 text-right font-semibold text-slate-500">Tax Amount:</td>
                        <td class="px-5 py-2 text-right font-bold text-slate-800"><?= format_currency($purchase['tax_amount']) ?></td>
                    </tr>
                    <tr class="border-t border-slate-200">
                        <td colspan="4" class="px-5 py-3 text-right font-black text-slate-900 text-sm">Grand Total:</td>
                        <td class="px-5 py-3 text-right font-black text-blue-600 text-base"><?= format_currency($purchase['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

