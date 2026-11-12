<?php
require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid Invoice ID.');
    redirect(url('/admin/invoices.php'));
}

$invoice = db_fetch_one(
    "SELECT i.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone
       FROM invoices i
  LEFT JOIN users u ON u.id = i.user_id
      WHERE i.id = ?",
    [$id]
);

if (!$invoice) {
    set_flash('error', 'Invoice not found.');
    redirect(url('/admin/invoices.php'));
}

// Store Settings
$store_name    = setting('site_name', 'Apex Mobile Hub');
$store_email   = setting('site_email', 'billing@apexmobilehub.com');
$store_phone   = setting('site_phone', '+91 98765 43210');
$store_address = setting('store_address', '124 Tech Gateway, Commercial Hub, Mumbai, India');
$store_gstin   = setting('gstin', '27AAPCA1234F1Z8');

// Resolve line items and customer based on invoice_type
$customer_name    = $invoice['user_name'] ?? 'Walk-in Customer';
$customer_email   = $invoice['user_email'] ?? '';
$customer_phone   = $invoice['user_phone'] ?? '';
$customer_address = '';
$items            = [];
$payment_method   = 'Paid';

if ($invoice['invoice_type'] === 'order') {
    $order = db_fetch_one("SELECT * FROM orders WHERE id = ?", [$invoice['reference_id']]);
    if ($order) {
        $customer_name    = $order['customer_name'] ?? $customer_name;
        $customer_email   = $order['customer_email'] ?? $customer_email;
        $customer_phone   = $order['customer_phone'] ?? $customer_phone;
        $customer_address = $order['shipping_address'] ?? '';
        $payment_method   = strtoupper($order['payment_method'] ?? 'Online');

        $order_items = db_fetch_all(
            "SELECT oi.*, p.name AS product_name, pv.variant_name AS variant_name
               FROM order_items oi
               JOIN products p ON p.id = oi.product_id
          LEFT JOIN product_variants pv ON pv.id = oi.variant_id
              WHERE oi.order_id = ?",
            [$order['id']]
        );
        foreach ($order_items as $oi) {
            $name = $oi['product_name'] . ($oi['variant_name'] ? ' (' . $oi['variant_name'] . ')' : '');
            $items[] = [
                'name'       => $name,
                'quantity'   => (int)$oi['quantity'],
                'unit_price' => (float)$oi['unit_price'],
                'total'      => (float)$oi['total_price'],
            ];
        }
    }
} elseif ($invoice['invoice_type'] === 'pos') {
    $pos = db_fetch_one("SELECT * FROM pos_sales WHERE id = ?", [$invoice['reference_id']]);
    if ($pos) {
        $customer_name  = $pos['customer_name'] ?: 'Walk-in Customer';
        $customer_phone = $pos['customer_phone'] ?: '';
        $payment_method = strtoupper($pos['payment_method'] ?? 'CASH');

        $pos_items = db_fetch_all("SELECT * FROM pos_sale_items WHERE pos_sale_id = ?", [$pos['id']]);
        foreach ($pos_items as $pi) {
            $name = $pi['product_name'] . ($pi['variant_name'] ? ' (' . $pi['variant_name'] . ')' : '');
            $items[] = [
                'name'       => $name,
                'quantity'   => (int)$pi['quantity'],
                'unit_price' => (float)$pi['unit_price'],
                'total'      => (float)$pi['total_price'],
            ];
        }
    }
} elseif ($invoice['invoice_type'] === 'repair') {
    $repair = db_fetch_one("SELECT * FROM repairs WHERE id = ?", [$invoice['reference_id']]);
    if ($repair) {
        $customer_name    = $repair['customer_name'];
        $customer_phone   = $repair['customer_phone'];
        $customer_email   = $repair['customer_email'];
        $customer_address = $repair['pickup_address'] ?: '';
        $payment_method   = 'Service Settlement';

        // Add repair service description item
        $items[] = [
            'name'       => "Device Repair Service: {$repair['device_brand']} {$repair['device_model']} (" . ucwords(str_replace('_', ' ', $repair['issue_type'])) . ")",
            'quantity'   => 1,
            'unit_price' => (float)$invoice['subtotal'],
            'total'      => (float)$invoice['subtotal'],
        ];

        // Also check if any spare parts were recorded
        $repair_parts = db_fetch_all("SELECT * FROM repair_parts WHERE repair_id = ?", [$repair['id']]);
        if (!empty($repair_parts)) {
            $items = []; // rebuild with detailed breakdown
            foreach ($repair_parts as $rp) {
                $items[] = [
                    'name'       => 'Spare Part: ' . $rp['part_name'],
                    'quantity'   => (int)$rp['quantity'],
                    'unit_price' => (float)$rp['unit_price'],
                    'total'      => (float)($rp['quantity'] * $rp['unit_price']),
                ];
            }
            $estimate = db_fetch_one("SELECT labour_cost FROM repair_estimates WHERE repair_id = ? ORDER BY id DESC LIMIT 1", [$repair['id']]);
            if ($estimate && (float)$estimate['labour_cost'] > 0) {
                $items[] = [
                    'name'       => 'Technician Diagnostic & Repair Labour',
                    'quantity'   => 1,
                    'unit_price' => (float)$estimate['labour_cost'],
                    'total'      => (float)$estimate['labour_cost'],
                ];
            }
        }
    }
}

