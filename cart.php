<?php
$pageTitle = 'Shopping Cart';
$robotsMeta = 'noindex, nofollow';
require_once __DIR__ . '/includes/header.php';

$cart = cart_get_details();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex items-center justify-between border-b border-slate-200 pb-5">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Shopping Cart</h1>
            <p class="text-xs text-slate-500 mt-0.5"><?= $cart['total_items'] ?> items currently in your cart</p>
        </div>
        <a href="<?= url('/shop.php') ?>" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Continue Shopping</span>
        </a>
    </div>

    <?php if (empty($cart['items'])): ?>
        <div class="bg-white rounded-3xl border border-slate-200 p-16 text-center text-slate-400 space-y-4 max-w-lg mx-auto">
            <div class="w-20 h-20 rounded-3xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto">
                <i data-lucide="shopping-cart" class="w-10 h-10"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800">Your shopping cart is empty</h2>
            <p class="text-xs text-slate-500 max-w-sm mx-auto leading-relaxed">
                Explore our collection of flagship smartphones, wireless audio, fast GaN chargers and accessories.
            </p>
            <div class="pt-2">
                <a href="<?= url('/shop.php') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-lg shadow-blue-500/25 transition">
                    <span>Browse Products</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Items Table -->
            <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs" aria-label="Shopping Cart Items">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-600 font-bold uppercase tracking-wider pb-3">
                                <th scope="col" class="pb-3">Product</th>
                                <th scope="col" class="pb-3">Unit Price</th>
                                <th scope="col" class="pb-3 text-center">Quantity</th>
                                <th scope="col" class="pb-3 text-right">Subtotal</th>
                                <th scope="col" class="pb-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($cart['items'] as $item): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-4 pr-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center text-blue-600 shrink-0" aria-hidden="true">
                                                <i data-lucide="smartphone" class="w-6 h-6"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <a href="<?= url('/product.php?slug=' . $item['product_slug']) ?>" class="font-bold text-slate-900 hover:text-blue-600 transition block truncate focus-visible:ring-2 focus-visible:ring-blue-600">
                                                    <?= e($item['product_name']) ?>
                                                </a>
                                                <?php if (!empty($item['variant_name'])): ?>
                                                    <span class="text-[11px] font-semibold text-blue-600"><?= e($item['variant_name']) ?></span>
                                                <?php endif; ?>
                                                <span class="block text-[10px] text-slate-500 font-mono">SKU: <?= e($item['variant_sku'] ?? $item['sku']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 font-bold text-slate-800">
                                        <?= format_currency($item['unit_price']) ?>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="inline-flex items-center border border-slate-200 rounded-xl p-0.5 bg-white">
                                            <button type="button" onclick="updateCartItem(<?= (int)$item['item_id'] ?>, <?= (int)$item['quantity'] - 1 ?>)" aria-label="Decrease quantity of <?= e($item['product_name']) ?>" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded-lg focus-visible:ring-2 focus-visible:ring-blue-600">
                                                <i data-lucide="minus" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                            </button>
                                            <span class="text-xs font-bold text-slate-900 px-2" aria-label="Quantity <?= $item['quantity'] ?>"><?= $item['quantity'] ?></span>
                                            <button type="button" onclick="updateCartItem(<?= (int)$item['item_id'] ?>, <?= (int)$item['quantity'] + 1 ?>)" aria-label="Increase quantity of <?= e($item['product_name']) ?>" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded-lg focus-visible:ring-2 focus-visible:ring-blue-600">
                                                <i data-lucide="plus" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-4 text-right font-extrabold text-slate-900 text-sm">
                                        <?= format_currency($item['item_total']) ?>
                                    </td>
                                    <td class="py-4 pl-4 text-right">
                                        <button type="button" onclick="updateCartItem(<?= (int)$item['item_id'] ?>, 0)" class="text-slate-400 hover:text-rose-600 transition p-1 rounded focus-visible:ring-2 focus-visible:ring-rose-500" aria-label="Remove <?= e($item['product_name']) ?> from shopping cart">
                                            <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Promo Coupon Form -->
                <div class="border-t border-slate-100 pt-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <form action="<?= url('/actions/cart-coupon.php') ?>" method="POST" class="flex items-center gap-2 max-w-sm w-full">
                        <?= csrf_field() ?>
                        <div class="relative flex-1">
                            <label for="cart_coupon_code" class="sr-only">Coupon code</label>
                            <input type="text" id="cart_coupon_code" name="coupon_code" placeholder="Coupon code (e.g. WELCOME10)" required class="w-full pl-9 pr-3 py-2 text-xs font-mono uppercase tracking-wider rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" aria-hidden="true"></i>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs transition focus-visible:ring-2 focus-visible:ring-slate-900">
                            Apply
                        </button>
                    </form>

                    <?php if (!empty($cart['coupon'])): ?>
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold" role="status">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600" aria-hidden="true"></i>
                            <span>Coupon '<?= e($cart['coupon']['code']) ?>' Applied</span>
                            <form action="<?= url('/actions/cart-coupon.php?action=remove') ?>" method="POST" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" aria-label="Remove applied coupon <?= e($cart['coupon']['code']) ?>" class="text-emerald-500 hover:text-rose-600 ml-1 p-0.5 rounded focus-visible:ring-2 focus-visible:ring-rose-500">
                                    <i data-lucide="x" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary Card -->
            <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs space-y-6">
                <h3 class="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-4">Order Summary</h3>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal</span>
                        <span class="font-bold text-slate-900"><?= format_currency($cart['subtotal']) ?></span>
                    </div>

                    <?php if ($cart['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-emerald-600 font-bold">
                            <span>Coupon Discount</span>
                            <span>-<?= format_currency($cart['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between text-slate-600">
                        <span>GST Tax (18% Integrated)</span>
                        <span class="font-bold text-slate-900"><?= format_currency($cart['tax_amount']) ?></span>
                    </div>

                    <div class="flex justify-between text-slate-600">
                        <span>Shipping Delivery</span>
                        <span class="font-bold text-slate-900"><?= $cart['shipping_fee'] > 0 ? format_currency($cart['shipping_fee']) : '<span class="text-emerald-600 font-bold">FREE</span>' ?></span>
                    </div>

                    <div class="border-t border-slate-200 pt-4 flex justify-between items-baseline">
                        <span class="text-sm font-extrabold text-slate-900">Total Payable</span>
                        <span class="text-2xl font-black text-blue-600"><?= format_currency($cart['grand_total']) ?></span>
                    </div>
                </div>

                <a href="<?= url('/checkout.php') ?>" class="w-full py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-xl shadow-blue-500/25 transition flex items-center justify-center gap-2">
                    <span>Proceed to Secure Checkout</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>

                <div class="pt-2 text-[11px] text-slate-400 space-y-2 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                        <span>100% Genuine Certified Devices</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="truck" class="w-4 h-4 text-blue-600"></i>
                        <span>Express Dispatched in 24 Hours</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-500"></i>
                        <span>Encrypted Checkout via Razorpay</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

