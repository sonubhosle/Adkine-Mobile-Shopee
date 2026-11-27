    </main>

    <!-- Slide-over Shopping Cart Drawer -->
    <div id="cartDrawerBackdrop" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs opacity-0 pointer-events-none transition-opacity duration-300" onclick="toggleCartDrawer(false)" aria-hidden="true"></div>
    <div id="cartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartDrawerHeading" class="fixed bottom-0 right-0 top-0 z-50 flex w-full max-w-md translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-in-out">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 p-4 sm:p-5">
            <div class="flex items-center gap-2"><i data-lucide="shopping-bag" class="h-5 w-5 text-blue-600" aria-hidden="true"></i><h2 id="cartDrawerHeading" class="text-base font-bold text-slate-900">Shopping Cart</h2><span id="drawerBadge" class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-blue-700" aria-label="<?= $cartPreview['total_items'] ?> items"><?= $cartPreview['total_items'] ?></span></div>
            <button id="cartDrawerCloseBtn" onclick="toggleCartDrawer(false)" aria-label="Close cart drawer" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:ring-2 focus-visible:ring-blue-600"><i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i></button>
        </div>
        <div id="drawerItemsContainer" class="touch-scroll flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:p-5" role="region" aria-label="Cart items list">
            <?php if (empty($cartPreview['items'])): ?>
                <div class="flex h-full flex-col items-center justify-center p-6 text-center text-slate-400"><div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100"><i data-lucide="shopping-cart" class="h-8 w-8" aria-hidden="true"></i></div><p class="mb-1 text-base font-bold text-slate-700">Your cart is currently empty</p><p class="mb-6 text-xs text-slate-500">Explore our premium smartphones and gadget accessories to add items.</p><a href="<?= url('/shop.php') ?>" onclick="toggleCartDrawer(false)" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white transition hover:bg-indigo-700 focus-visible:ring-2 focus-visible:ring-blue-600">Start Shopping</a></div>
            <?php else: ?>
                <?php foreach ($cartPreview['items'] as $cItem): ?>
                    <div class="flex gap-4 rounded-2xl border border-slate-100 bg-white p-3 shadow-xs transition hover:border-slate-200"><div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-100 p-2"><i data-lucide="smartphone" class="h-8 w-8 text-slate-400"></i></div><div class="min-w-0 flex-1"><h4 class="truncate text-xs font-bold text-slate-900"><?= e($cItem['product_name']) ?></h4><?php if ($cItem['variant_name']): ?><p class="text-[11px] font-semibold text-blue-600"><?= e($cItem['variant_name']) ?></p><?php endif; ?><div class="mt-2 flex items-center justify-between"><span class="text-xs font-bold text-slate-900"><?= format_currency($cItem['unit_price']) ?></span><div class="flex items-center gap-2 rounded-lg border border-slate-200 p-0.5"><button onclick="updateCartItem(<?= (int) $cItem['item_id'] ?>, <?= (int) $cItem['quantity'] - 1 ?>)" aria-label="Decrease quantity of <?= e($cItem['product_name']) ?>" class="flex h-6 w-6 items-center justify-center rounded text-slate-600 hover:bg-slate-100"><i data-lucide="minus" class="h-3 w-3"></i></button><span class="px-1 text-xs font-bold text-slate-800"><?= $cItem['quantity'] ?></span><button onclick="updateCartItem(<?= (int) $cItem['item_id'] ?>, <?= (int) $cItem['quantity'] + 1 ?>)" aria-label="Increase quantity of <?= e($cItem['product_name']) ?>" class="flex h-6 w-6 items-center justify-center rounded text-slate-600 hover:bg-slate-100"><i data-lucide="plus" class="h-3 w-3"></i></button></div></div></div><button onclick="updateCartItem(<?= (int) $cItem['item_id'] ?>, 0)" aria-label="Remove <?= e($cItem['product_name']) ?>" class="self-start rounded p-1 text-slate-300 transition hover:text-rose-500"><i data-lucide="trash-2" class="h-4 w-4"></i></button></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (!empty($cartPreview['items'])): ?>
            <div class="space-y-3 border-t border-slate-100 bg-slate-50 p-4 sm:p-5"><div class="space-y-1.5 text-xs"><div class="flex justify-between text-slate-500"><span>Subtotal</span><span class="font-semibold text-slate-800"><?= format_currency($cartPreview['subtotal']) ?></span></div><?php if ($cartPreview['discount_amount'] > 0): ?><div class="flex justify-between text-emerald-600"><span>Discount</span><span class="font-semibold">-<?= format_currency($cartPreview['discount_amount']) ?></span></div><?php endif; ?><div class="flex justify-between text-slate-500"><span>GST (18%)</span><span class="font-semibold text-slate-800"><?= format_currency($cartPreview['tax_amount']) ?></span></div><div class="flex justify-between text-slate-500"><span>Shipping</span><span class="font-semibold text-slate-800"><?= $cartPreview['shipping_fee'] > 0 ? format_currency($cartPreview['shipping_fee']) : 'FREE' ?></span></div><div class="flex justify-between border-t border-slate-200 pt-2 text-sm font-extrabold text-navy-900"><span>Grand Total</span><span class="text-blue-600"><?= format_currency($cartPreview['grand_total']) ?></span></div></div><div class="grid grid-cols-2 gap-2 pt-1"><a href="<?= url('/cart.php') ?>" class="w-full rounded-xl border border-slate-300 bg-white py-2.5 text-center text-xs font-bold text-slate-700 transition hover:bg-slate-100">View Full Cart</a><a href="<?= url('/checkout.php') ?>" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-indigo-600 py-2.5 text-center text-xs font-bold text-white shadow-lg shadow-blue-500/20 transition hover:bg-indigo-700"><span>Checkout</span><i data-lucide="arrow-right" class="h-3.5 w-3.5"></i></a></div></div>
        <?php endif; ?>
    </div>

    <!-- Storefront Footer -->
    <footer class="bg-navy-950 text-slate-400 text-xs border-t border-slate-800 mt-20">
        <div class="px-6 py-5">
            
            <!-- TOP ROW: 2 Columns (Left: Brand Identity & Store Info, Right: Newsletter & Social Media) -->
            <div class="grid grid-cols-1 pb-5 mb-5 md:grid-cols-2 gap-10 lg:gap-16 items-start pb-12 border-b border-slate-800/80">
                
                <!-- Left Column: Brand Identity & Store Info -->
                <div class="space-y-4">
                    <a href="<?= url('/index.php') ?>" class="flex items-center gap-2.5 group shrink-0 select-none">
                    <!-- High-Tech Smartphone + Lightning + 'A' Monogram Badge -->
                    <div class="relative w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-700 via-indigo-600 to-violet-700 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30 ring-1 ring-white/25 group-hover:scale-105 group-hover:shadow-indigo-600/50 transition-all duration-300 shrink-0 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-t from-transparent via-white/10 to-white/20 pointer-events-none"></div>
                        <svg class="w-6 h-6 relative z-10" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="6.5" y="2.5" width="19" height="27" rx="3.8" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <line x1="13" y1="5.5" x2="19" y2="5.5" stroke="white" stroke-width="1.6" stroke-linecap="round" opacity="0.8"/>
                            <path d="M16 8L10.5 22H13.6L14.7 19H17.3L18.4 22H21.5L16 8Z" fill="white"/>
                            <path d="M16 11.5L15.1 16.5H16.9L16 11.5Z" fill="#1e1b4b"/>
                            <path d="M16.8 13.5L13.8 19H17L15 25L21 18H17.5L19.2 13.5H16.8Z" fill="#facc15" filter="drop-shadow(0 1px 2px rgba(0,0,0,0.35))"/>
                            <circle cx="16" cy="26.5" r="1.1" fill="white" opacity="0.85"/>
                        </svg>
                    </div>

                    <!-- Brand Name & Identity -->
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1 leading-none">
                            <span class="text-lg font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600   transition-colors">
                               New Adkine
                            </span>
                            
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[0.12em] text-slate-400 mt-0.5">
                            <span class="text-sm sm:text-base font-black tracking-tight text-slate-200">
                                Mobile
                            </span>
                            <span class="inline-flex items-center px-1 py-0.5 rounded bg-gradient-to-r from-amber-500/15 to-orange-500/15 border border-amber-500/30 text-amber-700 text-[8px] font-black tracking-wider uppercase">
                               Shopee
                            </span>
                        </div>
                    </div>
                </a>

                    <p class="text-slate-400 text-xs leading-relaxed max-w-lg">
                        Premier electronics retail showroom, certified smartphone chip-level repair lab, and unified multi-channel omnichannel inventory ERP system.
                    </p>
                    <div class="text-[11px] space-y-1.5 text-slate-400 pt-1">
                        <div class="flex items-start gap-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-blue-400 shrink-0 mt-0.5"></i>
                            <span><strong class="text-white">Store Address:</strong> <?= e(setting('store_address', 'Showroom 101, Electronics City Tech Park, Bengaluru, Karnataka 560100')) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="phone" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                            <span><strong class="text-white">Customer Support:</strong> <?= e(setting('site_phone', '+91 98765 43210')) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-amber-400 shrink-0"></i>
                            <span><strong class="text-white">GSTIN:</strong> <?= e(setting('store_gstin', '29AABCA1234A1Z5')) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Newsletter & Social Media Icons -->
                <div class="space-y-6">
                    <div>
                        <h4 class="font-bold text-white text-sm uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 text-blue-400"></i>
                            Stay Updated
                        </h4>
                        <p class="mt-1.5 text-xs text-slate-400">
                            Get product launches, repair offers, and store updates.
                        </p>
                        <form action="<?= url('/subscribe.php') ?>" method="post" class="mt-3.5 flex flex-col sm:flex-row gap-2 max-w-lg">
                            <label for="footer-email" class="sr-only">Email address</label>
                            <div class="relative flex-1">
                                <input type="email" id="footer-email" name="email" required placeholder="Enter your email address" class="w-full rounded-xl border border-slate-700 bg-slate-900/90 px-4 py-2.5 text-xs text-white placeholder:text-slate-500 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-400/20">
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-blue-500/20 transition hover:from-blue-500 hover:to-indigo-500 focus-visible:ring-2 focus-visible:ring-blue-500 shrink-0">
                                <span>Subscribe</span>
                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Social Icons -->
                    <div class="pt-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 block mb-2.5">Follow Us &amp; Connect</span>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <a href="#" aria-label="Instagram" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-pink-500/50 hover:bg-pink-500/10 transition-all flex items-center justify-center group" title="Instagram">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                            <a href="#" aria-label="Facebook" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-blue-500/50 hover:bg-blue-500/10 transition-all flex items-center justify-center group" title="Facebook">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" aria-label="Twitter / X" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-sky-500/50 hover:bg-sky-500/10 transition-all flex items-center justify-center group" title="X (Twitter)">
                                <svg class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </a>
                            <a href="#" aria-label="WhatsApp" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-emerald-500/50 hover:bg-emerald-500/10 transition-all flex items-center justify-center group" title="WhatsApp">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </a>
                            <a href="#" aria-label="YouTube" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:border-red-500/50 hover:bg-red-500/10 transition-all flex items-center justify-center group" title="YouTube">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BOTTOM ROW: 4-Column Responsive Grid (Categories, Repair Lab, Customer Service, ERP & Accounts) -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 lg:gap-10 pt-12">

                <!-- Col 1: Categories -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5 text-blue-400"></i>
                        Categories
                    </h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/shop.php?category=smartphones') ?>" class="hover:text-white transition">Smartphones</a></li>
                        <li><a href="<?= url('/shop.php?category=chargers-adapters') ?>" class="hover:text-white transition">Fast Chargers &amp; GaN</a></li>
                        <li><a href="<?= url('/shop.php?category=earphones-audio') ?>" class="hover:text-white transition">TWS &amp; Headphones</a></li>
                        <li><a href="<?= url('/shop.php?category=smartwatches') ?>" class="hover:text-white transition">Smartwatches</a></li>
                        <li><a href="<?= url('/shop.php?category=mobile-covers') ?>" class="hover:text-white transition">Cases &amp; Protectors</a></li>
                        <li><a href="<?= url('/shop.php?category=spare-parts') ?>" class="hover:text-white transition">OEM Spare Parts</a></li>
                    </ul>
                </div>

                <!-- Col 2: Repair Lab -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="wrench" class="w-3.5 h-3.5 text-emerald-400"></i>
                        Repair Lab
                    </h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/repair-book.php') ?>" class="hover:text-white transition">Book Device Repair</a></li>
                        <li><a href="<?= url('/repair-track.php') ?>" class="hover:text-white transition">Track Repair Ticket</a></li>
                        <li><a href="<?= url('/repair-book.php?issue=screen_damage') ?>" class="hover:text-white transition">OLED Screen Replacement</a></li>
                        <li><a href="<?= url('/repair-book.php?issue=battery_replacement') ?>" class="hover:text-white transition">Original Battery Replacement</a></li>
                        <li><a href="<?= url('/repair-book.php?issue=water_damage') ?>" class="hover:text-white transition">Motherboard Water Recovery</a></li>
                    </ul>
                </div>

                <!-- Col 3: Customer Service -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="headphones" class="w-3.5 h-3.5 text-indigo-400"></i>
                        Customer Service
                    </h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/contact.php') ?>" class="hover:text-white transition">Contact Us</a></li>
                        <li><a href="<?= url('/faq.php') ?>" class="hover:text-white transition">FAQs</a></li>
                        <li><a href="<?= url('/shipping.php') ?>" class="hover:text-white transition">Shipping Policy</a></li>
                        <li><a href="<?= url('/returns.php') ?>" class="hover:text-white transition">Returns &amp; Warranty</a></li>
                        <li><a href="<?= url('/track-order.php') ?>" class="hover:text-white transition">Order Tracking</a></li>
                    </ul>
                </div>

                <!-- Col 4: ERP & Accounts -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i data-lucide="shield" class="w-3.5 h-3.5 text-amber-400"></i>
                        ERP &amp; Accounts
                    </h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/login.php') ?>" class="hover:text-white transition">Customer Sign In</a></li>
                        <li><a href="<?= url('/register.php') ?>" class="hover:text-white transition">Create Account</a></li>
                        <li><a href="<?= url('/orders.php') ?>" class="hover:text-white transition">Order History &amp; Invoices</a></li>
                        <li><a href="<?= url('/admin/index.php') ?>" class="text-blue-400 font-bold hover:underline">Admin ERP Terminal</a></li>
                        <li><a href="<?= url('/admin/pos.php') ?>" class="text-emerald-400 font-bold hover:underline">POS Cash Register</a></li>
                        <li><a href="<?= url('/technician/index.php') ?>" class="text-amber-400 font-bold hover:underline">Technician Workbench</a></li>
                    </ul>
                </div>
            </div>

            <!-- COPYRIGHT & SECURITY BAR -->
            <div class="border-t border-slate-900 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-slate-400 text-[11px]">
                    &copy; <?= date('Y') ?> <?= e(setting('site_name', 'Apex Mobile Hub & ERP')) ?>. All rights reserved. Plain Core PHP &amp; MySQLi.
                </p>
                <div class="flex items-center gap-3 text-slate-400">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Secured with Razorpay &amp; 256-Bit SSL</span>
                    <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 font-mono text-[10px] text-slate-300">UPI</span>
                    <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 font-mono text-[10px] text-slate-300">CARDS</span>
                    <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 font-mono text-[10px] text-slate-300">NETBANKING</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Responsive Navigation, Cart Drawer & WCAG Focus Trap Scripts -->
    <?php require __DIR__ . '/language-script.php'; ?>
    <script>
        let lastFocusedBeforeCartDrawer = null;

        function toggleResponsiveNavbar() {
            const nav = document.getElementById('responsiveMobileNav');
            const icon = document.getElementById('navHamburgerIcon');
            const btn = document.getElementById('mobileNavToggleBtn');
            if (!nav) return;
            const isHidden = nav.classList.contains('hidden');
            if (isHidden) {
                nav.classList.remove('hidden');
                nav.classList.add('animate-nav-down');
                if (icon) icon.setAttribute('data-lucide', 'x');
                if (btn) btn.setAttribute('aria-expanded', 'true');
            } else {
                nav.classList.add('hidden');
                nav.classList.remove('animate-nav-down');
                if (icon) icon.setAttribute('data-lucide', 'menu');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
            lucide.createIcons();
        }

        function toggleCartDrawer(open) {
            const drawer = document.getElementById('cartDrawer');
            const backdrop = document.getElementById('cartDrawerBackdrop');
            const trigger = document.getElementById('cartHeaderTrigger');
            const closeBtn = document.getElementById('cartDrawerCloseBtn');
            if (!drawer || !backdrop) return;

            if (open) {
                lastFocusedBeforeCartDrawer = document.activeElement;
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                drawer.classList.remove('translate-x-full');
                document.body.style.overflow = 'hidden';
                if (trigger) trigger.setAttribute('aria-expanded', 'true');
                setTimeout(() => {
                    if (closeBtn) closeBtn.focus();
                }, 100);
            } else {
                backdrop.classList.add('opacity-0', 'pointer-events-none');
                drawer.classList.add('translate-x-full');
                document.body.style.overflow = '';
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
                if (lastFocusedBeforeCartDrawer && typeof lastFocusedBeforeCartDrawer.focus === 'function') {
                    lastFocusedBeforeCartDrawer.focus();
                }
            }
        }

        // WCAG 2.2 Modal Focus Trap for Cart Drawer
        document.addEventListener('keydown', function(e) {
            const drawer = document.getElementById('cartDrawer');
            if (!drawer || drawer.classList.contains('translate-x-full')) return;

            if (e.key === 'Tab') {
                const focusable = drawer.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }
        });

        // Global Escape Key Listener for Modals, Drawers & Dropdowns
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                // 1. Close Cart Drawer
                const cartDrawer = document.getElementById('cartDrawer');
                if (cartDrawer && !cartDrawer.classList.contains('translate-x-full')) {
                    toggleCartDrawer(false);
                    return;
                }

                // 2. Close Search Suggestions Dropdown
                const dBox = document.getElementById('desktopSearchSuggestions');
                if (dBox && !dBox.classList.contains('pointer-events-none')) {
                    dBox.classList.add('opacity-0', '-translate-y-2', 'pointer-events-none', 'scale-95');
                    dBox.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto', 'scale-100');
                    const dInput = document.getElementById('desktopStoreSearch');
                    if (dInput) dInput.focus();
                    return;
                }
                const mBox = document.getElementById('mobileSearchSuggestions');
                if (mBox && !mBox.classList.contains('pointer-events-none')) {
                    mBox.classList.add('opacity-0', '-translate-y-2', 'pointer-events-none', 'scale-95');
                    mBox.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto', 'scale-100');
                    const mInput = document.getElementById('responsiveStoreSearch');
                    if (mInput) mInput.focus();
                    return;
                }

                // 3. Close Mobile Navigation Menu
                const mobileNav = document.getElementById('responsiveMobileNav');
                if (mobileNav && !mobileNav.classList.contains('hidden')) {
                    toggleResponsiveNavbar();
                    const navBtn = document.getElementById('mobileNavToggleBtn');
                    if (navBtn) navBtn.focus();
                    return;
                }
            }
        });

        // WCAG 2.2 SC 2.2.2 Pause/Resume Marquee Animation
        function toggleMarqueeAnimation() {
            const track = document.getElementById('topMarqueeTrack');
            const btn = document.getElementById('marqueeToggleBtn');
            const icon = document.getElementById('marqueeToggleIcon');
            if (!track || !btn) return;

            const isPaused = track.classList.toggle('top-marquee-paused');
            btn.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
            btn.setAttribute('aria-label', isPaused ? 'Resume announcement ticker' : 'Pause announcement ticker');
            if (icon) {
                icon.setAttribute('data-lucide', isPaused ? 'play' : 'pause');
                lucide.createIcons();
            }
        }

        async function addToCart(productId, variantId = null, qty = 1) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                if (variantId) formData.append('variant_id', variantId);
                formData.append('quantity', qty);
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const res = await fetch('<?= url('/actions/cart-add.php') ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.status) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Could not add item to cart.');
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred adding product to cart.');
            }
        }

        async function updateCartItem(itemId, qty) {
            try {
                const formData = new FormData();
                formData.append('item_id', itemId);
                formData.append('quantity', qty);
                formData.append('_csrf_token', '<?= csrf_token() ?>');

                const res = await fetch('<?= url('/actions/cart-update.php') ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.status) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Could not update cart.');
                }
            } catch (err) {
                console.error(err);
            }
        }

        lucide.createIcons();

        // -------------------------------------------------------------
        // Live Enhanced Product Search with Smooth Suggestion Dropdown
        // -------------------------------------------------------------
        function initLiveSearch(inputId, boxId, clearBtnId, formId) {
            const input = document.getElementById(inputId);
            const box = document.getElementById(boxId);
            const clearBtn = document.getElementById(clearBtnId);
            const form = document.getElementById(formId);
            if (!input || !box) return;

            let debounceTimer = null;
            let currentController = null;
            let activeIndex = -1;
            const searchEndpoint = '<?= url('/actions/product-search.php') ?>';
            const shopAllUrl = '<?= url('/shop.php') ?>';

            function openBox() {
                box.classList.remove('opacity-0', '-translate-y-2', 'pointer-events-none', 'scale-95');
                box.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto', 'scale-100');
                input.setAttribute('aria-expanded', 'true');
            }

            function closeBox() {
                box.classList.add('opacity-0', '-translate-y-2', 'pointer-events-none', 'scale-95');
                box.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto', 'scale-100');
                input.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function highlightMatch(text, query) {
                if (!query) return escapeHtml(text);
                const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp(`(${escaped})`, 'gi');
                return escapeHtml(text).replace(regex, '<mark class="bg-blue-100 text-blue-900 font-black px-0.5 rounded">$1</mark>');
            }

            function renderLoading(q) {
                box.innerHTML = `
                    <div class="p-3 space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-wider text-slate-400 px-1">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="loader-2" class="w-3.5 h-3.5 text-blue-600 animate-spin"></i>
                                <span>Searching for &ldquo;${escapeHtml(q)}&rdquo;...</span>
                            </span>
                        </div>
                        <div class="space-y-1.5 animate-pulse">
                            <div class="h-11 bg-slate-100 rounded-xl"></div>
                            <div class="h-11 bg-slate-100 rounded-xl"></div>
                            <div class="h-11 bg-slate-100 rounded-xl"></div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
                openBox();
            }

            function renderResults(data, q) {
                if (!data.products || data.products.length === 0) {
                    box.innerHTML = `
                        <div class="p-5 text-center">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                                <i data-lucide="search-x" class="w-5 h-5"></i>
                            </div>
                            <div class="text-xs font-black text-slate-800">No matching products found</div>
                            <p class="text-[11px] text-slate-400 mt-0.5">We could not find anything matching &ldquo;${escapeHtml(q)}&rdquo;</p>
                            <a href="${shopAllUrl}" class="inline-flex items-center gap-1 text-xs font-extrabold text-blue-600 hover:text-blue-700 mt-2.5 transition">
                                <span>Browse full catalogue</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    `;
                } else {
                    const itemsHtml = data.products.map((item, idx) => {
                        let stockBadge = '';
                        if (item.stock_status === 'in_stock') {
                            stockBadge = `<span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>In Stock</span>`;
                        } else if (item.stock_status === 'low_stock') {
                            stockBadge = `<span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Only ${item.stock} left</span>`;
                        } else {
                            stockBadge = `<span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded-md">Sold Out</span>`;
                        }

                        const imageMarkup = item.image_url
                            ? `<img src="${item.image_url}" alt="${escapeHtml(item.name)}" class="w-full h-full object-contain">`
                            : `<i data-lucide="smartphone" class="w-4 h-4 text-blue-600"></i>`;

                        const discountMarkup = item.has_discount
                            ? `<div class="flex items-center justify-end gap-1"><span class="text-[10px] text-slate-400 line-through">${item.formatted_base_price}</span><span class="text-[9px] font-black text-emerald-600 bg-emerald-50 px-1 rounded">-${item.discount_percent}%</span></div>`
                            : '';

                        return `
                            <a href="${item.url}" role="option" aria-selected="false" data-index="${idx}"
                                class="search-suggestion-item group flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 focus:bg-indigo-50/80 transition-all duration-150">
                                <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200/60 p-1 flex items-center justify-center shrink-0 overflow-hidden">
                                    ${imageMarkup}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-900 group-hover:text-blue-600 truncate transition-colors">
                                        ${highlightMatch(item.name, q)}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-500">
                                        <span class="font-bold text-slate-600">${escapeHtml(item.brand_name || 'Brand')}</span>
                                        <span>•</span>
                                        <span class="truncate">${escapeHtml(item.category_name || 'Accessories')}</span>
                                        <span>•</span>
                                        ${stockBadge}
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs font-black text-slate-900">${item.formatted_price}</div>
                                    ${discountMarkup}
                                </div>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300 group-hover:text-blue-600 group-hover:translate-x-0.5 transition-all shrink-0"></i>
                            </a>
                        `;
                    }).join('');

                    box.innerHTML = `
                        <div class="flex items-center justify-between px-2.5 py-1.5 border-b border-slate-100 text-[10px] font-black uppercase tracking-wider text-slate-400">
                            <span class="flex items-center gap-1 text-slate-500">
                                <i data-lucide="sparkles" class="w-3 h-3 text-blue-600"></i>
                                <span>Available Products (${data.total})</span>
                            </span>
                            <span class="text-slate-400 lowercase font-medium hidden sm:inline">use ↑ ↓ to navigate</span>
                        </div>
                        <div class="max-h-80 overflow-y-auto py-1 space-y-0.5" role="listbox">
                            ${itemsHtml}
                        </div>
                        <div class="pt-2 pb-0.5 px-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                            <a href="${data.all_url}" class="font-extrabold text-blue-600 hover:text-blue-700 flex items-center gap-1 transition">
                                <span>View all results for &ldquo;${escapeHtml(q)}&rdquo;</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                            <span class="text-[10px] text-slate-400 font-semibold hidden sm:inline">↵ to view all</span>
                        </div>
                    `;
                }

                lucide.createIcons();
                openBox();
            }

            async function performSearch(q) {
                if (q.length < 2) {
                    closeBox();
                    return;
                }

                if (currentController) {
                    currentController.abort();
                }
                currentController = new AbortController();

                renderLoading(q);

                try {
                    const res = await fetch(`${searchEndpoint}?q=${encodeURIComponent(q)}`, {
                        signal: currentController.signal,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('Search failed');
                    const data = await res.json();
                    renderResults(data, q);
                } catch (err) {
                    if (err.name === 'AbortError') return;
                    console.error('Search error:', err);
                    box.innerHTML = `
                        <div class="p-4 text-center text-xs text-slate-500">
                            Unable to load live suggestions. Press Enter to browse results.
                        </div>
                    `;
                    openBox();
                }
            }

            // Input typing listener with debounce
            input.addEventListener('input', function() {
                const val = input.value.trim();
                if (clearBtn) {
                    if (val.length > 0) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                }

                clearTimeout(debounceTimer);
                if (val.length < 2) {
                    closeBox();
                    return;
                }
                debounceTimer = setTimeout(() => {
                    performSearch(val);
                }, 200);
            });

            // Focus in listener
            input.addEventListener('focus', function() {
                const val = input.value.trim();
                if (val.length >= 2) {
                    performSearch(val);
                }
            });

            // Clear button click listener
            if (clearBtn) {
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    input.value = '';
                    clearBtn.classList.add('hidden');
                    closeBox();
                    input.focus();
                });
            }

            // Keyboard navigation in search suggestions
            input.addEventListener('keydown', function(e) {
                const items = box.querySelectorAll('.search-suggestion-item');
                if (!items.length || box.classList.contains('pointer-events-none')) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = (activeIndex + 1) % items.length;
                    updateActiveItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = (activeIndex - 1 + items.length) % items.length;
                    updateActiveItem(items);
                } else if (e.key === 'Enter') {
                    if (activeIndex >= 0 && items[activeIndex]) {
                        e.preventDefault();
                        window.location.href = items[activeIndex].href;
                    }
                } else if (e.key === 'Escape' || e.key === 'Esc') {
                    e.preventDefault();
                    closeBox();
                }
            });

            function updateActiveItem(items) {
                items.forEach((item, idx) => {
                    if (idx === activeIndex) {
                        item.classList.add('bg-blue-50/90', 'text-blue-600', 'ring-1', 'ring-blue-500/20');
                        item.setAttribute('aria-selected', 'true');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('bg-blue-50/90', 'text-blue-600', 'ring-1', 'ring-blue-500/20');
                        item.setAttribute('aria-selected', 'false');
                    }
                });
            }

            // Click outside closes suggestion box
            document.addEventListener('click', function(e) {
                if (!box.contains(e.target) && !input.contains(e.target)) {
                    closeBox();
                }
            });
        }

        // Initialize Desktop & Mobile Live Search
        initLiveSearch('desktopStoreSearch', 'desktopSearchSuggestions', 'desktopSearchClear', 'desktopSearchForm');
        initLiveSearch('responsiveStoreSearch', 'mobileSearchSuggestions', 'mobileSearchClear', 'mobileSearchForm');

        // Global Shortcut: Ctrl+K / Cmd+K or "/" to focus search
        document.addEventListener('keydown', function(e) {
            const isEditing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                const targetInput = window.innerWidth >= 768
                    ? document.getElementById('desktopStoreSearch')
                    : document.getElementById('responsiveStoreSearch');
                if (targetInput) {
                    targetInput.focus();
                    targetInput.select();
                }
            } else if (e.key === '/' && !isEditing) {
                e.preventDefault();
                const targetInput = window.innerWidth >= 768
                    ? document.getElementById('desktopStoreSearch')
                    : document.getElementById('responsiveStoreSearch');
                if (targetInput) {
                    targetInput.focus();
                    targetInput.select();
                }
            }
        });

        lucide.createIcons();
    </script>
</body>
</html>
