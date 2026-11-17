<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Flagship Smartphones, Audio & Certified Repairs';
$metaDescription = 'Explore premier flagship smartphones, original OEM accessories, GaN fast chargers, and certified 45-minute repair services at Adkine Mobile Shopee. Fast delivery & warranty across India.';
$canonicalUrl = absolute_url('/index.php');
require_once __DIR__ . '/includes/header.php';

// 1. Fetch Featured Products
$sqlFeatured = "SELECT p.*, c.name as category_name, b.name as brand_name,
                       COALESCE(i.current_stock, 0) as stock
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN inventory i ON (p.id = i.product_id AND i.variant_id IS NULL)
                WHERE p.is_active = 1 AND p.is_featured = 1
                ORDER BY p.id DESC
                LIMIT 8";
$featuredProducts = db_fetch_all($sqlFeatured);

// 2. Fetch Categories with Live Product Count
$categories = db_fetch_all(
    "SELECT c.*, COUNT(p.id) AS product_count 
     FROM categories c 
     LEFT JOIN products p ON (c.id = p.category_id AND p.is_active = 1) 
     WHERE c.is_active = 1 
     GROUP BY c.id 
     ORDER BY c.display_order ASC"
);

// Category Theme Mapping (Distinct Line Icons, Accent Colors & Ambient Glow)
$categoryThemeMap = [
    'smartphones' => [
        'icon' => 'smartphone',
        'bg' => 'bg-blue-50 text-blue-600 border-blue-200/60 group-hover:bg-blue-600 group-hover:text-white',
        'accent' => 'from-blue-600/10 via-indigo-600/5 to-transparent',
    ],
    'chargers-adapters' => [
        'icon' => 'zap',
        'bg' => 'bg-amber-50 text-amber-600 border-amber-200/60 group-hover:bg-amber-500 group-hover:text-white',
        'accent' => 'from-amber-600/10 via-orange-600/5 to-transparent',
    ],
    'earphones-audio' => [
        'icon' => 'headphones',
        'bg' => 'bg-violet-50 text-violet-600 border-violet-200/60 group-hover:bg-violet-600 group-hover:text-white',
        'accent' => 'from-violet-600/10 via-purple-600/5 to-transparent',
    ],
    'bluetooth-devices' => [
        'icon' => 'volume-2',
        'bg' => 'bg-cyan-50 text-cyan-600 border-cyan-200/60 group-hover:bg-cyan-600 group-hover:text-white',
        'accent' => 'from-cyan-600/10 via-blue-600/5 to-transparent',
    ],
    'smartwatches' => [
        'icon' => 'watch',
        'bg' => 'bg-rose-50 text-rose-600 border-rose-200/60 group-hover:bg-rose-600 group-hover:text-white',
        'accent' => 'from-rose-600/10 via-pink-600/5 to-transparent',
    ],
    'mobile-covers' => [
        'icon' => 'shield',
        'bg' => 'bg-emerald-50 text-emerald-600 border-emerald-200/60 group-hover:bg-emerald-600 group-hover:text-white',
        'accent' => 'from-emerald-600/10 via-teal-600/5 to-transparent',
    ],
    'tempered-glass' => [
        'icon' => 'sparkles',
        'bg' => 'bg-sky-50 text-sky-600 border-sky-200/60 group-hover:bg-sky-600 group-hover:text-white',
        'accent' => 'from-sky-600/10 via-indigo-600/5 to-transparent',
    ],
    'batteries-powerbanks' => [
        'icon' => 'battery-charging',
        'bg' => 'bg-amber-50 text-amber-700 border-amber-300/60 group-hover:bg-amber-600 group-hover:text-white',
        'accent' => 'from-amber-600/10 via-yellow-600/5 to-transparent',
    ],
    'spare-parts' => [
        'icon' => 'cpu',
        'bg' => 'bg-indigo-50 text-indigo-600 border-indigo-200/60 group-hover:bg-indigo-600 group-hover:text-white',
        'accent' => 'from-indigo-600/10 via-blue-600/5 to-transparent',
    ],
];

