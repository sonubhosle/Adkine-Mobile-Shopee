    </main>

    <!-- Slide-over Shopping Cart Drawer (WCAG 2.2 Dialog & Focus Management) -->
    <div id="cartDrawerBackdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 transition-opacity duration-300 opacity-0 pointer-events-none" onclick="toggleCartDrawer(false)" aria-hidden="true"></div>
    <div id="cartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartDrawerHeading"
         class="fixed right-0 top-0 bottom-0 w-full max-w-md bg-white shadow-2xl z-50 flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out">
        <!-- Drawer Header -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i data-lucide="shopping-bag" class="w-5 h-5 text-blue-600" aria-hidden="true"></i>
                <h2 id="cartDrawerHeading" class="font-bold text-slate-900 text-base">Shopping Cart</h2>
                <span id="drawerBadge" class="px-2 py-0.5 rounded-full bg-indigo-100 text-blue-700 text-xs font-bold" aria-label="<?= $cartPreview['total_items'] ?> items"><?= $cartPreview['total_items'] ?></span>
            </div>
            <button id="cartDrawerCloseBtn" onclick="toggleCartDrawer(false)" aria-label="Close cart drawer" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition focus-visible:ring-2 focus-visible:ring-blue-600">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Drawer Items List with iOS/Android Smooth Touch Scrolling -->
        <div id="drawerItemsContainer" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 touch-scroll overscroll-contain" role="region" aria-label="Cart items list">
            <?php if (empty($cartPreview['items'])): ?>
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4" aria-hidden="true">
                        <i data-lucide="shopping-cart" class="w-8 h-8"></i>
                    </div>
                    <p class="font-bold text-slate-700 text-base mb-1">Your cart is currently empty</p>
                    <p class="text-xs text-slate-500 mb-6">Explore our premium smartphones and gadget accessories to add items.</p>
                    <a href="<?= url('/shop.php') ?>" onclick="toggleCartDrawer(false)" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition focus-visible:ring-2 focus-visible:ring-blue-600">
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($cartPreview['items'] as $cItem): ?>
                    <div class="flex gap-4 p-3 rounded-2xl border border-slate-100 hover:border-slate-200 transition bg-white shadow-xs">
                        <div class="w-16 h-16 rounded-xl bg-slate-100 p-2 shrink-0 flex items-center justify-center" aria-hidden="true">
                            <i data-lucide="smartphone" class="w-8 h-8 text-slate-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-xs text-slate-900 truncate"><?= e($cItem['product_name']) ?></h4>
                            <?php if ($cItem['variant_name']): ?>
                                <p class="text-[11px] text-blue-600 font-semibold"><?= e($cItem['variant_name']) ?></p>
                            <?php endif; ?>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-900"><?= format_currency($cItem['unit_price']) ?></span>
                                <div class="flex items-center gap-2 border border-slate-200 rounded-lg p-0.5">
                                    <button onclick="updateCartItem(<?= (int)$cItem['item_id'] ?>, <?= (int)$cItem['quantity'] - 1 ?>)" aria-label="Decrease quantity of <?= e($cItem['product_name']) ?>" class="w-6 h-6 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded focus-visible:ring-2 focus-visible:ring-blue-600">
                                        <i data-lucide="minus" class="w-3 h-3" aria-hidden="true"></i>
                                    </button>
                                    <span class="text-xs font-bold text-slate-800 px-1" aria-label="Quantity <?= $cItem['quantity'] ?>"><?= $cItem['quantity'] ?></span>
                                    <button onclick="updateCartItem(<?= (int)$cItem['item_id'] ?>, <?= (int)$cItem['quantity'] + 1 ?>)" aria-label="Increase quantity of <?= e($cItem['product_name']) ?>" class="w-6 h-6 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded focus-visible:ring-2 focus-visible:ring-blue-600">
                                        <i data-lucide="plus" class="w-3 h-3" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button onclick="updateCartItem(<?= (int)$cItem['item_id'] ?>, 0)" aria-label="Remove <?= e($cItem['product_name']) ?> from cart" class="text-slate-300 hover:text-rose-500 transition self-start p-1 focus-visible:ring-2 focus-visible:ring-rose-500 rounded">
                            <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Drawer Footer Checkout -->
        <?php if (!empty($cartPreview['items'])): ?>
            <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50 space-y-3">
                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-500">
                        <span>Subtotal</span>
                        <span class="font-semibold text-slate-800"><?= format_currency($cartPreview['subtotal']) ?></span>
                    </div>
                    <?php if ($cartPreview['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-emerald-600">
                            <span>Discount</span>
                            <span class="font-semibold">-<?= format_currency($cartPreview['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-slate-500">
                        <span>GST (18%)</span>
                        <span class="font-semibold text-slate-800"><?= format_currency($cartPreview['tax_amount']) ?></span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Shipping</span>
                        <span class="font-semibold text-slate-800"><?= $cartPreview['shipping_fee'] > 0 ? format_currency($cartPreview['shipping_fee']) : 'FREE' ?></span>
                    </div>
                    <div class="border-t border-slate-200 pt-2 flex justify-between text-sm font-extrabold text-navy-900">
                        <span>Grand Total</span>
                        <span class="text-blue-600"><?= format_currency($cartPreview['grand_total']) ?></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="<?= url('/cart.php') ?>" class="w-full py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 font-bold text-xs text-slate-700 text-center transition">
                        View Full Cart
                    </a>
                    <a href="<?= url('/checkout.php') ?>" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs text-center shadow-lg shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                        <span>Checkout</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Storefront Footer -->
    <footer class="bg-navy-950 text-slate-400 text-xs border-t border-slate-800 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12">
                
                <!-- Col 1: Store Bio -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center text-white shadow-md shadow-indigo-600/30 ring-1 ring-white/20 shrink-0">
                            <svg class="w-5 h-5" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="6.5" y="2.5" width="19" height="27" rx="3.8" stroke="white" stroke-width="2"/>
                                <path d="M16 8L10.5 22H13.6L14.7 19H17.3L18.4 22H21.5L16 8Z" fill="white"/>
                                <path d="M16 11.5L15.1 16.5H16.9L16 11.5Z" fill="#1e1b4b"/>
                                <path d="M16.8 13.5L13.8 19H17L15 25L21 18H17.5L19.2 13.5H16.8Z" fill="#facc15"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 leading-none">
                                <span class="text-lg font-extrabold text-white tracking-tight">ADKINE <span class="text-blue-400">MOBILE</span></span>
                                <span class="px-1.5 py-0.5 rounded bg-amber-400/20 text-amber-300 text-[9px] font-black uppercase">SHOPEE</span>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 block mt-1">Smartphones • Certified Repairs • Gadgets</span>
                        </div>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed max-w-sm">
                        Premier electronics retail showroom, certified smartphone chip-level repair lab, and unified multi-channel omnichannel inventory ERP system.
                    </p>
                    <div class="text-[11px] space-y-1 text-slate-400">
                        <div><strong class="text-white">Store Address:</strong> <?= e(setting('store_address', 'Electronics City Tech Park, Bengaluru')) ?></div>
                        <div><strong class="text-white">Customer Support:</strong> <?= e(setting('site_phone', '+91 98765 43210')) ?></div>
                        <div><strong class="text-white">GSTIN:</strong> <?= e(setting('store_gstin', '29AABCA1234A1Z5')) ?></div>
                    </div>
                </div>

                <!-- Col 2: Categories -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Categories</h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/shop.php?category=smartphones') ?>" class="hover:text-white transition">Smartphones</a></li>
                        <li><a href="<?= url('/shop.php?category=chargers-adapters') ?>" class="hover:text-white transition">Fast Chargers & GaN</a></li>
                        <li><a href="<?= url('/shop.php?category=earphones-audio') ?>" class="hover:text-white transition">TWS & Headphones</a></li>
                        <li><a href="<?= url('/shop.php?category=smartwatches') ?>" class="hover:text-white transition">Smartwatches</a></li>
                        <li><a href="<?= url('/shop.php?category=mobile-covers') ?>" class="hover:text-white transition">Cases & Protectors</a></li>
                        <li><a href="<?= url('/shop.php?category=spare-parts') ?>" class="hover:text-white transition">OEM Spare Parts</a></li>
                    </ul>
                </div>

                <!-- Col 3: Repair Services -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Repair Lab</h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/repair-book.php') ?>" class="hover:text-white transition">Book Device Repair</a></li>
                        <li><a href="<?= url('/repair-track.php') ?>" class="hover:text-white transition">Track Repair Ticket</a></li>
                        <li><a href="<?= url('/repair-book.php?issue=screen_damage') ?>" class="hover:text-white transition">OLED Screen Replacement</a></li>
                        <li><a href="<?= url('/repair-book.php?issue=battery_replacement') ?>" class="hover:text-white transition">Original Battery Replacement</a></li>
                        <li><a href="<?= url('/repair-book.php?issue=water_damage') ?>" class="hover:text-white transition">Motherboard Water Recovery</a></li>
                    </ul>
                </div>

                <!-- Col 4: ERP & Accounts -->
                <div>
                    <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">ERP & Accounts</h4>
                    <ul class="space-y-2.5">
                        <li><a href="<?= url('/login.php') ?>" class="hover:text-white transition">Customer Sign In</a></li>
                        <li><a href="<?= url('/register.php') ?>" class="hover:text-white transition">Create Account</a></li>
                        <li><a href="<?= url('/orders.php') ?>" class="hover:text-white transition">Order History & Invoices</a></li>
                        <li><a href="<?= url('/admin/index.php') ?>" class="text-blue-400 font-bold hover:underline">Admin ERP Terminal</a></li>
                        <li><a href="<?= url('/admin/pos.php') ?>" class="text-emerald-400 font-bold hover:underline">POS Cash Register</a></li>
                        <li><a href="<?= url('/technician/index.php') ?>" class="text-amber-400 font-bold hover:underline">Technician Workbench</a></li>
                    </ul>
                </div>
            </div>

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
    <script>
        let lastFocusedBeforeCartDrawer = null;
        let lastFocusedBeforeMobileNav = null;

        function toggleResponsiveNavbar(forceOpen = null) {
            const sidebar = document.getElementById('responsiveMobileNav');
            const backdrop = document.getElementById('mobileSidebarBackdrop');
            const icon = document.getElementById('navHamburgerIcon');
            const btn = document.getElementById('mobileNavToggleBtn');
            const closeBtn = document.getElementById('mobileSidebarCloseBtn');
            if (!sidebar || !backdrop) return;

            const isOpen = !sidebar.classList.contains('-translate-x-full');
            const shouldOpen = forceOpen !== null ? forceOpen : !isOpen;

            if (shouldOpen) {
                lastFocusedBeforeMobileNav = document.activeElement;
                backdrop.classList.remove('opacity-0', 'pointer-events-none');
                sidebar.classList.remove('-translate-x-full');
                document.body.style.overflow = 'hidden';
                if (icon) icon.setAttribute('data-lucide', 'x');
                if (btn) btn.setAttribute('aria-expanded', 'true');
                setTimeout(() => {
                    if (closeBtn) closeBtn.focus();
                }, 100);
            } else {
                backdrop.classList.add('opacity-0', 'pointer-events-none');
                sidebar.classList.add('-translate-x-full');
                document.body.style.overflow = '';
                if (icon) icon.setAttribute('data-lucide', 'menu');
                if (btn) btn.setAttribute('aria-expanded', 'false');
                if (lastFocusedBeforeMobileNav && typeof lastFocusedBeforeMobileNav.focus === 'function') {
                    lastFocusedBeforeMobileNav.focus();
                }
            }
            if (window.lucide) {
                lucide.createIcons();
            }
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
                if (mobileNav && !mobileNav.classList.contains('-translate-x-full')) {
                    toggleResponsiveNavbar(false);
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
