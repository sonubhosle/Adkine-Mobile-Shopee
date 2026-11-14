<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    json_response(['products' => [], 'total' => 0, 'query' => $q]);
}

$wildcard = "%{$q}%";
$products = db_fetch_all(
    "SELECT 
        p.id, 
        p.name, 
        p.slug, 
        p.base_price, 
        p.sale_price, 
        p.thumbnail,
        c.name AS category_name,
        b.name AS brand_name,
        COALESCE(SUM(i.current_stock), 0) AS total_stock
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     LEFT JOIN inventory i ON p.id = i.product_id
     WHERE p.is_active = 1 
       AND (p.name LIKE ? OR p.sku LIKE ? OR p.model_number LIKE ? OR b.name LIKE ? OR c.name LIKE ?)
     GROUP BY p.id
     ORDER BY 
        CASE 
            WHEN p.name LIKE ? THEN 1
            WHEN b.name LIKE ? THEN 2
            ELSE 3
        END,
        p.id DESC
     LIMIT 8",
    [$wildcard, $wildcard, $wildcard, $wildcard, $wildcard, "{$q}%", "{$q}%"]
);

foreach ($products as &$p) {
    $currentPrice = ($p['sale_price'] !== null && $p['sale_price'] < $p['base_price']) 
        ? (float)$p['sale_price'] 
        : (float)$p['base_price'];
    
    $hasDiscount = ($p['sale_price'] !== null && (float)$p['sale_price'] < (float)$p['base_price']);
    $discountPercent = $hasDiscount 
        ? (int)round((((float)$p['base_price'] - (float)$p['sale_price']) / (float)$p['base_price']) * 100) 
        : 0;

    $imageUrl = null;
    if (!empty($p['thumbnail']) && file_exists(__DIR__ . '/../uploads/products/' . $p['thumbnail'])) {
        $imageUrl = url('/uploads/products/' . $p['thumbnail']);
    }

    $stock = (int)$p['total_stock'];

    $p['current_price'] = $currentPrice;
    $p['formatted_price'] = format_currency($currentPrice);
    $p['formatted_base_price'] = format_currency((float)$p['base_price']);
    $p['has_discount'] = $hasDiscount;
    $p['discount_percent'] = $discountPercent;
    $p['image_url'] = $imageUrl;
    $p['stock'] = $stock;
    $p['is_in_stock'] = ($stock > 0);
    $p['stock_status'] = $stock > 5 ? 'in_stock' : ($stock > 0 ? 'low_stock' : 'out_of_stock');
    $p['url'] = url('/product.php?slug=' . $p['slug']);
}
unset($p);

json_response([
    'products' => $products,
    'total'    => count($products),
    'query'    => $q,
    'all_url'  => url('/shop.php?q=' . urlencode($q))
]);


