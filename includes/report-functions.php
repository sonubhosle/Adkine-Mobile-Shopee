<?php
/**
 * MobileShopFlat — Reporting and Business Analytics Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function report_get_dashboard_metrics(): array
{
    $startOfToday = date('Y-m-d 00:00:00');
    $startOfMonth = date('Y-m-01 00:00:00');

    // 1. Online Orders Stats (Uses index on created_at and payment_status)
    $orderStats = db_fetch_one("SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as total_order_revenue,
        COALESCE(SUM(CASE WHEN created_at >= ? AND payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as today_order_revenue,
        COALESCE(SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
        COALESCE(SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_orders
    FROM orders", [$startOfToday]);

    // 2. POS Sales Stats (Uses index on status, created_at)
    $posStats = db_fetch_one("SELECT 
        COUNT(*) as total_pos_sales,
        COALESCE(SUM(grand_total), 0) as total_pos_revenue,
        COALESCE(SUM(CASE WHEN created_at >= ? THEN grand_total ELSE 0 END), 0) as today_pos_revenue
    FROM pos_sales WHERE status = 'completed'", [$startOfToday]);

    // 3. Repair Stats
    $repairStats = db_fetch_one("SELECT 
        COUNT(*) as total_repairs,
        COALESCE(SUM(CASE WHEN status IN ('completed', 'ready_for_delivery') THEN final_cost ELSE advance_paid END), 0) as total_repair_revenue,
        COALESCE(SUM(CASE WHEN status NOT IN ('completed', 'cancelled') THEN 1 ELSE 0 END), 0) as active_repairs,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed_repairs
    FROM repairs");

    // 4. Customer Count
    $totalCustomers = (int)(db_fetch_value("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'customer'") ?? 0);

    // 5. Low Stock Count
    $lowStockCount = (int)(db_fetch_value("SELECT COUNT(*) FROM inventory WHERE current_stock <= min_stock_threshold") ?? 0);

    // 6. Total Expenses
    $totalExpenses = (float)(db_fetch_value("SELECT COALESCE(SUM(amount), 0) FROM expenses") ?? 0);

    $totalRevenue = (float)($orderStats['total_order_revenue'] ?? 0) + (float)($posStats['total_pos_revenue'] ?? 0) + (float)($repairStats['total_repair_revenue'] ?? 0);
    $todayRevenue = (float)($orderStats['today_order_revenue'] ?? 0) + (float)($posStats['today_pos_revenue'] ?? 0);
    
    // Monthly revenue queries optimized with sargable created_at >= ? range lookups
    $monthlyRevenue = (float)(db_fetch_value(
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND created_at >= ?",
        [$startOfMonth]
    ) ?? 0);
    $monthlyRevenue += (float)(db_fetch_value(
        "SELECT COALESCE(SUM(grand_total), 0) FROM pos_sales WHERE status = 'completed' AND created_at >= ?",
        [$startOfMonth]
    ) ?? 0);
    $monthlyRevenue += (float)(db_fetch_value(
        "SELECT COALESCE(SUM(CASE WHEN status IN ('completed', 'ready_for_delivery') THEN final_cost ELSE advance_paid END), 0) FROM repairs WHERE created_at >= ?",
        [$startOfMonth]
    ) ?? 0);

    return [
        'total_revenue' => $totalRevenue,
        'today_revenue' => $todayRevenue,
        'total_orders' => (int)($orderStats['total_orders'] ?? 0) + (int)($posStats['total_pos_sales'] ?? 0),
        'completed_orders' => (int)($orderStats['delivered_orders'] ?? 0) + (int)($posStats['total_pos_sales'] ?? 0),
        'online_orders' => (int)($orderStats['total_orders'] ?? 0),
        'pos_sales' => (int)($posStats['total_pos_sales'] ?? 0),
        'pending_orders' => (int)($orderStats['pending_orders'] ?? 0),
        'delivered_orders' => (int)($orderStats['delivered_orders'] ?? 0),
        'total_customers' => $totalCustomers,
        'total_repairs' => (int)($repairStats['total_repairs'] ?? 0),
        'active_repairs' => (int)($repairStats['active_repairs'] ?? 0),
        'completed_repairs' => (int)($repairStats['completed_repairs'] ?? 0),
        'repair_revenue' => (float)($repairStats['total_repair_revenue'] ?? 0),
        'monthly_revenue' => $monthlyRevenue,
        'low_stock_count' => $lowStockCount,
        'total_expenses' => $totalExpenses,
    ];
}

function report_get_daily_sales_trend(int $days = 30): array
{
    $startDate = date('Y-m-d 00:00:00', strtotime("-" . ($days - 1) . " days"));

    // 1. Single aggregated query for online orders (replaces 30 queries)
    $onlineRows = db_fetch_all(
        "SELECT DATE(created_at) AS sale_date, COALESCE(SUM(total_amount), 0) AS total
         FROM orders
         WHERE payment_status = 'paid' AND created_at >= ?
         GROUP BY DATE(created_at)",
        [$startDate]
    );
    $onlineByDate = array_column($onlineRows, 'total', 'sale_date');

    // 2. Single aggregated query for POS sales (replaces 30 queries)
    $posRows = db_fetch_all(
        "SELECT DATE(created_at) AS sale_date, COALESCE(SUM(grand_total), 0) AS total
         FROM pos_sales
         WHERE status = 'completed' AND created_at >= ?
         GROUP BY DATE(created_at)",
        [$startDate]
    );
    $posByDate = array_column($posRows, 'total', 'sale_date');

    $dates = [];
    $sales = [];

    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $displayDate = date('D, d M', strtotime($date));
        $dates[] = $displayDate;
        $sales[] = (float)($onlineByDate[$date] ?? 0) + (float)($posByDate[$date] ?? 0);
    }

    return [
        'labels' => $dates,
        'data' => $sales,
    ];
}

function report_get_category_sales_breakdown(): array
{
    $sql = "SELECT c.name as category_name, COALESCE(SUM(oi.total_price), 0) as revenue
            FROM categories c
            JOIN products p ON c.id = p.category_id
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.payment_status = 'paid'
            GROUP BY c.id
            ORDER BY revenue DESC
            LIMIT 5";

    $rows = db_fetch_all($sql);
    $labels = [];
    $data = [];

    foreach ($rows as $r) {
        $labels[] = $r['category_name'];
        $data[] = (float)$r['revenue'];
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
}

function report_get_top_products(int $limit = 5): array
{
    $sql = "SELECT p.name, p.thumbnail AS image, c.name as category_name, COUNT(DISTINCT oi.order_id) as order_count, SUM(oi.total_price) as revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON o.id = oi.order_id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.payment_status = 'paid'
            GROUP BY p.id
            ORDER BY order_count DESC
            LIMIT ?";
    return db_fetch_all($sql, [$limit]);
}

