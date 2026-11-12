-- ==============================================================
-- Mobile Shop E-Commerce, Repair & Inventory ERP System
-- Normalized Database Schema (MySQL 8+ / MariaDB 10.4+)
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles Table
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `user_otps`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `webhook_events`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `refunds`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `pos_sale_items`;
DROP TABLE IF EXISTS `pos_sales`;
DROP TABLE IF EXISTS `repair_parts`;
DROP TABLE IF EXISTS `repair_history`;
DROP TABLE IF EXISTS `repair_estimates`;
DROP TABLE IF EXISTS `repairs`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `purchase_items`;
DROP TABLE IF EXISTS `purchases`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `addresses`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Permissions Table
CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role Permissions Pivot Table
CREATE TABLE `role_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users Table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `status` ENUM('active', 'pending_otp', 'suspended') DEFAULT 'active',
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `avatar` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_status` (`status`),
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. User OTPs Table
CREATE TABLE `user_otps` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `email` VARCHAR(191) NOT NULL,
    `otp_code` VARCHAR(255) NOT NULL,
    `type` ENUM('registration', 'password_reset', 'login') NOT NULL,
    `attempts` TINYINT UNSIGNED DEFAULT 0,
    `expires_at` TIMESTAMP NOT NULL,
    `is_used` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_otps_lookup` (`email`, `type`, `is_used`),
    CONSTRAINT `fk_user_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Login Rate Limiting Table
