<?php
/**
 * Dynamic XML Sitemap Generator for Google Search Console
 * Standards compliant: https://www.sitemaps.org/schemas/sitemap/0.9
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex, follow', true);

$baseUrl = rtrim(SITE_URL, '/');

// 1. Static Public Pages
$staticPages = [
    [
        'loc' => $baseUrl . '/index.php',
        'changefreq' => 'daily',
        'priority' => '1.0',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/shop.php',
        'changefreq' => 'daily',
        'priority' => '0.9',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/repair-book.php',
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'lastmod' => date('Y-m-d')
    ],
    [
        'loc' => $baseUrl . '/repair-track.php',
        'changefreq' => 'weekly',
        'priority' => '0.7',
        'lastmod' => date('Y-m-d')
    ]
];

// 2. Active Categories
$categories = db_fetch_all(
    "SELECT slug, created_at FROM categories WHERE is_active = 1 ORDER BY display_order ASC"
);

// 3. Active Brands
$brands = db_fetch_all(
    "SELECT slug, created_at FROM brands WHERE is_active = 1 ORDER BY name ASC"
);

// 4. Active Products
$products = db_fetch_all(
    "SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY id DESC"
);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Core Public Landing Pages -->
    <?php foreach ($staticPages as $p): ?>
    <url>
        <loc><?= htmlspecialchars($p['loc']) ?></loc>
        <lastmod><?= htmlspecialchars($p['lastmod']) ?></lastmod>
        <changefreq><?= htmlspecialchars($p['changefreq']) ?></changefreq>
        <priority><?= htmlspecialchars($p['priority']) ?></priority>
    </url>
    <?php endforeach; ?>

    <!-- Category Hubs -->
    <?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/shop.php?category=' . urlencode($cat['slug'])) ?></loc>
        <lastmod><?= htmlspecialchars(date('Y-m-d', strtotime($cat['created_at'] ?? 'now'))) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Brand Hubs -->
    <?php foreach ($brands as $br): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/shop.php?brand=' . urlencode($br['slug'])) ?></loc>
        <lastmod><?= htmlspecialchars(date('Y-m-d', strtotime($br['created_at'] ?? 'now'))) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

    <!-- Active Products -->
    <?php foreach ($products as $prod): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/product.php?slug=' . urlencode($prod['slug'])) ?></loc>
        <lastmod><?= htmlspecialchars(date('Y-m-d', strtotime($prod['updated_at'] ?? 'now'))) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <?php endforeach; ?>

</urlset>
