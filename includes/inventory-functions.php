<?php
/**
 * MobileShopFlat — Inventory & Stock Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function inventory_deduct_stock(
    int $productId,
    ?int $variantId,
    int $quantity,
    string $movementType,
    string $referenceId,
    ?string $notes = null,
    ?int $userId = null
): bool {
    if ($quantity <= 0) {
        throw new \InvalidArgumentException("Deduction quantity must be greater than zero.");
    }

    db_begin();
    try {
        $product = db_fetch_one('SELECT id, cost_price FROM products WHERE id = ? FOR UPDATE', [$productId]);
        if (!$product) {
            throw new RuntimeException("Product #{$productId} does not exist.");
        }

        $sqlCheck = "SELECT id, current_stock FROM inventory WHERE product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL)) FOR UPDATE";
        $row = db_fetch_one($sqlCheck, [$productId, $variantId, $variantId]);
        if (!$row || (int)$row['current_stock'] < $quantity) {
            $available = $row ? (int)$row['current_stock'] : 0;
            throw new RuntimeException("Insufficient stock available for product #{$productId}. Requested: {$quantity}, Available: {$available}.");
        }

        $affected = db_query(
            'UPDATE inventory SET current_stock = current_stock - ? WHERE id = ? AND current_stock >= ?',
            [$quantity, (int)$row['id'], $quantity]
        );
        if ($affected !== 1) {
            throw new RuntimeException('Stock concurrency race condition detected. Deduction aborted.');
        }

        db_query(
            "INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, cost_per_unit, reference_id, reference_type, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, 'system', ?, ?)",
            [$productId, $variantId, $movementType, -$quantity, (float)$product['cost_price'], $referenceId, $notes, $userId]
        );

        db_commit();
        return true;
    } catch (\Throwable $e) {
        db_rollback();
        throw $e;
    }
}

function inventory_add_stock(
    int $productId,
    ?int $variantId,
    int $quantity,
    string $movementType,
    string $referenceId,
    float $unitCost = 0.0,
    ?string $notes = null,
    ?int $userId = null
): bool {
    if ($quantity <= 0) {
        throw new \InvalidArgumentException("Addition quantity must be greater than zero.");
    }

    db_begin();
    try {
        $product = db_fetch_one('SELECT id FROM products WHERE id = ? FOR UPDATE', [$productId]);
        if (!$product) {
            throw new RuntimeException("Product #{$productId} does not exist.");
        }

        $sqlCheck = "SELECT id FROM inventory WHERE product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL)) FOR UPDATE";
        $row = db_fetch_one($sqlCheck, [$productId, $variantId, $variantId]);
        if ($row) {
            db_query("UPDATE inventory SET current_stock = current_stock + ? WHERE id = ?", [$quantity, $row['id']]);
        } else {
            db_insert("INSERT INTO inventory (product_id, variant_id, current_stock, min_stock_threshold) VALUES (?, ?, ?, 5)", [$productId, $variantId, $quantity]);
        }

        db_query(
            "INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, cost_per_unit, reference_id, reference_type, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, 'system', ?, ?)",
            [$productId, $variantId, $movementType, $quantity, $unitCost, $referenceId, $notes, $userId]
        );

        db_commit();
        return true;
    } catch (\Throwable $e) {
        db_rollback();
        throw $e;
    }
}

function inventory_adjust_stock(int $productId, ?int $variantId, int $newStock, string $reason, ?int $userId = null): bool
{
    if ($newStock < 0) {
        throw new \InvalidArgumentException('Stock cannot be negative.');
    }

    db_begin();
    try {
        $product = db_fetch_one('SELECT id FROM products WHERE id = ? FOR UPDATE', [$productId]);
        if (!$product) {
            throw new RuntimeException("Product #{$productId} does not exist.");
        }

        $sqlCheck = "SELECT id, current_stock FROM inventory WHERE product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL)) FOR UPDATE";
        $row = db_fetch_one($sqlCheck, [$productId, $variantId, $variantId]);

        $currentStock = $row ? (int)$row['current_stock'] : 0;
        $diff = $newStock - $currentStock;

        if ($diff === 0) {
            db_commit();
            return true;
        }

        if ($row) {
            db_query("UPDATE inventory SET current_stock = ? WHERE id = ?", [$newStock, (int)$row['id']]);
        } else {
            db_insert(
                "INSERT INTO inventory (product_id, variant_id, current_stock, min_stock_threshold) VALUES (?, ?, ?, 5)",
                [$productId, $variantId, $newStock]
            );
        }

        $movementType = $diff > 0 ? 'adjustment_add' : 'adjustment_sub';
        $sqlMovement = "INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, cost_per_unit, reference_id, reference_type, notes, created_by) VALUES (?, ?, ?, ?, 0.00, ?, 'adjustment', ?, ?)";
        db_query($sqlMovement, [$productId, $variantId, $movementType, $diff, 'ADJUST-' . time(), $reason, $userId]);

        db_commit();
        return true;
    } catch (\Throwable $e) {
        db_rollback();
        throw $e;
    }
}

function inventory_get_low_stock_alerts(): array
{
    $sql = "SELECT i.*, p.name as product_name, p.sku as product_sku, p.thumbnail, c.name as category_name, b.name as brand_name, pv.variant_name
            FROM inventory i
            JOIN products p ON i.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN product_variants pv ON i.variant_id = pv.id
            WHERE i.current_stock <= i.min_stock_threshold
            ORDER BY i.current_stock ASC";
    return db_fetch_all($sql);
}
