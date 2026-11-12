<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart-functions.php';

session_init();

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!empty($slug)) {
    $product = db_fetch_one(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN brands b ON p.brand_id = b.id
         WHERE p.slug = ? AND p.is_active = 1
         LIMIT 1",
        [$slug]
    );
} elseif ($id > 0) {
    $product = db_fetch_one(
        "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN brands b ON p.brand_id = b.id
         WHERE p.id = ? AND p.is_active = 1
         LIMIT 1",
        [$id]
    );
} else {
    redirect('/shop.php');
}

if (!$product) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body><h1>Product Not Found</h1><p><a href="' . url('/shop.php') . '">Return to shop</a></p></body></html>';
    exit;
}

$productId = (int)$product['id'];

// Variants
$variants = db_fetch_all(
    "SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY additional_price ASC",
    [$productId]
);

// Specs
$specifications = [];
if (!empty($product['specifications'])) {
    $specifications = json_decode($product['specifications'], true) ?: [];
}

// Reviews
$reviews = db_fetch_all(
    "SELECT r.*, u.full_name as user_name 
     FROM product_reviews r 
     JOIN users u ON r.user_id = u.id 
     WHERE r.product_id = ? AND r.status = 'approved' 
     ORDER BY r.id DESC",
    [$productId]
);

$avgRating = 0;
if (!empty($reviews)) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($sum / count($reviews), 1);
}

$pageTitle = $product['name'];
$metaDescription = !empty($product['short_desc']) 
    ? $product['short_desc'] 
    : 'Buy ' . $product['name'] . ' online at best price with warranty at ' . setting('site_name', 'Adkine Mobile Shopee') . '.';
$canonicalUrl = absolute_url('/product.php?slug=' . $product['slug']);
$ogType = 'product';
$ogImage = !empty($product['thumbnail']) ? absolute_url('/uploads/products/' . $product['thumbnail']) : '';

// Technical SEO Schema: Google Product & BreadcrumbList
$prodPrice = (float)($product['sale_price'] ?? $product['base_price']);
$offers = [
    '@type' => 'Offer',
    'price' => number_format($prodPrice, 2, '.', ''),
    'priceCurrency' => 'INR',
    'availability' => 'https://schema.org/InStock',
    'url' => $canonicalUrl,
    'priceValidUntil' => date('Y-12-31'),
];

$productSchema = [
    '@type' => 'Product',
    'name' => $product['name'],
    'image' => $ogImage ?: absolute_url('/assets/images/og-preview.jpg'),
    'description' => !empty($product['short_desc']) ? $product['short_desc'] : $product['name'],
    'sku' => !empty($product['sku']) ? $product['sku'] : ('PROD-' . $product['id']),
    'brand' => [
        '@type' => 'Brand',
        'name' => $product['brand_name'] ?? 'Generic'
    ],
    'offers' => $offers
];

if (!empty($reviews)) {
    $productSchema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $avgRating,
        'reviewCount' => count($reviews),
        'bestRating' => '5',
        'worstRating' => '1'
    ];
}

$breadcrumbSchema = [
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
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
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $product['category_name'],
            'item' => absolute_url('/shop.php?category=' . $product['category_slug'])
        ],
        [
            '@type' => 'ListItem',
            'position' => 4,
            'name' => $product['name'],
            'item' => $canonicalUrl
        ]
    ]
];

