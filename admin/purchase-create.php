<?php
require_once __DIR__ . '/../includes/admin-header.php';

// ── Handle PO Creation ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $supplier_id    = (int)($_POST['supplier_id'] ?? 0);
    $purchase_date  = trim($_POST['purchase_date'] ?? date('Y-m-d'));
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $tax_rate       = (float)($_POST['tax_rate'] ?? 0);
    $notes          = trim($_POST['notes'] ?? '');

    $product_ids    = $_POST['product_id'] ?? [];
    $variant_ids    = $_POST['variant_id'] ?? [];
    $quantities     = $_POST['quantity'] ?? [];
    $unit_costs     = $_POST['unit_cost'] ?? [];

    $errors = [];
    if ($supplier_id <= 0) $errors[] = 'Please select a valid supplier.';
    if (empty($product_ids)) $errors[] = 'Please add at least one line item to the purchase.';

    // Validate supplier exists
    $supplier = db_fetch_one("SELECT id, company_name FROM suppliers WHERE id = ?", [$supplier_id]);
    if (!$supplier) $errors[] = 'Selected supplier does not exist.';

    if (empty($errors)) {
        db_begin();
        try {
            // Generate unique Purchase Order Number
            $po_number = 'PO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

            $subtotal = 0.0;
            $items_to_insert = [];

            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = (int)($product_ids[$i] ?? 0);
                $vid = !empty($variant_ids[$i]) ? (int)$variant_ids[$i] : null;
                $qty = max(1, (int)($quantities[$i] ?? 1));
                $cost = max(0.0, (float)($unit_costs[$i] ?? 0.0));

                if ($pid <= 0) continue;

                $line_total = $qty * $cost;
                $subtotal += $line_total;

                $items_to_insert[] = [
                    'product_id' => $pid,
                    'variant_id' => $vid,
                    'quantity'   => $qty,
                    'unit_cost'  => $cost,
                    'total_cost' => $line_total,
                ];
            }

            if (empty($items_to_insert)) {
                throw new RuntimeException('No valid items were included in this purchase.');
            }

            $tax_amount = round($subtotal * ($tax_rate / 100), 2);
            $total_amount = $subtotal + $tax_amount;

            $purchase_id = db_insert(
                "INSERT INTO purchases (supplier_id, purchase_number, invoice_number, purchase_date, subtotal, tax_amount, total_amount, payment_status, status, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, NOW())",
                [
                    $supplier_id,
                    $po_number,
                    $invoice_number ?: null,
                    $purchase_date,
                    $subtotal,
                    $tax_amount,
                    $total_amount,
                    $payment_status,
                    $notes ?: null,
                    auth_id()
                ]
            );

            foreach ($items_to_insert as $item) {
                db_insert(
                    "INSERT INTO purchase_items (purchase_id, product_id, variant_id, quantity, unit_cost, total_cost)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $purchase_id,
                        $item['product_id'],
                        $item['variant_id'],
                        $item['quantity'],
                        $item['unit_cost'],
                        $item['total_cost']
                    ]
                );
            }

            db_commit();
            set_flash('success', "Purchase Draft {$po_number} created successfully. You can receive the stock when goods arrive.");
            redirect(url('/admin/purchase-detail.php?id=' . $purchase_id));
        } catch (\Throwable $e) {
            db_rollback();
            set_flash('error', 'Failed to create purchase: ' . $e->getMessage());
            redirect(url('/admin/purchase-create.php'));
        }
    } else {
        set_flash('error', implode(' ', $errors));
    }
}

// Fetch active suppliers and catalog products with variants
$suppliers = db_fetch_all("SELECT id, company_name, name FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC");

$products = db_fetch_all(
    "SELECT p.id, p.name, p.sku, p.cost_price, p.base_price,
            pv.id AS variant_id, pv.variant_name, pv.sku AS variant_sku, p.cost_price AS variant_cost
       FROM products p
  LEFT JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active = 1
      WHERE p.is_active = 1
   ORDER BY p.name ASC, pv.variant_name ASC"
);

$pageTitle = 'Create Purchase Order Draft';
?>

