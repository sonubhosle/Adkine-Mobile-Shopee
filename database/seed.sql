
-- 1. Insert Roles
INSERT INTO `roles` (`id`, `slug`, `name`, `description`) VALUES
(1, 'admin', 'Super Administrator', 'Full access to ERP, financial reports, settings, and staff management'),
(2, 'technician', 'Repair Technician', 'Inspect, diagnose, update repair tickets, and allocate parts'),
(3, 'customer', 'Registered Customer', 'Browse store, place orders, book device repairs, track tickets');

-- 2. Insert Permissions
INSERT INTO `permissions` (`id`, `slug`, `name`, `module`) VALUES
(1, 'view_dashboard', 'View ERP Dashboard', 'dashboard'),
(2, 'manage_products', 'Manage Products & Variants', 'catalog'),
(3, 'manage_inventory', 'Manage Stock & Adjustments', 'inventory'),
(4, 'manage_suppliers', 'Manage Suppliers & Purchases', 'purchases'),
(5, 'manage_orders', 'Manage Customer Orders', 'orders'),
(6, 'manage_repairs', 'Manage All Repairs & Estimates', 'repairs'),
(7, 'technician_repairs', 'Access Technician Workbench', 'repairs'),
(8, 'use_pos', 'Operate POS Cash Register', 'pos'),
(9, 'view_reports', 'View Sales & Financial Reports', 'reports'),
(10, 'manage_staff', 'Manage Staff & Roles', 'staff'),
(11, 'manage_settings', 'Manage System Settings', 'settings');

-- Admin has all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10), (1, 11),
(2, 7);

-- 3. Insert Users (Password: Admin@12345, Tech@12345, Customer@12345)
INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `phone`, `password_hash`, `status`, `email_verified_at`) VALUES
(1, 1, 'Store Administrator', 'admin@mobileshop.com', '9876543210', '$2y$10$/Mib3OwL1Md5kK2Cd3fwiuzkZ/0dFCXNgcKm.Pby0YCT5SeUj6DVO', 'active', NOW()),
(2, 2, 'Vikram Sharma (Technician)', 'tech@mobileshop.com', '9876543211', '$2y$10$QqhxqR4SBJ3QncZn9QnmCuGeYMfRiq9jbUYtE2xibiImrl//4yoEy', 'active', NOW()),
(4, 3, 'John Doe', 'john@example.com', '9876543213', '$2y$10$63rIOT00.p8pCbZyNPJ20.rh37SYItxKSpwGnRyCNm6bhkGqXE6Eu', 'active', NOW()),
(5, 3, 'Priya Patel', 'priya@example.com', '9876543214', '$2y$10$63rIOT00.p8pCbZyNPJ20.rh37SYItxKSpwGnRyCNm6bhkGqXE6Eu', 'active', NOW());

