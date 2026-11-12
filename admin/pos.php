<?php
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

// ── AJAX Product Search Endpoint ──────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 1) {
        json_response(['success' => false, 'products' => []]);
    }

    $like = '%' . $q . '%';
    $results = db_fetch_all(
        "SELECT p.id, p.name, p.sku, p.base_price, p.sale_price, p.thumbnail,
                pv.id AS variant_id, pv.variant_name, pv.sku AS variant_sku,
                (CASE WHEN p.sale_price > 0 THEN p.sale_price ELSE p.base_price END) + COALESCE(pv.additional_price, 0) AS variant_price,
                COALESCE(inv.current_stock, 0) AS stock
           FROM products p
      LEFT JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active = 1
      LEFT JOIN inventory inv ON inv.product_id = p.id AND (inv.variant_id = pv.id OR (pv.id IS NULL AND inv.variant_id IS NULL))
          WHERE p.is_active = 1
            AND (p.name LIKE ? OR p.sku = ? OR pv.sku = ? OR p.sku LIKE ? OR pv.variant_name LIKE ?)
       ORDER BY p.name ASC
          LIMIT 20",
        [$like, $q, $q, $like, $like]
    );

    $formatted = [];
    foreach ($results as $r) {
        $price = (float)($r['variant_price'] ?? ($r['sale_price'] > 0 ? $r['sale_price'] : $r['base_price']));
        $formatted[] = [
            'id'           => (int)$r['id'],
            'variant_id'   => !empty($r['variant_id']) ? (int)$r['variant_id'] : null,
            'name'         => $r['name'],
            'variant_name' => $r['variant_name'] ?? null,
            'sku'          => $r['variant_sku'] ?: $r['sku'],
            'price'        => $price,
            'stock'        => (int)$r['stock'],
            'thumbnail'    => $r['thumbnail'] ?: null,
        ];
    }

    json_response(['success' => true, 'products' => $formatted]);
}

