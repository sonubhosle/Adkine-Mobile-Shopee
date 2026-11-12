<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/inventory-functions.php';

session_init();
require_role('admin', 'manager', 'technician');

$repairId = (int)($_POST['repair_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['quantity'] ?? 1));

if ($repairId > 0 && $productId > 0 && verify_csrf()) {
    $product = db_fetch_one("SELECT name, sale_price, base_price FROM products WHERE id = ?", [$productId]);
    $price = (float)($product['sale_price'] ?? $product['base_price']);
    $total = $price * $qty;

    $cost = (float)($product['cost_price'] ?? ($price * 0.7));

    db_insert(
        "INSERT INTO repair_parts (repair_id, product_id, part_name, quantity, unit_cost, unit_price, added_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$repairId, $productId, $product['name'], $qty, $cost, $price, auth_id()]
    );

    try {
        inventory_deduct_stock(
            $productId,
            null,
            $qty,
            'repair_part',
            (string)$repairId,
            "Allocated for repair ticket #{$repairId}",
            auth_id()
        );
    } catch (\Throwable $e) {
        error_log("Part deduction error: " . $e->getMessage());
    }

    set_flash('success', "Spare part '{$product['name']}' allocated to this repair ticket.");
}

redirect('/technician/repair-detail.php?id=' . $repairId);