CREATE TABLE `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `attempts` INT UNSIGNED DEFAULT 1,
    `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_ip_email` (`ip_address`, `email`),
    KEY `idx_locked_until` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Audit Logs Table
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(100) NOT NULL,
    `record_id` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `details` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_module_action` (`module`, `action`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Categories Table
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT UNSIGNED NULL,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `display_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_category_parent` (`parent_id`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Brands Table
CREATE TABLE `brands` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `logo` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Products Table
CREATE TABLE `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NOT NULL,
    `brand_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `sku` VARCHAR(100) NOT NULL UNIQUE,
    `barcode` VARCHAR(100) NULL UNIQUE,
    `model_number` VARCHAR(100) NULL,
    `short_desc` VARCHAR(500) NULL,
    `description` LONGTEXT NULL,
    `specifications` JSON NULL,
    `thumbnail` VARCHAR(255) NULL,
    `images` JSON NULL,
    `base_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `sale_price` DECIMAL(12,2) NULL,
    `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `has_variants` TINYINT(1) DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `warranty_months` INT UNSIGNED DEFAULT 12,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_prod_category` (`category_id`),
    KEY `idx_prod_brand` (`brand_id`),
    KEY `idx_prod_featured` (`is_featured`),
    KEY `idx_prod_active` (`is_active`),
    CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Product Variants Table
CREATE TABLE `product_variants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `sku` VARCHAR(100) NOT NULL UNIQUE,
    `barcode` VARCHAR(100) NULL UNIQUE,
    `variant_name` VARCHAR(200) NOT NULL,
    `attributes` JSON NULL, -- e.g. {"storage":"128GB", "color":"Phantom Black"}
    `additional_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `image` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_variant_product` (`product_id`),
    CONSTRAINT `fk_variant_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Product Reviews Table
CREATE TABLE `product_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NULL,
    `review_text` TEXT NOT NULL,
    `is_verified_purchase` TINYINT(1) DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_reviews_product` (`product_id`),
    KEY `idx_reviews_user` (`user_id`),
    CONSTRAINT `fk_review_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Inventory Table
CREATE TABLE `inventory` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `current_stock` INT NOT NULL DEFAULT 0,
    `min_stock_threshold` INT NOT NULL DEFAULT 5,
    `max_stock_threshold` INT NOT NULL DEFAULT 100,
    `location_rack` VARCHAR(100) NULL DEFAULT 'Main Display',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_prod_variant_inv` (`product_id`, `variant_id`),
    KEY `idx_inv_low_stock` (`current_stock`, `min_stock_threshold`),
    CONSTRAINT `fk_inv_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Suppliers Table
CREATE TABLE `suppliers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `company_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(30) NOT NULL,
    `address` TEXT NULL,
    `gstin` VARCHAR(30) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Purchases (Stock-In) Table
CREATE TABLE `purchases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT UNSIGNED NOT NULL,
    `purchase_number` VARCHAR(50) NOT NULL UNIQUE,
    `invoice_number` VARCHAR(100) NULL,
    `purchase_date` DATE NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_status` ENUM('paid', 'partial', 'pending') DEFAULT 'paid',
    `status` ENUM('draft', 'received', 'cancelled') NOT NULL DEFAULT 'draft',
    `received_at` TIMESTAMP NULL DEFAULT NULL,
    `received_by` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_purchase_supplier` (`supplier_id`),
    KEY `idx_purchase_status` (`status`),
    CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_purchase_receiver` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_purchase_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Purchase Items Table
CREATE TABLE `purchase_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `purchase_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `quantity` INT NOT NULL,
    `unit_cost` DECIMAL(12,2) NOT NULL,
    `total_cost` DECIMAL(12,2) NOT NULL,
    KEY `idx_pi_purchase` (`purchase_id`),
    CONSTRAINT `fk_pi_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Stock Movements (Audit Trail of all inventory additions & deductions)
CREATE TABLE `stock_movements` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `movement_type` ENUM('purchase_in', 'pos_sale', 'online_order', 'repair_use', 'damage_out', 'adjustment_add', 'adjustment_sub', 'return_in') NOT NULL,
    `quantity` INT NOT NULL, -- positive for additions, negative for deductions
    `cost_per_unit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `reference_id` VARCHAR(100) NULL, -- Order #, POS #, Repair #, Purchase #
    `reference_type` VARCHAR(50) NULL,
    `notes` VARCHAR(255) NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_sm_prod` (`product_id`),
    KEY `idx_sm_variant` (`variant_id`),
    KEY `idx_sm_type` (`movement_type`),
    CONSTRAINT `fk_sm_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sm_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Customer Addresses Table
CREATE TABLE `addresses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address_line1` VARCHAR(255) NOT NULL,
    `address_line2` VARCHAR(255) NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `type` ENUM('shipping', 'billing') DEFAULT 'shipping',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_address_user` (`user_id`),
    CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Coupons Table
CREATE TABLE `coupons` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `discount_value` DECIMAL(12,2) NOT NULL,
    `min_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `max_discount_amount` DECIMAL(12,2) NULL,
    `usage_limit` INT UNSIGNED DEFAULT 1000,
    `usage_count` INT UNSIGNED DEFAULT 0,
    `valid_from` DATETIME NOT NULL,
    `valid_until` DATETIME NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_coupon_code` (`code`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Shopping Cart Table
CREATE TABLE `cart` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `session_id` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cart_session` (`session_id`),
    KEY `idx_cart_user` (`user_id`),
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Cart Items Table
CREATE TABLE `cart_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cart_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cart_item` (`cart_id`, `product_id`, `variant_id`),
    CONSTRAINT `fk_ci_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ci_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ci_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Customer Orders Table
CREATE TABLE `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NOT NULL,
    `shipping_address_id` INT UNSIGNED NULL,
    `billing_address_id` INT UNSIGNED NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `coupon_id` INT UNSIGNED NULL,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `shipping_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('razorpay', 'cod', 'pos_cash', 'pos_card', 'pos_upi') NOT NULL DEFAULT 'razorpay',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned') NOT NULL DEFAULT 'pending',
    `shipping_notes` TEXT NULL,
    `razorpay_order_id` VARCHAR(100) NULL,
    `razorpay_payment_id` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_orders_user` (`user_id`),
    KEY `idx_orders_status` (`order_status`),
    KEY `idx_orders_payment_status` (`payment_status`),
    UNIQUE KEY `uk_orders_razorpay_order` (`razorpay_order_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_orders_ship_addr` FOREIGN KEY (`shipping_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_orders_bill_addr` FOREIGN KEY (`billing_address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Order Items Table
CREATE TABLE `order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `variant_name` VARCHAR(200) NULL,
    `sku` VARCHAR(100) NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `total_price` DECIMAL(12,2) NOT NULL,
    KEY `idx_oi_order` (`order_id`),
    CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_oi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Order Status History Table
CREATE TABLE `order_status_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `old_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `comment` VARCHAR(255) NULL,
    `changed_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_osh_order` (`order_id`),
    CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_osh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Mobile Repairs Table
CREATE TABLE `repairs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repair_ticket_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT UNSIGNED NULL,
    `customer_name` VARCHAR(150) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(191) NOT NULL,
    `device_brand` VARCHAR(100) NOT NULL,
    `device_model` VARCHAR(100) NOT NULL,
    `device_imei_serial` VARCHAR(100) NULL,
    `device_password_pin` VARCHAR(50) NULL,
    `issue_type` ENUM('screen_damage', 'battery_replacement', 'charging_port', 'water_damage', 'camera_issue', 'speaker_mic', 'motherboard_repair', 'software_os', 'other') NOT NULL,
    `issue_description` TEXT NOT NULL,
    `condition_notes` VARCHAR(255) NULL,
    `device_images` JSON NULL,
    `service_mode` ENUM('pickup', 'shop_visit') NOT NULL DEFAULT 'shop_visit',
    `pickup_address` TEXT NULL,
    `assigned_technician_id` INT UNSIGNED NULL,
    `estimated_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `advance_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `final_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `balance_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('received', 'diagnosis_pending', 'estimate_sent', 'estimate_approved', 'in_repair', 'waiting_parts', 'qa_testing', 'ready_for_delivery', 'completed', 'cancelled') NOT NULL DEFAULT 'received',
    `customer_approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `customer_approval_at` TIMESTAMP NULL DEFAULT NULL,
    `priority` ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
    `razorpay_order_id` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_repair_user` (`user_id`),
    KEY `idx_repair_status` (`status`),
    KEY `idx_repair_tech` (`assigned_technician_id`),
    CONSTRAINT `fk_rep_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rep_tech` FOREIGN KEY (`assigned_technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Repair Estimates Table
CREATE TABLE `repair_estimates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repair_id` INT UNSIGNED NOT NULL,
    `diagnostic_notes` TEXT NOT NULL,
    `spare_parts_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `labour_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_estimate` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'sent', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `responded_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_re_repair` (`repair_id`),
    CONSTRAINT `fk_re_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Repair History / Notes Table
CREATE TABLE `repair_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repair_id` INT UNSIGNED NOT NULL,
    `status_from` VARCHAR(50) NULL,
    `status_to` VARCHAR(50) NOT NULL,
    `notes` TEXT NULL,
    `images` JSON NULL,
    `updated_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rh_repair` (`repair_id`),
    CONSTRAINT `fk_rh_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rh_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Repair Parts Used Table
CREATE TABLE `repair_parts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `repair_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NULL,
    `variant_id` INT UNSIGNED NULL,
    `part_name` VARCHAR(200) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `added_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rp_repair` (`repair_id`),
    CONSTRAINT `fk_rp_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rp_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_rp_user` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. POS Sales Table
CREATE TABLE `pos_sales` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `cashier_id` INT UNSIGNED NOT NULL,
    `customer_name` VARCHAR(150) NULL DEFAULT 'Walk-in Customer',
    `customer_phone` VARCHAR(20) NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- Combined CGST+SGST
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('cash', 'card', 'upi', 'split') NOT NULL DEFAULT 'cash',
    `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `change_returned` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('completed', 'returned', 'voided') NOT NULL DEFAULT 'completed',
    `notes` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_pos_cashier` (`cashier_id`),
    CONSTRAINT `fk_pos_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. POS Sale Items Table
CREATE TABLE `pos_sale_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pos_sale_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `variant_name` VARCHAR(200) NULL,
    `sku` VARCHAR(100) NOT NULL,
    `unit_price` DECIMAL(12,2) NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 18.00, -- 18% GST
    `total_price` DECIMAL(12,2) NOT NULL,
    KEY `idx_psi_sale` (`pos_sale_id`),
    CONSTRAINT `fk_psi_sale` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_psi_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_psi_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. Unified Invoices Table
CREATE TABLE `invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `invoice_type` ENUM('order', 'repair', 'pos') NOT NULL,
    `reference_id` INT UNSIGNED NOT NULL, -- points to order_id, repair_id, or pos_sale_id
    `user_id` INT UNSIGNED NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `pdf_path` VARCHAR(255) NULL,
    `status` ENUM('issued', 'paid', 'cancelled') NOT NULL DEFAULT 'issued',
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_inv_type_ref` (`invoice_type`, `reference_id`),
    KEY `idx_inv_user` (`user_id`),
    CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. Payments Table
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NULL,
    `repair_id` INT UNSIGNED NULL,
    `pos_sale_id` INT UNSIGNED NULL,
    `transaction_ref` VARCHAR(100) NOT NULL UNIQUE,
    `payment_method` VARCHAR(50) NOT NULL,
    `gateway` ENUM('razorpay', 'cash', 'card', 'upi') NOT NULL DEFAULT 'razorpay',
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
    `status` ENUM('pending', 'captured', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `gateway_response` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_pay_order` (`order_id`),
    KEY `idx_pay_repair` (`repair_id`),
    KEY `idx_pay_pos` (`pos_sale_id`),
    CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_pos` FOREIGN KEY (`pos_sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 33. Refunds Table
CREATE TABLE `refunds` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `gateway_refund_id` VARCHAR(100) NULL,
    `status` ENUM('pending', 'processed', 'failed') NOT NULL DEFAULT 'pending',
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_refund_payment` (`payment_id`),
    CONSTRAINT `fk_ref_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ref_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ref_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 34. Expenses Table
CREATE TABLE `expenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category` ENUM('rent', 'electricity', 'salaries', 'marketing', 'equipment', 'tools', 'repair_parts', 'maintenance', 'other') NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('cash', 'bank_transfer', 'upi', 'card') NOT NULL DEFAULT 'cash',
    `expense_date` DATE NOT NULL,
    `notes` TEXT NULL,
    `receipt_image` VARCHAR(255) NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_exp_category` (`category`),
    KEY `idx_exp_date` (`expense_date`),
    CONSTRAINT `fk_exp_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 35. Webhook Events Table (Idempotent Webhook Processing)
CREATE TABLE `webhook_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `gateway` VARCHAR(50) NOT NULL DEFAULT 'razorpay',
    `event_id` VARCHAR(100) NOT NULL UNIQUE,
    `event_type` VARCHAR(100) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `status` ENUM('processed', 'ignored', 'failed') NOT NULL DEFAULT 'processed',
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_we_gateway_type` (`gateway`, `event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 36. Settings Table
CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT NULL,
    `is_autoload` TINYINT(1) DEFAULT 1,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_setting_group` (`setting_group`, `is_autoload`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

