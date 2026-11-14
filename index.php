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

        <!-- 5 Category Cards Per Row: Left Image, Name & Button Below -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-3.5">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('/shop.php?category=' . $cat['slug']) ?>" 
                   class="group bg-white rounded-2xl border border-slate-200/80 hover:border-blue-400/80 shadow-2xs hover:shadow-lg hover:shadow-blue-500/10 p-3 transition-all duration-300 flex items-center gap-3 hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-blue-600 overflow-hidden">
                    
                    <!-- Small Category Image in Left -->
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center p-1 shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <?php if (!empty($cat['image']) && file_exists(__DIR__ . '/uploads/categories/' . $cat['image'])): ?>
                            <img src="<?= url('/uploads/categories/' . $cat['image']) ?>" alt="<?= e($cat['name']) ?>" loading="lazy" decoding="async" class="w-full h-full object-cover rounded-lg">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400 rounded-lg">
                                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Name & Below Name Button -->
                    <div class="min-w-0 flex-1 flex flex-col justify-center">
                        <h3 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-blue-600 transition-colors truncate" title="<?= e($cat['name']) ?>">
                            <?= e($cat['name']) ?>
                        </h3>
                        <span class="inline-flex items-center gap-1 mt-1.5 px-2.5 py-1 rounded-lg bg-slate-900 group-hover:bg-blue-600 text-white font-bold text-[10px] sm:text-[11px] w-fit transition-colors shadow-2xs">
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

<!-- Repair CTA Banner -->
<section class="py-14 bg-gradient-to-r from-indigo-700 to-indigo-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-8 space-y-4">
                <span class="px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider inline-block">
                    Certified Technicians &bull; 90-Day Repair Warranty
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Cracked Screen or Draining Battery?</h2>
                <p class="text-blue-100 text-sm sm:text-base max-w-2xl leading-relaxed">
                    We repair all major smartphone models with original OEM components. Book a service appointment online, request a doorstep pickup, or walk into our showroom repair lab.
                </p>
                <div class="flex flex-wrap gap-4 pt-2">
                    <a href="<?= url('/repair-book.php') ?>" class="px-6 py-3 rounded-xl bg-white text-blue-700 font-extrabold text-xs hover:bg-indigo-50 transition shadow-lg shadow-black/10 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Book Repair Ticket</span>
                    </a>
                    <a href="<?= url('/repair-track.php') ?>" class="px-6 py-3 rounded-xl bg-indigo-800/80 hover:bg-indigo-800 text-white font-extrabold text-xs border border-blue-500/40 transition flex items-center gap-2">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Check Repair Status</span>
                    </a>
                </div>
            </div>
            <div class="lg:col-span-4 flex justify-center lg:justify-end">
                <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 text-center space-y-3 max-w-xs w-full">
                    <div class="w-14 h-14 rounded-2xl bg-white text-blue-600 flex items-center justify-center mx-auto shadow-md">
                        <i data-lucide="wrench" class="w-7 h-7"></i>
                    </div>
                    <div class="font-extrabold text-lg">Instant Repair Quote</div>
                    <p class="text-xs text-blue-200">Transparent pricing. No hidden diagnostic fees upon repair approval.</p>
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