// Fallback line item if none
if (empty($items)) {
    $items[] = [
        'name'       => ucfirst($invoice['invoice_type']) . " Settlement - Ref #" . (int)$invoice['reference_id'],
        'quantity'   => 1,
        'unit_price' => (float)$invoice['subtotal'],
        'total'      => (float)$invoice['subtotal'],
    ];
}

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Non-printable action buttons -->
    <div class="flex items-center justify-between no-print">
        <a href="<?= url('/admin/invoices.php') ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Invoices</span>
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Print Tax Invoice</span>
            </button>
        </div>
    </div>

    <!-- Printable Invoice Container -->
    <div id="printableInvoice" class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 sm:p-12 space-y-8 text-slate-800">
        <!-- Top Store Header -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6 border-b border-slate-100 pb-8">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5 mb-2">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-base shadow-sm">
                        A
                    </div>
                    <span class="text-xl font-black text-slate-900 tracking-tight"><?= e($store_name) ?></span>
                </div>
                <p class="text-xs text-slate-500"><?= e($store_address) ?></p>
                <p class="text-xs text-slate-500">Phone: <?= e($store_phone) ?> &bull; Email: <?= e($store_email) ?></p>
                <p class="text-xs font-mono font-bold text-slate-700">GSTIN: <?= e($store_gstin) ?></p>
            </div>

            <div class="text-left sm:text-right space-y-1">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase bg-blue-50 text-blue-700 border border-blue-100">
                    TAX INVOICE
                </span>
                <p class="text-2xl font-black text-slate-900 tracking-tight mt-1"><?= e($invoice['invoice_number']) ?></p>
                <p class="text-xs text-slate-500">Date: <?= date('d F Y', strtotime($invoice['issued_at'])) ?></p>
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Payment: <?= e($payment_method) ?> (PAID)</p>
            </div>
        </div>

        <!-- Bill To -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
            <div>
                <p class="font-bold text-slate-400 uppercase tracking-wider text-[10px] mb-1">Billed To Customer</p>
                <p class="text-base font-black text-slate-900"><?= e($customer_name) ?></p>
                <?php if (!empty($customer_phone)): ?>
                    <p class="text-slate-600 mt-0.5">Phone: <?= e($customer_phone) ?></p>
                <?php endif; ?>
                <?php if (!empty($customer_email)): ?>
                    <p class="text-slate-600">Email: <?= e($customer_email) ?></p>
                <?php endif; ?>
                <?php if (!empty($customer_address)): ?>
                    <p class="text-slate-500 mt-1 max-w-xs"><?= e($customer_address) ?></p>
                <?php endif; ?>
            </div>

            <div class="sm:text-right space-y-1">
                <p class="font-bold text-slate-400 uppercase tracking-wider text-[10px] mb-1">Billing Reference</p>
                <p class="font-bold text-slate-800">Source: <span class="uppercase text-blue-600"><?= e($invoice['invoice_type']) ?></span></p>
                <p class="font-mono text-slate-600">Ref ID: #<?= (int)$invoice['reference_id'] ?></p>
                <p class="text-slate-500">Place of Supply: Maharashtra (27)</p>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="border rounded-2xl border-slate-200 overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3 text-right">Qty</th>
                        <th class="px-5 py-3 text-right">Unit Price</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="px-5 py-3.5 font-bold text-slate-900"><?= e($it['name']) ?></td>
                            <td class="px-5 py-3.5 text-right font-medium"><?= (int)$it['quantity'] ?></td>
                            <td class="px-5 py-3.5 text-right font-medium"><?= format_currency($it['unit_price']) ?></td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-900"><?= format_currency($it['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-50/70 border-t border-slate-200 text-xs">
                    <tr>
                        <td colspan="3" class="px-5 py-2.5 text-right font-semibold text-slate-500">Subtotal:</td>
                        <td class="px-5 py-2.5 text-right font-bold text-slate-800"><?= format_currency($invoice['subtotal']) ?></td>
                    </tr>
                    <?php if ($invoice['discount_amount'] > 0): ?>
                        <tr>
                            <td colspan="3" class="px-5 py-2 text-right font-semibold text-emerald-600">Discount:</td>
                            <td class="px-5 py-2 text-right font-bold text-emerald-600">-<?= format_currency($invoice['discount_amount']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="3" class="px-5 py-2 text-right font-semibold text-slate-500">GST Tax (Incl.):</td>
                        <td class="px-5 py-2 text-right font-bold text-slate-800"><?= format_currency($invoice['tax_amount']) ?></td>
                    </tr>
                    <tr class="border-t border-slate-200">
                        <td colspan="3" class="px-5 py-3.5 text-right font-black text-slate-900 text-sm">Grand Total (INR):</td>
                        <td class="px-5 py-3.5 text-right font-black text-blue-600 text-lg"><?= format_currency($invoice['grand_total']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer / Terms -->
        <div class="border-t border-slate-100 pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-[11px] text-slate-400">
            <div>
                <p class="font-bold text-slate-700">Thank you for choosing <?= e($store_name) ?>!</p>
                <p class="mt-0.5">Goods once sold can be exchanged within 7 days with original invoice.</p>
            </div>
            <div class="text-left sm:text-right font-medium text-slate-500">
                <p>Authorized Signatory</p>
                <div class="h-8"></div>
                <p class="font-bold text-slate-700"><?= e($store_name) ?></p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body { background: white !important; }
    #adminSidebar, header, .no-print { display: none !important; }
    main { padding: 0 !important; overflow: visible !important; }
    #printableInvoice { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