// ── Handle POS Sale Submission ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $customer_name   = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    $customer_phone  = trim($_POST['customer_phone'] ?? '');
    $payment_method  = $_POST['payment_method'] ?? 'cash';
    $discount_amount = max(0.0, (float)($_POST['discount_amount'] ?? 0));
    $amount_paid     = max(0.0, (float)($_POST['amount_paid'] ?? 0));
    $notes           = trim($_POST['notes'] ?? '');

    $cart_json = $_POST['cart_data'] ?? '[]';
    $cart_items = json_decode($cart_json, true) ?: [];

    if (empty($cart_items)) {
        set_flash('error', 'Cannot complete sale: POS cart is empty.');
        redirect(url('/admin/pos.php'));
    }

    db_begin();
    try {
        $subtotal = 0.0;
        $items_to_process = [];

        foreach ($cart_items as $item) {
            $pid = (int)($item['id'] ?? 0);
            $vid = !empty($item['variant_id']) ? (int)$item['variant_id'] : null;
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $unit_price = max(0.0, (float)($item['price'] ?? 0));

            // Check current stock
            $stock_row = db_fetch_one(
                "SELECT current_stock FROM inventory WHERE product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL)) FOR UPDATE",
                [$pid, $vid, $vid]
            );
            $available = $stock_row ? (int)$stock_row['current_stock'] : 0;
            if ($available < $qty) {
                throw new RuntimeException("Insufficient stock for '{$item['name']}'. Requested: {$qty}, Available: {$available}");
            }

            $line_total = round($qty * $unit_price, 2);
            $subtotal += $line_total;

            $items_to_process[] = [
                'product_id'   => $pid,
                'variant_id'   => $vid,
                'product_name' => $item['name'],
                'variant_name' => $item['variant_name'] ?? null,
                'sku'          => $item['sku'] ?? 'GEN',
                'unit_price'   => $unit_price,
                'quantity'     => $qty,
                'total_price'  => $line_total,
            ];
        }

        // Standard 18% GST (Tax included in retail price)
        $tax_amount = round($subtotal * (18 / 118), 2);
        $grand_total = max(0.0, round($subtotal - $discount_amount, 2));

        if ($amount_paid < $grand_total && $payment_method === 'cash') {
            $amount_paid = $grand_total; // default to exact if not entered
        }
        $change_returned = max(0.0, round($amount_paid - $grand_total, 2));

        $invoice_number = 'POS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        // 1. Insert pos_sales
        $sale_id = db_insert(
            "INSERT INTO pos_sales (invoice_number, cashier_id, customer_name, customer_phone, subtotal, tax_amount, discount_amount, grand_total, payment_method, amount_paid, change_returned, status, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())",
            [
                $invoice_number,
                auth_id(),
                $customer_name ?: 'Walk-in Customer',
                $customer_phone ?: null,
                $subtotal,
                $tax_amount,
                $discount_amount,
                $grand_total,
                $payment_method,
                $amount_paid,
                $change_returned,
                $notes ?: null
            ]
        );

        // 2. Insert pos_sale_items and deduct stock
        foreach ($items_to_process as $it) {
            db_insert(
                "INSERT INTO pos_sale_items (pos_sale_id, product_id, variant_id, product_name, variant_name, sku, unit_price, quantity, tax_rate, total_price)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 18.00, ?)",
                [
                    $sale_id,
                    $it['product_id'],
                    $it['variant_id'],
                    $it['product_name'],
                    $it['variant_name'],
                    $it['sku'],
                    $it['unit_price'],
                    $it['quantity'],
                    $it['total_price']
                ]
            );

            // Deduct stock from inventory
            inventory_deduct_stock(
                $it['product_id'],
                $it['variant_id'],
                $it['quantity'],
                'pos_sale',
                $invoice_number,
                "POS Counter Sale #{$invoice_number}",
                auth_id()
            );
        }

        // 3. Create master invoice
        $inv_id = db_insert(
            "INSERT INTO invoices (invoice_number, invoice_type, reference_id, subtotal, tax_amount, discount_amount, grand_total, status, issued_at)
             VALUES (?, 'pos', ?, ?, ?, ?, ?, 'paid', NOW())",
            [$invoice_number, $sale_id, $subtotal, $tax_amount, $discount_amount, $grand_total]
        );

        db_commit();
        set_flash('success', "Sale #{$invoice_number} completed successfully!");
        redirect(url('/admin/invoice-view.php?id=' . $inv_id));
    } catch (\Throwable $e) {
        db_rollback();
        set_flash('error', 'Sale failed: ' . $e->getMessage());
        redirect(url('/admin/pos.php'));
    }
}

// Quick products list (top in-stock products)
$quick_products = db_fetch_all(
    "SELECT p.id, p.name, p.sku, p.base_price, p.sale_price, p.thumbnail,
            pv.id AS variant_id, pv.variant_name, pv.sku AS variant_sku,
            (CASE WHEN p.sale_price > 0 THEN p.sale_price ELSE p.base_price END) + COALESCE(pv.additional_price, 0) AS variant_price,
            COALESCE(inv.current_stock, 0) AS stock
       FROM products p
  LEFT JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active = 1
  LEFT JOIN inventory inv ON inv.product_id = p.id AND (inv.variant_id = pv.id OR (pv.id IS NULL AND inv.variant_id IS NULL))
      WHERE p.is_active = 1 AND COALESCE(inv.current_stock, 0) > 0
   ORDER BY inv.current_stock DESC, p.name ASC
      LIMIT 12"
);

$pageTitle = 'POS Cash Register Terminal';
?>