<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="<?= url('/admin/purchases.php') ?>" class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Create Purchase Draft</h1>
                <p class="text-xs text-slate-500 mt-0.5">Order goods from suppliers. Stock will only be added to inventory when marked as 'Received'.</p>
            </div>
        </div>
    </div>

    <?php if (empty($suppliers)): ?>
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>You do not have any active suppliers yet. Please add a supplier first.</span>
            </div>
            <a href="<?= url('/admin/suppliers.php') ?>" class="px-3 py-1.5 rounded-lg bg-amber-600 text-white font-bold text-xs hover:bg-amber-700">Add Supplier</a>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/admin/purchase-create.php') ?>" id="purchaseForm" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Supplier & PO Information -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-blue-600"></i>
                Supplier &amp; PO Details
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Select Supplier *</label>
                    <select name="supplier_id" required class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                        <option value="">-- Choose Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= e($s['company_name']) ?> (<?= e($s['name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Purchase Date *</label>
                    <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Supplier Invoice Number</label>
                    <input type="text" name="invoice_number" placeholder="e.g. INV-98421"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Payment Status</label>
                    <select name="payment_status" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                        <option value="pending">Pending Payment</option>
                        <option value="partial">Partially Paid</option>
                        <option value="paid">Fully Paid</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tax Rate (%)</label>
                    <input type="number" name="tax_rate" id="tax_rate" value="18" min="0" max="100" step="0.01" oninput="calculateTotals()"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Order Notes / References</label>
                    <input type="text" name="notes" placeholder="e.g. Batch order for new flagship launch..."
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="boxes" class="w-4 h-4 text-blue-600"></i>
                    Purchase Line Items
                </h2>
                <button type="button" onclick="addLineItem()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs transition">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add Item</span>
                </button>
            </div>

            <div id="lineItemsContainer" class="space-y-3">
                <!-- Dynamic Items Inserted Here -->
            </div>

            <!-- Totals Summary -->
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <div class="w-72 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Items Subtotal:</span>
                        <span id="displaySubtotal" class="font-bold text-slate-800">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Tax Amount:</span>
                        <span id="displayTax" class="font-bold text-slate-800">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-sm font-black text-slate-900 pt-2 border-t border-slate-200">
                        <span>Grand Total:</span>
                        <span id="displayGrandTotal" class="text-blue-600">₹0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="<?= url('/admin/purchases.php') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition">
                Save Purchase Draft
            </button>
        </div>
    </form>
</div>

<!-- Template for Line Item -->
<template id="lineItemTemplate">
    <div class="line-item-row p-3 rounded-xl border border-slate-200 bg-slate-50/50 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
        <div class="sm:col-span-6">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">Product / Variant *</label>
            <select class="product-selector w-full px-3 py-2 text-xs rounded-lg border border-slate-200 bg-white" required onchange="handleProductChange(this)">
                <option value="">-- Choose Product --</option>
                <?php foreach ($products as $p): ?>
                    <?php
                    $label = $p['name'] . ($p['variant_name'] ? ' (' . $p['variant_name'] . ')' : '');
                    $sku = $p['variant_sku'] ?: $p['sku'];
                    $cost = $p['variant_cost'] ?? $p['cost_price'] ?? 0;
                    $val = (int)$p['id'] . '|' . ((int)($p['variant_id'] ?? 0)) . '|' . (float)$cost;
                    ?>
                    <option value="<?= e($val) ?>"><?= e($label) ?> — [<?= e($sku) ?>]</option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="product_id[]" class="input-product-id">
            <input type="hidden" name="variant_id[]" class="input-variant-id">
        </div>

        <div class="sm:col-span-2">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">Qty *</label>
            <input type="number" name="quantity[]" min="1" value="1" required class="input-qty w-full px-3 py-2 text-xs rounded-lg border border-slate-200" oninput="calculateTotals()">
        </div>

        <div class="sm:col-span-3">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">Unit Cost (₹) *</label>
            <input type="number" name="unit_cost[]" min="0" step="0.01" value="0.00" required class="input-cost w-full px-3 py-2 text-xs rounded-lg border border-slate-200" oninput="calculateTotals()">
        </div>

        <div class="sm:col-span-1 text-right sm:pt-4">
            <button type="button" onclick="this.closest('.line-item-row').remove(); calculateTotals();" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition" title="Remove line">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</template>

<script>
function addLineItem() {
    const container = document.getElementById('lineItemsContainer');
    const template = document.getElementById('lineItemTemplate');
    const clone = template.content.cloneNode(true);
    container.appendChild(clone);
    lucide.createIcons();
    calculateTotals();
}

function handleProductChange(select) {
    const row = select.closest('.line-item-row');
    const parts = select.value.split('|');
    if (parts.length >= 3) {
        row.querySelector('.input-product-id').value = parts[0];
        row.querySelector('.input-variant-id').value = parts[1] !== '0' ? parts[1] : '';
        row.querySelector('.input-cost').value = parseFloat(parts[2]).toFixed(2);
    } else {
        row.querySelector('.input-product-id').value = '';
        row.querySelector('.input-variant-id').value = '';
    }
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('.line-item-row');
    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.input-qty').value) || 0;
        const cost = parseFloat(row.querySelector('.input-cost').value) || 0;
        subtotal += (qty * cost);
    });

    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const taxAmount = (subtotal * (taxRate / 100));
    const grandTotal = subtotal + taxAmount;

    const fmt = (v) => '₹' + v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displaySubtotal').textContent = fmt(subtotal);
    document.getElementById('displayTax').textContent = fmt(taxAmount);
    document.getElementById('displayGrandTotal').textContent = fmt(grandTotal);
}

// Add an initial line item on load
document.addEventListener('DOMContentLoaded', () => {
    addLineItem();
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

