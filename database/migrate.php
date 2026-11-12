<?php
/**
 * MobileShopFlat — Database Migration & Seeding Tool
 * Automatically creates database, applies schema.sql and seed.sql
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$name = DB_NAME;
$port = DB_PORT;

echo "===============================================================\n";
echo " MobileShopFlat Database Migration & Seeder\n";
echo " Target DB: `{$name}` on {$host}:{$port}\n";
echo "===============================================================\n\n";

$mysqli = new mysqli($host, $user, $pass, '', $port);
if ($mysqli->connect_error) {
    die("❌ Connection failed: " . $mysqli->connect_error . "\n");
}

// 1. Create database if not exists
echo "1. Checking database `{$name}`...\n";
$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$mysqli->select_db($name);
echo "   Database `{$name}` is ready.\n\n";

// 2. Apply schema.sql
echo "2. Applying schema.sql...\n";
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    die("❌ Error: schema.sql not found at {$schemaFile}\n");
}

$schemaSql = file_get_contents($schemaFile);
if ($mysqli->multi_query($schemaSql)) {
    do {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
}

if ($mysqli->error) {
    die("❌ Schema execution error: " . $mysqli->error . "\n");
}
echo "   ✅ Schema applied successfully.\n\n";

// 3. Apply seed.sql
echo "3. Applying seed.sql...\n";
$seedFile = __DIR__ . '/seed.sql';
if (!file_exists($seedFile)) {
    die("❌ Error: seed.sql not found at {$seedFile}\n");
}

$seedSql = file_get_contents($seedFile);
if ($mysqli->multi_query($seedSql)) {
    do {
        if ($res = $mysqli->store_result()) {
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
}

if ($mysqli->error) {
    // If it's a duplicate entry notice, that's fine, otherwise show error
    if (str_contains($mysqli->error, 'Duplicate entry')) {
        echo "   ℹ️ Seed data already present (duplicate entry skipped).\n";
    } else {
        echo "   ⚠️ Seed notice: " . $mysqli->error . "\n";
    }
} else {
    echo "   ✅ Master & demo seed data inserted successfully.\n\n";
}

echo "===============================================================\n";
echo "🎉 Migration & Seeding Complete!\n";
echo "===============================================================\n";

