<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart-functions.php';

session_init();

$q = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$brandSlug = trim($_GET['brand'] ?? '');
$minPrice = $_GET['min_price'] ?? null;
$maxPrice = $_GET['max_price'] ?? null;
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = ["p.is_active = 1"];
$params = [];

if (!empty($q)) {
    $where[] = "(p.name LIKE ? OR p.short_desc LIKE ? OR p.sku LIKE ? OR p.model_number LIKE ?)";
    $wildcard = "%{$q}%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
    $params[] = $wildcard;
}

if (!empty($categorySlug)) {
    $where[] = "c.slug = ?";
    $params[] = $categorySlug;
}

if (!empty($brandSlug)) {
    $where[] = "b.slug = ?";
    $params[] = $brandSlug;
}

if ($minPrice !== null && $minPrice !== '') {
    $where[] = "COALESCE(p.sale_price, p.base_price) >= ?";
    $params[] = (float)$minPrice;
}

if ($maxPrice !== null && $maxPrice !== '') {
    $where[] = "COALESCE(p.sale_price, p.base_price) <= ?";
    $params[] = (float)$maxPrice;
}

$whereClause = implode(' AND ', $where);

$orderBy = match ($sort) {
    'price_low' => 'COALESCE(p.sale_price, p.base_price) ASC',
    'price_high' => 'COALESCE(p.sale_price, p.base_price) DESC',
    'name' => 'p.name ASC',
    default => 'p.id DESC',
};

$countSql = "SELECT COUNT(*) as total 
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE {$whereClause}";
$totalItems = (int)(db_fetch_value($countSql, $params) ?? 0);
$totalPages = ceil($totalItems / $perPage);

$sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug,
               COALESCE(i.current_stock, 0) as stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN inventory i ON (p.id = i.product_id AND i.variant_id IS NULL)
        WHERE {$whereClause}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$offset}";
$products = db_fetch_all($sql, $params);

$categories = db_fetch_all("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC");
$brands = db_fetch_all("SELECT * FROM brands WHERE is_active = 1 ORDER BY name ASC");

$activeFilters = [
    'q' => $q,
    'category' => $categorySlug,
    'brand' => $brandSlug,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort' => $sort,
];

// Technical SEO Dynamic Metadata
$selectedCategory = !empty($categorySlug) ? db_fetch_one("SELECT name, description FROM categories WHERE slug = ?", [$categorySlug]) : null;
$selectedBrand = !empty($brandSlug) ? db_fetch_one("SELECT name FROM brands WHERE slug = ?", [$brandSlug]) : null;

if (!empty($q)) {
    $pageTitle = 'Search Results for "' . $q . '"';
    $shopH1 = 'Search Results for "' . $q . '"';
    $metaDescription = 'Explore matching electronics, smartphones, and accessories for query "' . $q . '". Fast shipping & warranty at Adkine Mobile Shopee.';
} elseif ($selectedCategory) {
    $pageTitle = $selectedCategory['name'] . ' Catalogue';
    $shopH1 = $selectedCategory['name'];
    $metaDescription = !empty($selectedCategory['description']) 
        ? $selectedCategory['description'] 
        : 'Browse our selection of certified ' . $selectedCategory['name'] . ' with best prices, 1-year warranty, and express delivery.';
} elseif ($selectedBrand) {
    $pageTitle = $selectedBrand['name'] . ' Devices & Accessories';
    $shopH1 = $selectedBrand['name'] . ' Collection';
    $metaDescription = 'Official ' . $selectedBrand['name'] . ' smartphones and accessories available at Adkine Mobile Shopee with verified manufacturer warranty.';
} else {
    $pageTitle = 'Smartphones, Audio & Gadgets Catalogue';
    $shopH1 = 'All Products & Electronics';
    $metaDescription = 'Explore the complete catalogue of smartphones, GaN chargers, audio devices, smartwatches, and OEM spare parts. Free delivery over ₹2,000.';
}

$canonicalParams = [];
if (!empty($categorySlug)) $canonicalParams['category'] = $categorySlug;
if (!empty($brandSlug)) $canonicalParams['brand'] = $brandSlug;
$canonicalUrl = absolute_url('/shop.php' . ($canonicalParams ? '?' . http_build_query($canonicalParams) : ''));