<div class="h-full flex flex-col space-y-4 max-w-[1600px] mx-auto">
    <!-- Top Bar -->
    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-sm">
                <i data-lucide="calculator" class="w-5 h-5"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Counter POS Terminal</h1>
                <p class="text-xs text-slate-500">Scan barcode or search products to process in-store sales</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= url('/admin/invoices.php?type=pos') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition">
                <i data-lucide="receipt" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>POS Sales History</span>
            </a>
            <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Terminal Active
            </span>
        </div>
    </div>

    <!-- Main Workspace: Left Catalog, Right Cart -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 flex-1 items-start">
        <!-- Left: Product Scanner & Catalog (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <!-- Search & Barcode Scan Input -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs relative">
                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                    <i data-lucide="scan-barcode" class="w-4 h-4 text-emerald-600"></i>
                    Scan Barcode or Search Catalog
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" id="posSearchInput" placeholder="Scan barcode with scanner or type product name / SKU..." autofocus autocomplete="off"
                           class="w-full pl-10 pr-10 py-3 text-sm rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition shadow-inner">
                    <div id="searchSpinner" class="hidden absolute right-3.5 top-1/2 -translate-y-1/2">
                        <i data-lucide="loader-2" class="w-4 h-4 text-emerald-600 animate-spin"></i>
                    </div>
                </div>

                <!-- Instant Search Dropdown Results -->
                <div id="searchResultsDropdown" class="hidden absolute left-4 right-4 top-full mt-2 bg-white rounded-2xl border border-slate-200 shadow-2xl z-30 max-h-80 overflow-y-auto divide-y divide-slate-100">
                    <!-- Populated via JS -->
                </div>
            </div>

            <!-- Quick In-Stock Catalog Grid -->
            <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Quick Select (In-Stock Items)</h2>
                    <span class="text-xs text-slate-400">Click to add to sale</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <?php foreach ($quick_products as $qp): ?>
                        <?php
                        $price = (float)($qp['variant_price'] ?? ($qp['sale_price'] > 0 ? $qp['sale_price'] : $qp['base_price']));
                        $item_payload = htmlspecialchars(json_encode([
                            'id'           => (int)$qp['id'],
                            'variant_id'   => !empty($qp['variant_id']) ? (int)$qp['variant_id'] : null,
                            'name'         => $qp['name'],
                            'variant_name' => $qp['variant_name'] ?? null,
                            'sku'          => $qp['variant_sku'] ?: $qp['sku'],
                            'price'        => $price,
                            'stock'        => (int)$qp['stock'],
                        ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <button type="button" onclick="addToCart(<?= $item_payload ?>)"
                                class="p-3 rounded-xl border border-slate-200 bg-white hover:border-emerald-500 hover:shadow-md transition text-left flex flex-col justify-between group">
                            <div>
                                <p class="text-xs font-bold text-slate-900 line-clamp-2 group-hover:text-emerald-700 transition">
                                    <?= e($qp['name']) ?>
                                </p>
                                <?php if (!empty($qp['variant_name'])): ?>
                                    <span class="text-[10px] text-blue-600 font-semibold block truncate"><?= e($qp['variant_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-3 flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="font-black text-xs text-slate-900"><?= format_currency($price) ?></span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold bg-slate-100 text-slate-600">
                                    <?= (int)$qp['stock'] ?> left
                                </span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Current Sale Cart & Checkout Panel (5 cols) -->
        <div class="lg:col-span-5">
            <form method="POST" action="<?= url('/admin/pos.php') ?>" id="posForm" class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="cart_data" id="cartDataInput" value="[]">

                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shopping-basket" class="w-4 h-4 text-emerald-600"></i>
                        <h2 class="text-sm font-bold text-slate-900">Current Sale Cart</h2>
                    </div>
                    <button type="button" onclick="clearCart()" class="text-xs text-rose-600 hover:text-rose-800 font-bold">
                        Clear Cart
                    </button>
                </div>

                <!-- Cart Items Container -->
                <div id="cartItemsList" class="space-y-2 max-h-72 overflow-y-auto pr-1">
                    <p id="emptyCartMessage" class="text-center py-10 text-xs text-slate-400">
                        <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto mb-1.5 opacity-30"></i>
                        Cart is currently empty. Scan or select an item.
                    </p>
                </div>

                <!-- Customer Details -->
                <div class="pt-3 border-t border-slate-100 grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Customer Name</label>
                        <input type="text" name="customer_name" value="Walk-in Customer"
                               class="w-full px-3 py-1.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Customer Phone</label>
                        <input type="tel" name="customer_phone" placeholder="Mobile number"
                               class="w-full px-3 py-1.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Totals Breakdown -->
                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Items Subtotal:</span>
                        <span id="posSubtotal" class="font-bold text-slate-800">₹0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-slate-500">
                        <span>Discount (₹):</span>
                        <input type="number" name="discount_amount" id="posDiscount" min="0" step="0.01" value="0.00" oninput="renderCartSummary()"
                               class="w-24 px-2 py-1 text-right rounded-lg border border-slate-200 text-xs font-bold text-emerald-600">
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>GST Tax (18% Incl.):</span>
                        <span id="posTax" class="font-bold text-slate-800">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-200">
                        <span>Grand Total:</span>
                        <span id="posGrandTotal" class="text-emerald-600 text-lg">₹0.00</span>
                    </div>
                </div>

                <!-- Payment Tender Controls -->
                <div class="pt-3 border-t border-slate-100 space-y-3">
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="cash" checked class="peer sr-only">
                            <div class="p-2 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 text-xs font-bold peer-checked:text-emerald-700 transition">
                                <i data-lucide="banknote" class="w-4 h-4 mx-auto mb-0.5"></i>
                                Cash
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="upi" class="peer sr-only">
                            <div class="p-2 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 text-xs font-bold peer-checked:text-emerald-700 transition">
                                <i data-lucide="qr-code" class="w-4 h-4 mx-auto mb-0.5"></i>
                                UPI / QR
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="card" class="peer sr-only">
                            <div class="p-2 text-center rounded-xl border border-slate-200 peer-checked:border-emerald-600 peer-checked:bg-emerald-50 text-xs font-bold peer-checked:text-emerald-700 transition">
                                <i data-lucide="credit-card" class="w-4 h-4 mx-auto mb-0.5"></i>
                                Card
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Cash Received</label>
                            <input type="number" name="amount_paid" id="posAmountPaid" min="0" step="0.01" placeholder="0.00" oninput="calculateChange()"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 font-bold focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Change Return</label>
                            <div id="posChangeDue" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 font-black text-slate-800 text-sm">
                                ₹0.00
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="posSubmitBtn" disabled
                        class="w-full py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-black text-sm shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                    <span>Complete Sale &amp; Print Receipt</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let cart = [];
let searchTimer = null;

const searchInput = document.getElementById('posSearchInput');
const dropdown = document.getElementById('searchResultsDropdown');
const spinner = document.getElementById('searchSpinner');

// Search catalog or handle barcode scanner
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 1) {
        dropdown.classList.add('hidden');
        return;
    }

    spinner.classList.remove('hidden');
    searchTimer = setTimeout(() => {
        fetch(`<?= url('/admin/pos.php') ?>?action=search&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('hidden');
                if (data.success && data.products.length > 0) {
                    renderSearchResults(data.products);
                } else {
                    dropdown.innerHTML = `<div class="p-4 text-xs text-center text-slate-400">No matching products found.</div>`;
                    dropdown.classList.remove('hidden');
                }
            })
            .catch(() => spinner.classList.add('hidden'));
    }, 250);
});

// Barcode scanner Enter key
searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const q = this.value.trim();
        if (!q) return;

        fetch(`<?= url('/admin/pos.php') ?>?action=search&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.products.length === 1) {
                    addToCart(data.products[0]);
                    searchInput.value = '';
                    dropdown.classList.add('hidden');
                }
            });
    }
});

function renderSearchResults(products) {
    dropdown.innerHTML = '';
    products.forEach(p => {
        const row = document.createElement('div');
        row.className = 'p-3 hover:bg-slate-50 cursor-pointer flex items-center justify-between transition';
        row.innerHTML = `
            <div>
                <p class="text-xs font-bold text-slate-900">${escapeHtml(p.name)} ${p.variant_name ? '(' + escapeHtml(p.variant_name) + ')' : ''}</p>
                <p class="text-[10px] text-slate-400 font-mono">SKU: ${escapeHtml(p.sku)} &bull; ${p.stock} in stock</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-black text-emerald-700">₹${p.price.toFixed(2)}</p>
                <span class="text-[10px] font-bold text-blue-600">+ Add</span>
            </div>
        `;
        row.onclick = () => {
            addToCart(p);
            dropdown.classList.add('hidden');
            searchInput.value = '';
            searchInput.focus();
        };
        dropdown.appendChild(row);
    });
    dropdown.classList.remove('hidden');
}

function addToCart(product) {
    if (product.stock <= 0) {
        alert('This product is currently out of stock.');
        return;
    }

    const key = `${product.id}-${product.variant_id || 0}`;
    const existing = cart.find(i => `${i.id}-${i.variant_id || 0}` === key);

    if (existing) {
        if (existing.quantity >= product.stock) {
            alert(`Cannot add more. Only ${product.stock} units available in stock.`);
            return;
        }
        existing.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            variant_id: product.variant_id,
            name: product.name,
            variant_name: product.variant_name,
            sku: product.sku,
            price: product.price,
            stock: product.stock,
            quantity: 1
        });
    }

    renderCart();
}