$pageSchema = [
    '@context' => 'https://schema.org',
    '@graph' => [$productSchema, $breadcrumbSchema]
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">
    <!-- Breadcrumbs -->
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= url('/index.php') ?>" class="hover:text-blue-600">Home</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <a href="<?= url('/shop.php') ?>" class="hover:text-blue-600">Products</a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <a href="<?= url('/shop.php?category=' . $product['category_slug']) ?>" class="hover:text-blue-600"><?= e($product['category_name']) ?></a>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        <span class="text-slate-900 font-semibold truncate"><?= e($product['name']) ?></span>
    </nav>

    <!-- Product Main View -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 bg-white p-6 sm:p-10 rounded-3xl border border-slate-200/80 shadow-xs">
        <!-- Gallery Preview -->
        <div class="lg:col-span-5 flex flex-col items-center">
            <div class="w-full aspect-square rounded-3xl bg-slate-50 border border-slate-100 flex items-center justify-center p-8 relative overflow-hidden">
                <?php if (!empty($product['thumbnail']) && file_exists(__DIR__ . '/uploads/products/' . $product['thumbnail'])): ?>
                    <img src="<?= url('/uploads/products/' . $product['thumbnail']) ?>" alt="<?= e($product['name']) ?> - Official <?= e($product['brand_name'] ?? 'Electronics') ?> Smartphone / Device" decoding="async" fetchpriority="high" class="max-h-full max-w-full object-contain">
                <?php else: ?>
                    <div class="w-40 h-40 rounded-3xl bg-white shadow-md border border-slate-100 flex items-center justify-center text-blue-600">
                        <i data-lucide="smartphone" class="w-24 h-24"></i>
                    </div>
                <?php endif; ?>
                <div class="absolute top-4 left-4">
                    <span class="px-3 py-1 rounded-xl bg-slate-900 text-white text-[10px] font-extrabold uppercase tracking-wider">
                        <?= e($product['brand_name'] ?? 'Brand') ?>
                    </span>
                </div>
            </div>
            
            <div class="flex items-center gap-4 mt-6 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i> <?= (int)$product['warranty_months'] ?> Months Warranty</span>
                <span>&bull;</span>
                <span class="flex items-center gap-1.5"><i data-lucide="check" class="w-4 h-4 text-blue-600"></i> 100% Genuine OEM</span>
            </div>
        </div>

        <!-- Product Details & Actions -->
        <div class="lg:col-span-7 space-y-6">
            <div>
                <span class="text-xs font-bold text-blue-600 uppercase tracking-wider"><?= e($product['category_name'] ?? '') ?></span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1"><?= e($product['name']) ?></h1>
                
                <div class="flex flex-wrap items-center gap-4 mt-3 text-xs">
                    <div class="flex items-center gap-1.5 text-amber-500 font-bold">
                        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
                        <span><?= $avgRating > 0 ? $avgRating : '5.0' ?></span>
                        <span class="text-slate-400 font-normal">(<?= count($reviews) ?> customer reviews)</span>
                    </div>
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-500 font-medium">SKU: <code class="font-mono text-slate-700 font-bold"><?= e($product['sku']) ?></code></span>
                    <?php if (!empty($product['model_number'])): ?>
                        <span class="text-slate-300">|</span>
                        <span class="text-slate-500 font-medium">Model: <strong class="text-slate-700"><?= e($product['model_number']) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Price Display -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex items-baseline gap-3">
                <span id="displayPrice" class="text-3xl font-black text-slate-900" data-base-price="<?= (float)($product['sale_price'] ?? $product['base_price']) ?>">
                    <?= format_currency($product['sale_price'] ?? $product['base_price']) ?>
                </span>
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['base_price']): ?>
                    <span class="text-sm text-slate-400 line-through">
                        <?= format_currency($product['base_price']) ?>
                    </span>
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-extrabold">
                        Save <?= format_currency($product['base_price'] - $product['sale_price']) ?>
                    </span>
                <?php endif; ?>
                <span class="text-xs text-slate-400 font-medium ml-auto">Inclusive of all taxes (GST 18%)</span>
            </div>

            <p class="text-sm text-slate-600 leading-relaxed">
                <?= e($product['short_desc'] ?? '') ?>
            </p>

            <!-- Form: Variants & Add to Cart -->
            <form id="addToCartForm" class="space-y-6 pt-2">
                <?php if (!empty($variants)): ?>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-900 uppercase tracking-wider">
                            Select Specification / Variant:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <?php foreach ($variants as $idx => $v): ?>
                                <label class="relative flex items-center justify-between p-3.5 rounded-xl border border-slate-200 hover:border-blue-500 cursor-pointer transition">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="variant_id" value="<?= $v['id'] ?>" data-additional="<?= (float)$v['additional_price'] ?>" <?= $idx === 0 ? 'checked' : '' ?> onchange="updateVariantPrice(this)" class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-xs font-bold text-slate-900"><?= e($v['variant_name']) ?></span>
                                    </div>
                                    <?php if ($v['additional_price'] > 0): ?>
                                        <span class="text-xs font-bold text-blue-600">+<?= format_currency($v['additional_price']) ?></span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3">
                        <label for="quantityInput" class="text-xs font-bold text-slate-700">Quantity:</label>
                        <div class="flex items-center border border-slate-200 rounded-xl p-1 bg-white">
                            <button type="button" onclick="adjustQty(-1)" aria-label="Decrease quantity" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded-lg focus-visible:ring-2 focus-visible:ring-blue-600">
                                <i data-lucide="minus" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                            <input type="number" id="quantityInput" name="quantity" value="1" min="1" max="10" aria-label="Selected quantity" class="w-12 text-center text-sm font-bold text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 rounded">
                            <button type="button" onclick="adjustQty(1)" aria-label="Increase quantity" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded-lg focus-visible:ring-2 focus-visible:ring-blue-600">
                                <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs font-bold text-emerald-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                        <span>In Stock &amp; Ready for Immediate Dispatch</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <button type="button" onclick="handleProductAddToCart(false)" class="w-full py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-xl shadow-blue-500/25 transition flex items-center justify-center gap-2 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <i data-lucide="shopping-bag" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Add to Shopping Cart</span>
                    </button>
                    <button type="button" onclick="handleProductAddToCart(true)" class="w-full py-3.5 px-6 rounded-2xl bg-navy-900 hover:bg-black text-white font-bold text-sm shadow-xl shadow-slate-900/20 transition flex items-center justify-center gap-2 focus-visible:ring-2 focus-visible:ring-slate-900">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-400" aria-hidden="true"></i>
                        <span>Buy Now &bull; Instant Checkout</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Technical Specifications -->
    <div class="bg-white p-6 sm:p-10 rounded-3xl border border-slate-200/80 shadow-xs space-y-8">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Technical Specifications</h2>
            <p class="text-xs text-slate-500 mt-1">Verified hardware and engineering details</p>
        </div>

        <?php if (!empty($specifications)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse" aria-label="Product Technical Specifications">
                    <caption class="sr-only">Detailed hardware and engineering specifications</caption>
                    <tbody>
                        <?php foreach ($specifications as $specKey => $specVal): ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition">
                                <th scope="row" class="py-3 px-4 font-bold text-slate-700 bg-slate-50/50 w-1/3 sm:w-1/4 uppercase tracking-wider text-[11px] text-left">
                                    <?= e(ucwords(str_replace('_', ' ', (string)$specKey))) ?>
                                </th>
                                <td class="py-3 px-4 text-slate-900 font-medium">
                                    <?= e(is_array($specVal) ? implode(', ', $specVal) : (string)$specVal) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-xs text-slate-400">Detailed specifications will be updated shortly.</p>
        <?php endif; ?>

        <?php if (!empty($product['description'])): ?>
            <div class="border-t border-slate-100 pt-6 space-y-3">
                <h3 class="text-base font-bold text-slate-900">Overview & Features</h3>
                <div class="text-xs text-slate-600 leading-relaxed space-y-2 prose prose-slate">
                    <?= $product['description'] ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function adjustQty(amount) {
    const input = document.getElementById('quantityInput');
    let val = parseInt(input.value) || 1;
    val = Math.max(1, Math.min(10, val + amount));
    input.value = val;
}

function updateVariantPrice(radio) {
    const base = parseFloat(document.getElementById('displayPrice').getAttribute('data-base-price'));
    const add = parseFloat(radio.getAttribute('data-additional')) || 0;
    const finalPrice = base + add;
    document.getElementById('displayPrice').textContent = '₹' + finalPrice.toLocaleString('en-IN', { minimumFractionDigits: 2 });
}

async function handleProductAddToCart(buyNow = false) {
    const qty = parseInt(document.getElementById('quantityInput').value) || 1;
    const variantEl = document.querySelector('input[name="variant_id"]:checked');
    const variantId = variantEl ? variantEl.value : null;

    try {
        const formData = new FormData();
        formData.append('product_id', <?= $productId ?>);
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
            if (buyNow) {
                window.location.href = '<?= url('/checkout.php') ?>';
            } else {
                window.location.reload();
            }
        } else {
            alert(data.message || 'Could not add item to cart.');
        }
    } catch (e) {
        console.error(e);
        alert('An error occurred.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