$breadcrumbItems = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => absolute_url('/index.php')
    ],
    [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => 'Products',
        'item' => absolute_url('/shop.php')
    ]
];
if ($selectedCategory) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $selectedCategory['name'],
        'item' => $canonicalUrl
    ];
}

$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="px-6 py-6">
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
        <a href="<?= url('/index.php') ?>" class="hover:text-blue-600">Home</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-900 font-semibold">Products</span>
        <?php if (!empty($activeFilters['category'])): ?>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-blue-600 font-bold"><?= e(ucwords(str_replace('-', ' ', $activeFilters['category']))) ?></span>
        <?php endif; ?>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Filter Sidebar -->
        <aside class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4 text-blue-600"></i>
                        <span>Filters</span>
                    </h3>
                    <a href="<?= url('/shop.php') ?>" class="text-[11px] font-bold text-blue-600 hover:underline">Reset All</a>
                </div>

                <form id="filterForm" action="<?= url('/shop.php') ?>" method="GET" class="space-y-6">
                    <?php if (!empty($activeFilters['q'])): ?>
                        <input type="hidden" name="q" value="<?= e($activeFilters['q']) ?>">
                    <?php endif; ?>

                    <!-- Categories Filter -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Categories</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto scrollbar-thin">
                            <label class="flex items-center font-semibold text-[13px] text-slate-900 gap-2.5 hover:text-slate-900 cursor-pointer">
                                <input type="radio" name="category" value="" <?= empty($activeFilters['category']) ? 'checked' : '' ?> onchange="this.form.submit()" class="text-blue-600 rounded">
                                <span class="<?= empty($activeFilters['category']) ? 'font-bold text-blue-600' : '' ?>">All Categories</span>
                            </label>
                            <?php foreach ($categories as $cat): ?>
                                <label class="flex items-center gap-2.5 font-semibold text-[13px] text-slate-900 hover:text-slate-900 cursor-pointer">
                                    <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $activeFilters['category'] === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()" class="text-blue-600 rounded">
                                    <span class="<?= $activeFilters['category'] === $cat['slug'] ? 'font-bold text-blue-600' : '' ?>"><?= e($cat['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Brands Filter -->
                    <div class="border-t border-slate-100 pt-5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Brands</h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto scrollbar-thin">
                            <label class="flex items-center gap-2.5 font-semibold text-[13px] text-slate-900 hover:text-slate-900 cursor-pointer">
                                <input type="radio" name="brand" value="" <?= empty($activeFilters['brand']) ? 'checked' : '' ?> onchange="this.form.submit()" class="text-blue-600 rounded">
                                <span class="<?= empty($activeFilters['brand']) ? 'font-bold text-blue-600' : '' ?>">All Brands</span>
                            </label>
                            <?php foreach ($brands as $b): ?>
                                <label class="flex items-center gap-2.5 font-semibold text-[13px] text-slate-900 hover:text-slate-900 cursor-pointer">
                                    <input type="radio" name="brand" value="<?= e($b['slug']) ?>" <?= $activeFilters['brand'] === $b['slug'] ? 'checked' : '' ?> onchange="this.form.submit()" class="text-blue-600 rounded">
                                    <span class="<?= $activeFilters['brand'] === $b['slug'] ? 'font-bold text-blue-600' : '' ?>"><?= e($b['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="border-t border-slate-100 pt-5">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Price Range (₹)</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="minPriceInput" class="sr-only">Minimum price in Rupees</label>
                                <input type="number" id="minPriceInput" name="min_price" value="<?= e($activeFilters['min_price'] ?? '') ?>" placeholder="Min ₹" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                            <div>
                                <label for="maxPriceInput" class="sr-only">Maximum price in Rupees</label>
                                <input type="number" id="maxPriceInput" name="max_price" value="<?= e($activeFilters['max_price'] ?? '') ?>" placeholder="Max ₹" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus-visible:ring-2 focus-visible:ring-blue-600">
                            </div>
                        </div>
                        <button type="submit" class="w-full mt-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition focus-visible:ring-2 focus-visible:ring-slate-900">
                            Apply Price
                        </button>
                    </div>

                    <!-- Sort -->
                    <div class="border-t border-slate-100 pt-5">
                        <label for="shopSortSelect" class="block text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Sort By</label>
                        <select id="shopSortSelect" name="sort" onchange="this.form.submit()" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 bg-white focus-visible:ring-2 focus-visible:ring-blue-600">
                            <option value="newest" <?= $activeFilters['sort'] === 'newest' ? 'selected' : '' ?>>Newest Arrivals</option>
                            <option value="price_low" <?= $activeFilters['sort'] === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= $activeFilters['sort'] === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="name" <?= $activeFilters['sort'] === 'name' ? 'selected' : '' ?>>Alphabetical</option>
                        </select>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Product Grid Section (Replacing duplicate main tag) -->
        <section class="lg:col-span-3 space-y-6" aria-label="Product Catalog">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                        <?= e($shopH1) ?>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Showing <strong class="text-slate-900"><?= count($products) ?></strong> of <strong class="text-slate-900"><?= $totalItems ?></strong> products
                        <?php if (!empty($q)): ?>
                            matching "<strong class="text-blue-600"><?= e($q) ?></strong>"
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center space-y-3">
                    <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-2xl flex items-center justify-center mx-auto" aria-hidden="true">
                        <i data-lucide="package-x" class="w-8 h-8"></i>
                    </div>
                    <h2 class="text-base font-bold text-slate-900">No products found</h2>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">Try loosening your filter parameters or search terms to discover items.</p>
                    <a href="<?= url('/shop.php') ?>" class="inline-block mt-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition focus-visible:ring-2 focus-visible:ring-blue-600">
                        Clear Filters
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach ($products as $prod): ?>
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

                            <a href="<?= url('/product.php?slug=' . $prod['slug']) ?>" class="w-full h-44 rounded-2xl bg-slate-50 flex items-center justify-center p-4 group-hover:scale-102 transition overflow-hidden focus-visible:ring-2 focus-visible:ring-blue-600" aria-label="View details of <?= e($prod['name']) ?>">
                                <?php if (!empty($prod['thumbnail']) && file_exists(__DIR__ . '/uploads/products/' . $prod['thumbnail'])): ?>
                                    <img src="<?= url('/uploads/products/' . $prod['thumbnail']) ?>" alt="<?= e($prod['name']) ?> - <?= e($prod['category_name'] ?? 'Electronics') ?> Online" loading="lazy" decoding="async" class="max-h-36 max-w-full object-contain">
                                <?php else: ?>
                                    <div class="w-20 h-20 rounded-2xl bg-white shadow-xs border border-slate-100 flex items-center justify-center text-blue-600" aria-hidden="true">
                                        <i data-lucide="smartphone" class="w-10 h-10"></i>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div class="mt-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <div class="text-[11px] text-slate-500 font-medium"><?= e($prod['category_name'] ?? '') ?></div>
                                    <h3 class="font-bold text-sm text-slate-900 group-hover:text-blue-600 transition mt-0.5 line-clamp-1">
                                        <a href="<?= url('/product.php?slug=' . $prod['slug']) ?>" class="focus-visible:ring-2 focus-visible:ring-blue-600">
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
                                            <div class="text-[11px] text-slate-500 line-through">
                                                <?= format_currency($prod['base_price']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($prod['has_variants'])): ?>
                                        <a href="<?= url('/product.php?slug=' . $prod['slug']) ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition focus-visible:ring-2 focus-visible:ring-blue-600" aria-label="Select options for <?= e($prod['name']) ?>">
                                            Options
                                        </a>
                                    <?php else: ?>
                                        <button onclick="addToCart(<?= (int)$prod['id'] ?>)" aria-label="Add <?= e($prod['name']) ?> to shopping cart" class="p-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition focus-visible:ring-2 focus-visible:ring-blue-600" title="Add to Cart">
                                            <i data-lucide="shopping-bag" class="w-4 h-4" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="flex justify-center items-center gap-2 pt-6" aria-label="Pagination navigation">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php
                            $queryArgs = array_merge($_GET, ['page' => $i]);
                            $pageUrl = url('/shop.php?' . http_build_query($queryArgs));
                            ?>
                            <a href="<?= $pageUrl ?>" <?= $i === $page ? 'aria-current="page"' : '' ?> aria-label="Page <?= $i ?>" class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition focus-visible:ring-2 focus-visible:ring-blue-600 <?= $i === $page ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