// 3. Fetch Brands
$brands = db_fetch_all(
    "SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC"
);

// 4. Fetch New Accessories Arrivals
$sqlNewArrivals = "SELECT p.*, c.name as category_name, b.name as brand_name
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   LEFT JOIN brands b ON p.brand_id = b.id
                   WHERE p.is_active = 1 AND p.category_id != 1
                   ORDER BY p.id DESC
                   LIMIT 4";
$newArrivals = db_fetch_all($sqlNewArrivals);
?>

<?php require_once __DIR__ . '/includes/hero.php'; ?>

<!-- Enhanced Product Categories / Browse by Category -->
<section class="py-14 sm:py-16 bg-slate-50 border-b border-slate-200/80">
    <div class="px-4 sm:px-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-3 mb-8">
            <div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200/60 text-blue-700 text-[11px] font-extrabold uppercase tracking-wider mb-2">
                    <i data-lucide="layout-grid" class="w-3.5 h-3.5 text-blue-600"></i>
                    <span>Product Categories</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Browse by Category</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl">
                    Discover verified smartphones, OEM charging gear, studio audio, and technician-grade spare parts.
                </p>
            </div>
            <a href="<?= url('/shop.php') ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white hover:bg-slate-50 border border-slate-200/90 text-slate-800 hover:text-blue-600 font-bold text-xs shadow-2xs hover:shadow-xs transition-all duration-200 group shrink-0 focus-visible:ring-2 focus-visible:ring-blue-600">
                <span>View All Products</span>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all"></i>
            </a>
        </div>

        <!-- 5 Category Cards Per Row: Mobile (Image on Top, Label Middle, Button Below) | Desktop (Horizontal) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-3.5">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('/shop.php?category=' . $cat['slug']) ?>" 
                   class="group bg-white rounded-2xl border border-slate-200/80 hover:border-blue-400/80 shadow-2xs hover:shadow-lg hover:shadow-blue-500/10 p-3 sm:p-3.5 transition-all duration-300 flex flex-col sm:flex-row items-center sm:items-center text-center sm:text-left gap-2.5 sm:gap-3 hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-blue-600 overflow-hidden">
                    
                    <!-- Category Image: On Top in Mobile, Left in Desktop -->
                    <div class="w-16 h-16 sm:w-14 sm:h-14 lg:w-16 lg:h-16 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center p-1.5 shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/uploads/categories/' . $cat['image'])): ?>
                            <img src="<?= url('/uploads/categories/' . $cat['image']) ?>" alt="<?= e($cat['name']) ?>" loading="lazy" decoding="async" class="w-full h-full object-cover rounded-lg">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 rounded-lg">
                                <i data-lucide="layout-grid" class="w-6 h-6"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Name (Label) & Below Button -->
                    <div class="min-w-0 flex-1 flex flex-col items-center sm:items-start justify-center w-full">
                        <h3 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-blue-600 transition-colors line-clamp-1 sm:truncate text-center sm:text-left w-full" title="<?= e($cat['name']) ?>">
                            <?= e($cat['name']) ?>
                        </h3>
                        <span class="inline-flex items-center justify-center gap-1 mt-2 sm:mt-1.5 px-3 sm:px-2.5 py-1.5 sm:py-1 rounded-xl sm:rounded-lg bg-slate-900 group-hover:bg-blue-600 text-white font-bold text-[10px] sm:text-[11px] w-full sm:w-fit transition-colors shadow-2xs">
                            <span>Explore</span>
                            <i data-lucide="arrow-right" class="w-3 h-3 transition-transform duration-200 group-hover:translate-x-0.5" aria-hidden="true"></i>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Showcase -->
