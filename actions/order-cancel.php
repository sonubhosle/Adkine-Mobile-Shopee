<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/inventory-functions.php';

session_init();
require_auth();

$userId = auth_id();
$orderId = (int)($_POST['order_id'] ?? 0);

if ($orderId > 0 && verify_csrf()) {
    $order = db_fetch_one("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?", [$orderId, $userId]);
    if ($order && $order['order_status'] === 'pending') {
        db_query("UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ?", [$orderId]);
        db_query("INSERT INTO order_status_history (order_id, old_status, new_status, comment, changed_by) VALUES (?, 'pending', 'cancelled', 'Cancelled by customer.', ?)", [$orderId, $userId]);

        $items = db_fetch_all("SELECT product_id, variant_id, quantity, unit_price FROM order_items WHERE order_id = ?", [$orderId]);
        foreach ($items as $item) {
            inventory_add_stock(
                (int)$item['product_id'],
                $item['variant_id'] ? (int)$item['variant_id'] : null,
                (int)$item['quantity'],
                'order_cancellation',
                'ORD-' . $orderId,
                (float)$item['unit_price'],
                'Restocked on order cancellation by customer',
                $userId
            );
        }

        set_flash('info', 'Your order has been cancelled.');
    } else {
        set_flash('error', 'This order cannot be cancelled.');
    }
}

redirect('/orders.php');

