<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart-functions.php';
require_once __DIR__ . '/includes/payment-functions.php';

session_init();
require_auth();

$cart = cart_get_details();
if (empty($cart['items'])) {
    set_flash('warning', 'Your shopping cart is empty.');
    redirect('/cart.php');
}

$userId = auth_id();
$addresses = db_fetch_all("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC", [$userId]);
$user = auth_user();
$razorpayKey = defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '';
$razorpayConfigured = razorpay_is_configured();

$pageTitle = 'Secure Checkout';
$robotsMeta = 'noindex, nofollow';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="border-b border-slate-200 pb-5">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Checkout</h1>
        <p class="text-xs text-slate-500 mt-0.5">Select delivery address and payment method to complete your purchase</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Column: Address & Payment -->
        <div class="lg:col-span-7 space-y-8">
            <!-- 1. Delivery Address Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-extrabold text-xs flex items-center justify-center">1</div>
                    <h2 class="font-extrabold text-base text-slate-900">Shipping Delivery Address</h2>
                </div>

                <div class="space-y-4">
                    <?php if (!empty($addresses)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($addresses as $idx => $addr): ?>
                                <label class="p-4 rounded-2xl border border-slate-200 hover:border-blue-500 cursor-pointer transition flex flex-col justify-between">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?> class="text-blue-600">
                                                <span class="font-bold text-xs text-slate-900"><?= e($addr['full_name']) ?></span>
                                            </div>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 bg-indigo-100 text-blue-700 rounded">Default</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-[11px] text-slate-600 leading-relaxed pt-1">
                                            <?= e($addr['address_line1']) ?><br>
                                            <?= !empty($addr['address_line2']) ? e($addr['address_line2']) . '<br>' : '' ?>
                                            <?= e($addr['city']) ?>, <?= e($addr['state']) ?> - <?= e($addr['postal_code']) ?>
                                        </p>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium pt-2 border-t border-slate-100 mt-2 flex items-center gap-1">
                                        <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                                        <span><?= e($addr['phone']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="pt-2">
                        <button type="button" onclick="toggleNewAddressForm()" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1.5">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span><?= empty($addresses) ? 'Enter Delivery Address' : '+ Ship to a different address' ?></span>
                        </button>
                    </div>

                    <div id="newAddressSection" class="<?= empty($addresses) ? '' : 'hidden' ?> p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-4">
                        <input type="radio" name="address_id" value="new" id="addressNewRadio" class="hidden" <?= empty($addresses) ? 'checked' : '' ?>>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="new_name" class="block text-[11px] font-bold text-slate-700 mb-1">Recipient Name</label>
                                <input type="text" id="new_name" name="new_full_name" value="<?= e($user['full_name'] ?? '') ?>" placeholder="Full Name" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                            <div>
                                <label for="new_phone" class="block text-[11px] font-bold text-slate-700 mb-1">Contact Phone</label>
                                <input type="tel" id="new_phone" name="new_phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="10-digit Phone" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                        </div>

                        <div>
                            <label for="new_line1" class="block text-[11px] font-bold text-slate-700 mb-1">Flat / Building / House No.</label>
                            <input type="text" id="new_line1" name="new_address_line1" placeholder="e.g. Flat 302, Green Valley Apartments" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                        </div>

                        <div>
                            <label for="new_line2" class="block text-[11px] font-bold text-slate-700 mb-1">Street / Area / Landmark (Optional)</label>
                            <input type="text" id="new_line2" name="new_address_line2" placeholder="e.g. Near Metro Station" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label for="new_city" class="block text-[11px] font-bold text-slate-700 mb-1">City</label>
                                <input type="text" id="new_city" name="new_city" placeholder="City" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                            <div>
                                <label for="new_state" class="block text-[11px] font-bold text-slate-700 mb-1">State</label>
                                <input type="text" id="new_state" name="new_state" placeholder="State" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                            <div>
                                <label for="new_pin" class="block text-[11px] font-bold text-slate-700 mb-1">PIN Code</label>
                                <input type="text" id="new_pin" name="new_postal_code" placeholder="PIN" class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Payment Method Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                <div class="flex items-center gap-2.5 border-b border-slate-100 pb-4">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-extrabold text-xs flex items-center justify-center">2</div>
                    <h2 class="font-extrabold text-base text-slate-900">Select Payment Method</h2>
                </div>

                <div class="space-y-3">
                    <?php if ($razorpayConfigured): ?>
                    <label class="p-4 rounded-2xl border border-slate-200 hover:border-blue-500 cursor-pointer transition flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="payment_method" value="razorpay" checked class="text-blue-600">
                            <div>
                                <div class="font-bold text-xs text-slate-900 flex items-center gap-2">
                                    <span>Razorpay Secure Gateway</span>
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-100 text-blue-700 text-[9px] font-extrabold">Instant</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">UPI (GPay/PhonePe), Credit/Debit Cards, NetBanking, Cred & Wallets</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 text-slate-400">
                            <i data-lucide="shield-check" class="w-5 h-5 text-blue-600"></i>
                        </div>
                    </label>
                    <?php endif; ?>

                    <label class="p-4 rounded-2xl border border-slate-200 hover:border-blue-500 cursor-pointer transition flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="payment_method" value="cod" <?= !$razorpayConfigured ? 'checked' : '' ?> class="text-blue-600">
                            <div>
                                <div class="font-bold text-xs text-slate-900">Cash on Delivery (COD)</div>
                                <p class="text-[11px] text-slate-500 mt-0.5">Pay via cash or UPI to courier upon delivery</p>
                            </div>
                        </div>
                        <i data-lucide="banknote" class="w-5 h-5 text-slate-400"></i>
                    </label>
                </div>

                <div class="pt-2">
                    <label for="orderNotes" class="block text-[11px] font-bold text-slate-700 mb-1">Delivery Instructions / Notes (Optional)</label>
                    <textarea id="orderNotes" rows="2" placeholder="e.g. Leave package with apartment security guard..." class="w-full text-xs p-2.5 rounded-xl border border-slate-200"></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column: Cart Review & Pay -->
        <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
            <h3 class="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-4">Review Order (<?= $cart['total_items'] ?> Items)</h3>

            <div class="space-y-3 max-h-60 overflow-y-auto scrollbar-thin">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 text-slate-400">
                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-900 truncate"><?= e($item['product_name']) ?></div>
                            <div class="text-[11px] text-slate-400"><?= $item['quantity'] ?> &times; <?= format_currency($item['unit_price']) ?></div>
                        </div>
                        <div class="font-extrabold text-slate-900"><?= format_currency($item['item_total']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="border-t border-slate-100 pt-4 space-y-2.5 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span>
                    <span class="font-bold text-slate-900"><?= format_currency($cart['subtotal']) ?></span>
                </div>
                <?php if ($cart['discount_amount'] > 0): ?>
                    <div class="flex justify-between text-emerald-600 font-bold">
                        <span>Promo Coupon Discount</span>
                        <span>-<?= format_currency($cart['discount_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between text-slate-600">
                    <span>GST (18% Integrated)</span>
                    <span class="font-bold text-slate-900"><?= format_currency($cart['tax_amount']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Shipping Delivery</span>
                    <span class="font-bold text-slate-900"><?= $cart['shipping_fee'] > 0 ? format_currency($cart['shipping_fee']) : '<span class="text-emerald-600 font-bold">FREE</span>' ?></span>
                </div>
                <div class="border-t border-slate-200 pt-3 flex justify-between items-baseline">
                    <span class="text-sm font-extrabold text-slate-900">Total Payable</span>
                    <span class="text-2xl font-black text-blue-600"><?= format_currency($cart['grand_total']) ?></span>
                </div>
            </div>

            <button id="payButton" onclick="initiateCheckout()" class="w-full py-4 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-xl shadow-blue-500/25 transition flex items-center justify-center gap-2">
                <i data-lucide="lock" class="w-4 h-4"></i>
                <span>Confirm &amp; Place Order (<?= format_currency($cart['grand_total']) ?>)</span>
            </button>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
function toggleNewAddressForm() {
    const sec = document.getElementById('newAddressSection');
    const radio = document.getElementById('addressNewRadio');
    sec.classList.toggle('hidden');
    if (!sec.classList.contains('hidden')) {
        radio.checked = true;
    }
}

function getSelectedAddressId() {
    const checked = document.querySelector('input[name="address_id"]:checked');
    return checked ? checked.value : null;
}

async function initiateCheckout() {
    const methodEl = document.querySelector('input[name="payment_method"]:checked');
    const method = methodEl ? methodEl.value : 'cod';
    const addressId = getSelectedAddressId();
    const notes = document.getElementById('orderNotes').value;

    if (!addressId) {
        alert('Please select or add a delivery address.');
        return;
    }

    if (method === 'cod') {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('/actions/checkout-process.php') ?>';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_csrf_token';
        csrf.value = '<?= csrf_token() ?>';
        form.appendChild(csrf);

        const addrInput = document.createElement('input');
        addrInput.type = 'hidden';
        addrInput.name = 'address_id';
        addrInput.value = addressId;
        form.appendChild(addrInput);

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = 'payment_method';
        methodInput.value = 'cod';
        form.appendChild(methodInput);

        const notesInput = document.createElement('input');
        notesInput.type = 'hidden';
        notesInput.name = 'notes';
        notesInput.value = notes;
        form.appendChild(notesInput);

        if (addressId === 'new') {
            const fields = {
                full_name: 'new_name',
                phone: 'new_phone',
                address_line1: 'new_line1',
                address_line2: 'new_line2',
                city: 'new_city',
                state: 'new_state',
                postal_code: 'new_pin'
            };
            for (const [key, id] of Object.entries(fields)) {
                const el = document.getElementById(id);
                if (el) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = key;
                    inp.value = el.value;
                    form.appendChild(inp);
                }
            }
        }

        document.body.appendChild(form);
        form.submit();
        return;
    }

    // Razorpay flow
    const orderForm = new FormData();
    orderForm.append('address_id', addressId);
    orderForm.append('notes', notes);
    orderForm.append('_csrf_token', '<?= csrf_token() ?>');
    if (addressId === 'new') {
        const fields = {
            new_full_name: 'new_name',
            new_phone: 'new_phone',
            new_address_line1: 'new_line1',
            new_address_line2: 'new_line2',
            new_city: 'new_city',
            new_state: 'new_state',
            new_postal_code: 'new_pin'
        };
        for (const [key, id] of Object.entries(fields)) {
            const el = document.getElementById(id);
            if (el) orderForm.append(key, el.value);
        }
    }

    const orderResponse = await fetch('<?= url('/actions/checkout-process.php?action=create_razorpay_order') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: orderForm
    });
    const orderData = await orderResponse.json();
    if (!orderResponse.ok || !orderData.status) {
        alert(orderData.message || 'Could not initiate online payment.');
        return;
    }

    const options = {
        key: '<?= e($razorpayKey) ?>',
        amount: orderData.order.amount,
        currency: orderData.order.currency,
        name: '<?= e(setting('site_name', 'Apex Mobile Hub')) ?>',
        description: 'Order Payment - <?= $cart['total_items'] ?> Items',
        order_id: orderData.order.id,
        handler: async function (response) {
            await verifyPaymentWithServer(response);
        },
        prefill: {
            name: '<?= e($user['full_name'] ?? '') ?>',
            email: '<?= e($user['email'] ?? '') ?>',
            contact: '<?= e($user['phone'] ?? '') ?>'
        },
        theme: {
            color: '#2563EB'
        }
    };

    const rzp = new Razorpay(options);
    rzp.on('payment.failed', function (resp) {
        alert('Payment failed: ' + resp.error.description);
    });
    rzp.open();
}

async function verifyPaymentWithServer(response) {
    try {
        const formData = new FormData();
        formData.append('razorpay_order_id', response.razorpay_order_id);
        formData.append('razorpay_payment_id', response.razorpay_payment_id);
        formData.append('razorpay_signature', response.razorpay_signature);
        formData.append('_csrf_token', '<?= csrf_token() ?>');

        const res = await fetch('<?= url('/payment-verify.php') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const data = await res.json();
        if (data.status && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            alert(data.message || 'Payment verification failed.');
        }
    } catch (err) {
        console.error(err);
        alert('An unexpected error occurred verifying your payment.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