function updateQty(key, delta) {
    const item = cart.find(i => `${i.id}-${i.variant_id || 0}` === key);
    if (!item) return;

    const newQty = item.quantity + delta;
    if (newQty <= 0) {
        cart = cart.filter(i => `${i.id}-${i.variant_id || 0}` !== key);
    } else if (newQty > item.stock) {
        alert(`Only ${item.stock} units available in stock.`);
    } else {
        item.quantity = newQty;
    }
    renderCart();
}

function removeFromCart(key) {
    cart = cart.filter(i => `${i.id}-${i.variant_id || 0}` !== key);
    renderCart();
}

function clearCart() {
    cart = [];
    renderCart();
}

function renderCart() {
    const list = document.getElementById('cartItemsList');
    const emptyMsg = document.getElementById('emptyCartMessage');
    const submitBtn = document.getElementById('posSubmitBtn');

    if (cart.length === 0) {
        list.innerHTML = '';
        list.appendChild(emptyMsg);
        emptyMsg.classList.remove('hidden');
        submitBtn.disabled = true;
        renderCartSummary();
        return;
    }

    emptyMsg.classList.add('hidden');
    submitBtn.disabled = false;
    list.innerHTML = '';

    cart.forEach(item => {
        const key = `${item.id}-${item.variant_id || 0}`;
        const row = document.createElement('div');
        row.className = 'p-2.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3 text-xs';
        row.innerHTML = `
            <div class="min-w-0 flex-1">
                <p class="font-bold text-slate-900 truncate">${escapeHtml(item.name)}</p>
                ${item.variant_name ? `<span class="text-[10px] text-blue-600 block">${escapeHtml(item.variant_name)}</span>` : ''}
                <span class="text-[10px] text-slate-400 font-mono">₹${item.price.toFixed(2)} each</span>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button" onclick="updateQty('${key}', -1)" class="w-6 h-6 rounded-lg bg-white border border-slate-200 font-bold hover:bg-slate-100 flex items-center justify-center">-</button>
                <span class="w-6 text-center font-bold text-slate-900">${item.quantity}</span>
                <button type="button" onclick="updateQty('${key}', 1)" class="w-6 h-6 rounded-lg bg-white border border-slate-200 font-bold hover:bg-slate-100 flex items-center justify-center">+</button>
            </div>
            <div class="text-right shrink-0">
                <p class="font-bold text-slate-900">₹${(item.quantity * item.price).toFixed(2)}</p>
                <button type="button" onclick="removeFromCart('${key}')" class="text-[10px] text-rose-500 hover:text-rose-700">Remove</button>
            </div>
        `;
        list.appendChild(row);
    });

    renderCartSummary();
}

function renderCartSummary() {
    let subtotal = 0;
    cart.forEach(i => subtotal += (i.quantity * i.price));

    const discount = parseFloat(document.getElementById('posDiscount').value) || 0;
    const grandTotal = Math.max(0, subtotal - discount);
    const tax = grandTotal * (18 / 118);

    const fmt = v => '₹' + v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('posSubtotal').textContent = fmt(subtotal);
    document.getElementById('posTax').textContent = fmt(tax);
    document.getElementById('posGrandTotal').textContent = fmt(grandTotal);

    document.getElementById('cartDataInput').value = JSON.stringify(cart);
    calculateChange();
}

function calculateChange() {
    let subtotal = 0;
    cart.forEach(i => subtotal += (i.quantity * i.price));
    const discount = parseFloat(document.getElementById('posDiscount').value) || 0;
    const grandTotal = Math.max(0, subtotal - discount);

    const paid = parseFloat(document.getElementById('posAmountPaid').value) || 0;
    const change = Math.max(0, paid - grandTotal);
    document.getElementById('posChangeDue').textContent = '₹' + change.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

// Close dropdown on click outside
document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && e.target !== searchInput) {
        dropdown.classList.add('hidden');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