<section class="py-14 bg-white border-t border-slate-200">
    <div class="px-6">
        <div class="flex justify-between items-end mb-8">
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600">Handpicked Deals</span>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Featured Electronics</h2>
            </div>
            <a href="<?= url('/shop.php') ?>" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                <span>See More</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featuredProducts as $prod): ?>
                <div class="group bg-white rounded-3xl border border-slate-200/80 hover:border-slate-300 hover:shadow-xl hover:shadow-slate-200/50 transition flex flex-col p-5 relative">
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold uppercase">
                            <?= e($prod['brand_name'] ?? 'Brand') ?>
                        </span>
                        <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['base_price']): ?>
                            <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-600 text-[10px] font-extrabold">
                                SALE
                            </span>
                        <?php endif; ?>
                    </div>

                    <a href="<?= url('/product.php?slug=' . $prod['slug']) ?>" class="w-full h-48 rounded-2xl bg-slate-50 flex items-center justify-center p-4 group-hover:scale-102 transition overflow-hidden">
                        <?php if (!empty($prod['thumbnail']) && file_exists(__DIR__ . '/uploads/products/' . $prod['thumbnail'])): ?>
                            <img src="<?= url('/uploads/products/' . $prod['thumbnail']) ?>" alt="<?= e($prod['name']) ?> - Buy Online at <?= e(setting('site_name', 'Adkine Mobile Shopee')) ?>" loading="lazy" decoding="async" class="max-h-40 max-w-full object-contain">
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-2xl bg-white shadow-xs border border-slate-100 flex items-center justify-center text-blue-600">
                                <i data-lucide="smartphone" class="w-10 h-10"></i>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="mt-4 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="text-[11px] text-slate-400 font-medium"><?= e($prod['category_name'] ?? '') ?></div>
                            <h3 class="font-bold text-sm text-slate-900 group-hover:text-blue-600 transition mt-0.5 line-clamp-1">
                                <a href="<?= url('/product.php?slug=' . $prod['slug']) ?>">
                                    <?= e($prod['name']) ?>
                                </a>
                            </h3>
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                                <?= e($prod['short_desc'] ?? '') ?>
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <div class="text-base font-extrabold text-slate-900">
                                    <?= format_currency($prod['sale_price'] ?? $prod['base_price']) ?>
                                </div>
                                <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['base_price']): ?>
                                    <div class="text-[11px] text-slate-400 line-through">
                                        <?= format_currency($prod['base_price']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($prod['has_variants'])): ?>
                                <a href="<?= url('/product.php?slug=' . $prod['slug']) ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                                    Options
                                </a>
                            <?php else: ?>
                                <button onclick="addToCart(<?= (int)$prod['id'] ?>)" class="p-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition" title="Add to Cart">
                                    <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Repair CTA Banner: Certified Technicians & 90-Day Repair Warranty Showcase -->
