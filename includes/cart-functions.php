<?php
/**
 * MobileShopFlat — Cart Functions (Procedural Core PHP)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

session_init();

function cart_get_session_id(): string
{
    if (empty($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['cart_session_id'];
}

function cart_get_id(bool $createIfMissing = true): ?int
{
    static $requestCartId = null;
    if ($requestCartId !== null) {
        return $requestCartId;
    }

    if (!empty($_SESSION['cart_id'])) {
        $requestCartId = (int)$_SESSION['cart_id'];
        return $requestCartId;
    }

    $sid = cart_get_session_id();
    $userId = auth_id();

    $cart = db_fetch_one("SELECT id, user_id FROM cart WHERE session_id = ?", [$sid]);
    if ($cart) {
        if ($userId && empty($cart['user_id'])) {
            db_query("UPDATE cart SET user_id = ? WHERE id = ?", [$userId, $cart['id']]);
        }
        $requestCartId = (int)$cart['id'];
        $_SESSION['cart_id'] = $requestCartId;
        return $requestCartId;
    }

    if (!$createIfMissing) {
        return null;
    }

    $id = db_insert(
        "INSERT INTO cart (user_id, session_id) VALUES (?, ?)",
        [$userId, $sid]
    );
    $requestCartId = $id;
    $_SESSION['cart_id'] = $id;
    return $id;
}

function cart_add_item(int $productId, ?int $variantId = null, int $quantity = 1): array
{
    $cartId = cart_get_id();

    $product = db_fetch_one("SELECT id, name, base_price, sale_price, is_active FROM products WHERE id = ?", [$productId]);
    if (!$product || (int)$product['is_active'] !== 1) {
        return ['status' => false, 'message' => 'Product is currently unavailable.'];
    }

    $unitPrice = $product['sale_price'] !== null ? (float)$product['sale_price'] : (float)$product['base_price'];

    if (!$variantId) {
        $firstVariant = db_fetch_one("SELECT id, additional_price FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1", [$productId]);
        if ($firstVariant) {
            $variantId = (int)$firstVariant['id'];
        }
    }

    if ($variantId) {
        $variant = db_fetch_one("SELECT id, additional_price, is_active FROM product_variants WHERE id = ? AND product_id = ?", [$variantId, $productId]);
        if ($variant && (int)$variant['is_active'] === 1) {
            $unitPrice += (float)$variant['additional_price'];
        } else {
            return ['status' => false, 'message' => 'Selected product variant is unavailable.'];
        }
    }

    $inv = db_fetch_one(
        "SELECT current_stock FROM inventory WHERE product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL))",
        [$productId, $variantId, $variantId]
    );
    $stock = $inv ? (int)$inv['current_stock'] : 0;
    if ($stock < $quantity) {
        return ['status' => false, 'message' => "Only {$stock} units available in stock."];
    }

    $existing = db_fetch_one(
        "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL))",
        [$cartId, $productId, $variantId, $variantId]
    );

    if ($existing) {
        $newQty = (int)$existing['quantity'] + $quantity;
        if ($stock < $newQty) {
            return ['status' => false, 'message' => "Cannot add more. Maximum available stock is {$stock}."];
        }
        db_query("UPDATE cart_items SET quantity = ?, unit_price = ? WHERE id = ?", [$newQty, $unitPrice, $existing['id']]);
    } else {
        db_insert(
            "INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)",
            [$cartId, $productId, $variantId, $quantity, $unitPrice]
        );
    }

    cart_invalidate_cache();
    return ['status' => true, 'message' => 'Item added to your shopping cart.'];
}

function cart_update_item(int $cartItemId, int $quantity): array
{
    $cartId = cart_get_id();
    if ($quantity <= 0) {
        return cart_remove_item($cartItemId);
    }

    $item = db_fetch_one("SELECT product_id, variant_id FROM cart_items WHERE id = ? AND cart_id = ?", [$cartItemId, $cartId]);
    if (!$item) {
        return ['status' => false, 'message' => 'Cart item not found.'];
    }

    $inv = db_fetch_one(
        "SELECT current_stock FROM inventory WHERE product_id = ? AND (variant_id = ? OR (? IS NULL AND variant_id IS NULL))",
        [$item['product_id'], $item['variant_id'], $item['variant_id']]
    );
    $stock = $inv ? (int)$inv['current_stock'] : 0;
    if ($stock < $quantity) {
        return ['status' => false, 'message' => "Only {$stock} units available."];
    }

    db_query("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?", [$quantity, $cartItemId, $cartId]);
    cart_invalidate_cache();
    return ['status' => true, 'message' => 'Cart updated.'];
}

function cart_remove_item(int $cartItemId): array
{
    $cartId = cart_get_id();
    db_query("DELETE FROM cart_items WHERE id = ? AND cart_id = ?", [$cartItemId, $cartId]);
    cart_invalidate_cache();
    return ['status' => true, 'message' => 'Item removed from cart.'];
}

function cart_invalidate_cache(): void
{
    cart_get_details(true);
}

function cart_get_details(bool $forceRefresh = false): array
{
    static $memoized = null;
    if ($forceRefresh) {
        $memoized = null;
        return [];
    }
    if ($memoized !== null) {
        return $memoized;
    }

    $emptyCart = [
        'cart_id' => 0,
        'items' => [],
        'total_items' => 0,
        'subtotal' => 0.0,
        'discount_amount' => 0.0,
        'coupon' => null,
        'tax_amount' => 0.0,
        'shipping_fee' => 0.0,
        'grand_total' => 0.0,
    ];

    $cartId = cart_get_id(false);
    if (!$cartId) {
        $memoized = $emptyCart;
        return $memoized;
    }

    $sql = "SELECT ci.id as item_id, ci.quantity,
               COALESCE(p.sale_price, p.base_price) + COALESCE(pv.additional_price, 0) AS unit_price,
               p.id as product_id, p.name as product_name, p.slug as product_slug, p.thumbnail, p.sku,
               pv.id as variant_id, pv.variant_name, pv.sku as variant_sku,
               COALESCE(i.current_stock, 0) as available_stock
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            LEFT JOIN product_variants pv ON ci.variant_id = pv.id AND pv.product_id = p.id AND pv.is_active = 1
            LEFT JOIN inventory i ON (ci.product_id = i.product_id AND (ci.variant_id = i.variant_id OR (ci.variant_id IS NULL AND i.variant_id IS NULL)))
            WHERE ci.cart_id = ? AND p.is_active = 1 AND (ci.variant_id IS NULL OR pv.id IS NOT NULL)
            ORDER BY ci.id DESC";
    $items = db_fetch_all($sql, [$cartId]);

    $subtotal = 0.0;
    $totalItems = 0;

    foreach ($items as &$item) {
        $itemTotal = round((float)$item['unit_price'] * (int)$item['quantity'], 2);
        $item['item_total'] = $itemTotal;
        $subtotal += $itemTotal;
        $totalItems += (int)$item['quantity'];
    }

    $couponCode = $_SESSION['applied_coupon'] ?? null;
    $discountAmount = 0.0;
    $couponData = null;

    if ($couponCode) {
        $coupon = db_fetch_one(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND valid_from <= NOW() AND valid_until >= NOW()",
            [$couponCode]
        );

        if ($coupon && $subtotal >= (float)$coupon['min_order_amount']) {
            $couponData = $coupon;
            if ($coupon['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * (float)$coupon['discount_value']) / 100;
                if ($coupon['max_discount_amount'] !== null && $discountAmount > (float)$coupon['max_discount_amount']) {
                    $discountAmount = (float)$coupon['max_discount_amount'];
                }
            } else {
                $discountAmount = min($subtotal, (float)$coupon['discount_value']);
            }
        } else {
            unset($_SESSION['applied_coupon']);
        }
    }

    $taxableAmount = max(0, $subtotal - $discountAmount);
    $taxRate = 18.0;
    $discountAmount = round($discountAmount, 2);
    $taxAmount = round(($taxableAmount * $taxRate) / 100, 2);
    $shippingFee = $subtotal > 2000 || $totalItems === 0 ? 0.0 : 99.0;
    $grandTotal = round($taxableAmount + $taxAmount + $shippingFee, 2);

    $memoized = [
        'cart_id' => $cartId,
        'items' => $items,
        'total_items' => $totalItems,
        'subtotal' => $subtotal,
        'discount_amount' => $discountAmount,
        'coupon' => $couponData,
        'tax_amount' => $taxAmount,
        'shipping_fee' => $shippingFee,
        'grand_total' => $grandTotal,
    ];
    return $memoized;
}

function cart_apply_coupon(string $code): array
{
    $code = strtoupper(trim($code));
    $cart = cart_get_details();

    if ($cart['subtotal'] <= 0) {
        return ['status' => false, 'message' => 'Cart is empty.'];
    }

    $coupon = db_fetch_one(
        "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND valid_from <= NOW() AND valid_until >= NOW()",
        [$code]
    );

    if (!$coupon) {
        return ['status' => false, 'message' => 'Invalid or expired promo code.'];
    }

    if ((int)$coupon['usage_limit'] > 0 && (int)$coupon['usage_count'] >= (int)$coupon['usage_limit']) {
        return ['status' => false, 'message' => 'Coupon usage limit has been reached.'];
    }

    if ($cart['subtotal'] < (float)$coupon['min_order_amount']) {
        $minReq = format_currency($coupon['min_order_amount']);
        return ['status' => false, 'message' => "Coupon requires a minimum cart total of {$minReq}."];
    }

    $_SESSION['applied_coupon'] = $code;
    cart_invalidate_cache();
    return ['status' => true, 'message' => "Coupon '{$code}' applied successfully!"];
}

function cart_remove_coupon(): array
{
    unset($_SESSION['applied_coupon']);
    cart_invalidate_cache();
    return ['status' => true, 'message' => 'Coupon removed.'];
}

function cart_clear(): void
{
    $cartId = cart_get_id(false);
    if ($cartId) {
        db_query("DELETE FROM cart_items WHERE cart_id = ?", [$cartId]);
    }
    unset($_SESSION['applied_coupon']);
    cart_invalidate_cache();
}

