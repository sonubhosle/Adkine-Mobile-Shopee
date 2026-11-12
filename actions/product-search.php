<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    json_response(['products' => []]);
}

$wildcard = "%{$q}%";
$products = db_fetch_all(
    "SELECT id, name, slug, base_price, sale_price, thumbnail 
     FROM products 
     WHERE is_active = 1 AND (name LIKE ? OR sku LIKE ? OR model_number LIKE ?) 
     ORDER BY id DESC 
     LIMIT 6",
    [$wildcard, $wildcard, $wildcard]
);

foreach ($products as &$p) {
    $p['formatted_price'] = format_currency($p['sale_price'] ?? $p['base_price']);
    $p['url'] = url('/product.php?slug=' . $p['slug']);
}

json_response(['products' => $products]);