<section class="relative bg-slate-950 text-white overflow-hidden py-16 sm:py-20 border-y border-slate-800/80">
    <!-- Ambient Tech Glow Backdrops -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -right-32 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-emerald-600/15 rounded-full blur-3xl"></div>
        <!-- High-tech subtle dot-grid mask -->
        <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-25"></div>
    </div>

    <div class="relative  px-4 sm:px-6 lg:px-8">
        <!-- Top Dual Trust Pill Badge -->
        <div class="flex flex-wrap items-center gap-2.5 mb-6">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-emerald-500/15 to-teal-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold tracking-wide backdrop-blur-md shadow-xs shadow-emerald-950">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Certified Technicians</span>
                <span class="text-emerald-500/60">•</span>
                <span>ESD-Safe Cleanroom Lab</span>
            </div>

            <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-blue-500/15 border border-blue-500/30 text-blue-400 text-xs font-bold tracking-wide backdrop-blur-md shadow-xs shadow-blue-950">
                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-blue-400"></i>
                <span>90-Day Repair Warranty</span>
            </div>

            <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-800/80 border border-slate-700/80 text-slate-300 text-xs font-medium">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>45-Min Express Turnaround</span>
            </div>
        </div>

        <!-- Main 2-Column Showcase Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            
            <!-- Left Column: Compelling Typography & CTAs -->
            <div class="lg:col-span-7 space-y-6">
                <div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-[1.15]">
                        Cracked Screen or <br class="hidden sm:block">
                        <span class="bg-gradient-to-r from-blue-400 via-sky-300 to-emerald-300 bg-clip-text text-transparent">
                            Draining Battery?
                        </span>
                    </h2>
                    <p class="mt-4 text-slate-300 text-sm sm:text-base leading-relaxed max-w-xl">
                        Restore your smartphone to factory precision with 100% genuine OEM components, robotic laser re-sealing, and rigorous 24-point diagnostic testing. Walk into our showroom lab, request doorstep courier pickup, or book an appointment online.
                    </p>
                </div>

                <!-- 4 Trust Bullet Pills -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 max-w-lg pt-1">
                    <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-blue-500/15 text-blue-400 shrink-0">
                            <i data-lucide="cpu" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">100% OEM Spares</div>
                            <div class="text-[11px] text-slate-400">Direct factory supply</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-emerald-500/15 text-emerald-400 shrink-0">
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">90-Day Warranty</div>
                            <div class="text-[11px] text-slate-400">Zero-hassle replacement</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-amber-500/15 text-amber-400 shrink-0">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">45-Min Express</div>
                            <div class="text-[11px] text-slate-400">While-you-wait repairs</div>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 backdrop-blur-sm">
                        <div class="p-1.5 rounded-lg bg-purple-500/15 text-purple-400 shrink-0">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">Data Privacy Safe</div>
                            <div class="text-[11px] text-slate-400">Zero data wipe needed</div>
                        </div>
                    </div>
                </div>

                <!-- CTA Actions & Direct Contact -->
                <div class="flex flex-wrap items-center gap-3.5 pt-2">
                    <a href="<?= url('/repair-book.php') ?>" class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black text-xs sm:text-sm shadow-xl shadow-blue-600/30 hover:shadow-blue-600/50 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Book Repair Ticket</span>
                    </a>

                    <a href="<?= url('/repair-track.php') ?>" class="inline-flex items-center gap-2.5 px-5 py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white font-bold text-xs sm:text-sm border border-slate-700/80 shadow-md hover:border-slate-600 transition-all duration-200">
                        <i data-lucide="search" class="w-4 h-4 text-blue-400"></i>
                        <span>Track Ticket Status</span>
                    </a>

                    <?php if ($sitePhone = setting('site_phone')): ?>
                        <a href="tel:<?= preg_replace('/\s+/', '', $sitePhone) ?>" class="inline-flex items-center gap-2 px-3.5 py-3 rounded-xl text-slate-400 hover:text-amber-400 text-xs font-semibold hover:bg-slate-900/50 transition">
                            <i data-lucide="phone-call" class="w-4 h-4 text-amber-400"></i>
                            <span>Call Lab: <?= e($sitePhone) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Interactive Diagnostic & Popular Repair Matrix Card -->
            <div class="lg:col-span-5">
                <div class="relative rounded-3xl bg-slate-900/90 border border-slate-700/80 p-5 sm:p-6 shadow-2xl shadow-black/60 backdrop-blur-xl overflow-hidden">
                    <!-- Subtle top glow inside the card -->
                    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-64 h-32 bg-blue-500/20 blur-2xl pointer-events-none rounded-full"></div>

                    <!-- Card Header / Diagnostic Live Bar -->
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-blue-400">
                                <i data-lucide="wrench" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <h3 class="text-xs font-black tracking-wider uppercase text-slate-200">Instant Repair Intake</h3>
                                <p class="text-[10px] text-slate-400">Choose your issue for instant quote &amp; booking</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Lab Online
                        </span>
                    </div>

                    <!-- 4 Quick-Select Repair Issue Tiles -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Screen -->
                        <a href="<?= url('/repair-book.php?issue=screen_damage') ?>" class="group p-3 rounded-2xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/60 hover:border-blue-500/60 transition-all duration-200 flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-7 h-7 rounded-lg bg-sky-500/15 text-sky-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 group-hover:text-blue-400 flex items-center gap-0.5">
                                    Book <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="text-xs font-bold text-white group-hover:text-blue-300 transition-colors">Screen &amp; OLED</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Glass, Touch, Lines • ~45m</div>
                            </div>
                        </a>

                        <!-- Battery -->
                        <a href="<?= url('/repair-book.php?issue=battery_replacement') ?>" class="group p-3 rounded-2xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/60 hover:border-emerald-500/60 transition-all duration-200 flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-7 h-7 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i data-lucide="battery-charging" class="w-4 h-4"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 group-hover:text-emerald-400 flex items-center gap-0.5">
                                    Book <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="text-xs font-bold text-white group-hover:text-emerald-300 transition-colors">Battery Replacement</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">OEM Cell, Fast Drain • ~30m</div>
                            </div>
                        </a>

                        <!-- Charging Port -->
                        <a href="<?= url('/repair-book.php?issue=charging_port') ?>" class="group p-3 rounded-2xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/60 hover:border-amber-500/60 transition-all duration-200 flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-7 h-7 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i data-lucide="zap" class="w-4 h-4"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 group-hover:text-amber-400 flex items-center gap-0.5">
                                    Book <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="text-xs font-bold text-white group-hover:text-amber-300 transition-colors">Charging Port</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Loose pin, Slow charge • ~40m</div>
                            </div>
                        </a>

                        <!-- Liquid / Motherboard -->
                        <a href="<?= url('/repair-book.php?issue=water_damage') ?>" class="group p-3 rounded-2xl bg-slate-800/50 hover:bg-slate-800 border border-slate-700/60 hover:border-purple-500/60 transition-all duration-200 flex flex-col justify-between">
                            <div class="flex items-start justify-between">
                                <div class="w-7 h-7 rounded-lg bg-purple-500/15 text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i data-lucide="droplets" class="w-4 h-4"></i>
                                </div>
                                <span class="text-[10px] font-bold text-slate-400 group-hover:text-purple-400 flex items-center gap-0.5">
                                    Book <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                </span>
                            </div>
                            <div class="mt-3">
                                <div class="text-xs font-bold text-white group-hover:text-purple-300 transition-colors">Water Damage &amp; IC</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Ultrasonic wash &amp; micro-solder</div>
                            </div>
                        </a>
                    </div>

                    <!-- Bottom Guarantee Banner in Card -->
                    <div class="mt-4 pt-3.5 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-emerald-400"></i>
                            <span>No Fix, No Fee Guarantee</span>
                        </span>
                        <a href="<?= url('/repair-book.php') ?>" class="text-blue-400 hover:text-blue-300 font-bold hover:underline flex items-center gap-0.5">
                            <span>All Issues</span>
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Detailed Repair Guarantees / Quality Seals Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-12 pt-10 border-t border-slate-800/80">
            <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-white">Certified Technicians</h4>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Level-4 ESD-certified chip engineers trained across Apple, Samsung, OnePlus &amp; Xiaomi.</p>
                </div>
            </div>

            <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-white">90-Day Full Warranty</h4>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Every replaced screen, battery, or component comes with a 90-day free replacement guarantee.</p>
                </div>
            </div>

            <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                    <i data-lucide="cpu" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-white">100% Genuine OEM Parts</h4>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Authentic displays and high-density cells ensure true-tone fidelity and battery health metrics.</p>
                </div>
            </div>

            <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-white">Doorstep &amp; Express Lab</h4>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">Walk in for 45-min counter service or schedule insured doorstep pickup &amp; delivery.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Brand Carousel -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-6">Authorized Brands</h3>
        <div class="flex flex-wrap justify-center items-center gap-8 sm:gap-12 opacity-75 grayscale hover:grayscale-0 transition-all duration-300">
            <?php foreach ($brands as $b): ?>
                <a href="<?= url('/shop.php?brand=' . $b['slug']) ?>" class="font-extrabold text-base tracking-wider text-slate-800 hover:text-blue-600 transition">
                    <?= e($b['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
