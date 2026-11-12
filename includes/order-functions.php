<?php
/**
 * MobileShopFlat — Order Lifecycle & Fulfillment Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cart-functions.php';
require_once __DIR__ . '/inventory-functions.php';

function order_address_belongs_to_user(int $userId, int $addressId): bool
{
    if ($userId <= 0 || $addressId <= 0) {
        return false;
    }

    return db_fetch_one(
        "SELECT id FROM addresses WHERE id = ? AND user_id = ? LIMIT 1",
        [$addressId, $userId]
    ) !== null;
}

function order_create(
    int $userId,
    int $shippingAddressId,
    int $billingAddressId,
    string $paymentMethod,
    string $paymentStatus = 'pending',
    ?string $razorpayOrderId = null,
    ?string $razorpayPaymentId = null,
    ?string $notes = null
): array {
    $cart = cart_get_details();
    if (empty($cart['items'])) {
        throw new RuntimeException("Cannot place order with an empty shopping cart.");
    }

    if (
        !order_address_belongs_to_user($userId, $shippingAddressId)
        || !order_address_belongs_to_user($userId, $billingAddressId)
    ) {
        throw new RuntimeException("The selected address is invalid.");
    }

    db_begin();
    try {
        $orderNumber = 'ORD-' . date('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $couponId = !empty($cart['coupon']) ? (int)$cart['coupon']['id'] : null;

        $sqlOrder = "INSERT INTO orders (
            order_number, user_id, shipping_address_id, billing_address_id,
            subtotal, discount_amount, coupon_id, tax_amount, shipping_fee,
            total_amount, payment_method, payment_status, order_status,
            shipping_notes, razorpay_order_id, razorpay_payment_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)";

        $orderId = db_insert($sqlOrder, [
            $orderNumber,
            $userId,
            $shippingAddressId,
            $billingAddressId,
            $cart['subtotal'],
            $cart['discount_amount'],
            $couponId,
            $cart['tax_amount'],
            $cart['shipping_fee'],
            $cart['grand_total'],
            $paymentMethod,
            $paymentStatus,
            $notes,
            $razorpayOrderId,
            $razorpayPaymentId,
        ]);

        foreach ($cart['items'] as $item) {
            $sqlItem = "INSERT INTO order_items (
                order_id, product_id, variant_id, product_name, variant_name,
                sku, unit_price, quantity, total_price
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            db_insert($sqlItem, [
                $orderId,
                $item['product_id'],
                $item['variant_id'],
                $item['product_name'],
                $item['variant_name'],
                $item['sku'],
                $item['unit_price'],
                $item['quantity'],
                $item['item_total'],
            ]);

            try {
                inventory_deduct_stock(
                    (int)$item['product_id'],
                    $item['variant_id'] ? (int)$item['variant_id'] : null,
                    (int)$item['quantity'],
                    'online_order',
                    $orderNumber,
                    "Order fulfillment #{$orderNumber}",
                    $userId
                );
            } catch (\Throwable $e) {
                // Keep order flow resilient if inventory tracking record fails
                error_log("Stock deduction notice: " . $e->getMessage());
            }
        }

        if ($couponId) {
            db_query("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?", [$couponId]);
        }

        db_query(
            "INSERT INTO order_status_history (order_id, old_status, new_status, comment, changed_by) VALUES (?, NULL, 'pending', 'Order placed successfully by customer.', ?)",
            [$orderId, $userId]
        );

        $invoiceNumber = 'INV-' . date('Ym') . '-' . sprintf('%04d', $orderId);
        db_insert(
            "INSERT INTO invoices (invoice_number, invoice_type, reference_id, user_id, subtotal, tax_amount, discount_amount, grand_total, status) VALUES (?, 'order', ?, ?, ?, ?, ?, ?, 'issued')",
            [
                $invoiceNumber,
                $orderId,
                $userId,
                $cart['subtotal'],
                $cart['tax_amount'],
                $cart['discount_amount'],
                $cart['grand_total'],
            ]
        );

        if ($paymentStatus === 'paid') {
            db_insert(
                "INSERT INTO payments (order_id, transaction_ref, payment_method, gateway, amount, currency, status) VALUES (?, ?, ?, ?, ?, 'INR', 'captured')",
                [
                    $orderId,
                    $razorpayPaymentId ?? ('TXN-' . time()),
                    $paymentMethod,
                    $paymentMethod === 'razorpay' ? 'razorpay' : 'cash',
                    $cart['grand_total'],
                ]
            );
        }

        cart_clear();
        db_commit();

        return [
            'status' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'invoice_number' => $invoiceNumber,
        ];
    } catch (\Throwable $e) {
        db_rollback();
        throw $e;
    }
}
