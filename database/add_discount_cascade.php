<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php'; // assumes db connection helper

// Get DB connection
$db = db();

// 1. Add discount_percent column if not exists
$addColumn = "ALTER TABLE `products` ADD COLUMN `discount_percent` DECIMAL(5,2) NULL AFTER `warranty_months`";
if (!$db->query($addColumn)) {
    if (strpos($db->error, 'Duplicate column name') === false) {
        echo "Error adding column: " . $db->error . "\n";
    } else {
        echo "Column discount_percent already exists.\n";
    }
} else {
    echo "Added discount_percent column.\n";
}

// 2. Modify foreign keys for cascade delete on order_items and pos_sale_items
function updateFk($table, $fkName, $column) {
    global $db;
    // Drop existing FK if exists
    $drop = "ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`";
    $db->query($drop); // ignore error if not exists
    // Add new FK with cascade
    $add = "ALTER TABLE `$table` ADD CONSTRAINT `$fkName` FOREIGN KEY (`$column`) REFERENCES `products`(`id`) ON DELETE CASCADE";
    if (!$db->query($add)) {
        echo "Error updating FK on $table: " . $db->error . "\n";
    } else {
        echo "Updated FK $fkName on $table with ON DELETE CASCADE.\n";
    }
}

updateFk('order_items', 'fk_oi_product', 'product_id');
updateFk('pos_sale_items', 'fk_psi_prod', 'product_id');

?>
