-- ==============================================================
-- Mobile Shop E-Commerce, Repair & Inventory ERP System
-- Comprehensive Seed Data
-- ==============================================================

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

-- 4. Insert Categories
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `image`, `display_order`, `is_active`) VALUES
(1, NULL, 'Smartphones', 'smartphones', 'Flagship, premium and budget smartphones from top global brands', 'cat_smartphones.jpg', 1, 1),
(2, NULL, 'Chargers & Adapters', 'chargers-adapters', 'Fast GaN chargers, wireless pads, and original USB-C adapters', 'cat_chargers.jpg', 2, 1),
(3, NULL, 'Earphones & Audio', 'earphones-audio', 'TWS earbuds, noise-cancelling headphones and studio audio', 'cat_audio.jpg', 3, 1),
(4, NULL, 'Bluetooth Devices', 'bluetooth-devices', 'Bluetooth portable speakers, car transmitters and transmitters', 'cat_bluetooth.jpg', 4, 1),
(5, NULL, 'Smartwatches & Bands', 'smartwatches', 'Health tracking smartwatches, fitness bands and accessories', 'cat_smartwatches.jpg', 5, 1),
(6, NULL, 'Mobile Covers & Cases', 'mobile-covers', 'Shockproof armor, slim silicone, and premium leather cases', 'cat_covers.jpg', 6, 1),
(7, NULL, 'Tempered Glass & Protection', 'tempered-glass', '9H scratch-resistant tempered glass and privacy protectors', 'cat_tempered.jpg', 7, 1),
(8, NULL, 'Batteries & Power Banks', 'batteries-powerbanks', 'High-capacity fast-charging power banks and original batteries', 'cat_powerbanks.jpg', 8, 1),
(9, NULL, 'Repair Spare Parts', 'spare-parts', 'OEM displays, camera modules, charging ports, and flex cables', 'cat_spares.jpg', 9, 1);

-- 5. Insert Brands
INSERT INTO `brands` (`id`, `name`, `slug`, `logo`, `is_active`) VALUES
(1, 'Apple', 'apple', 'apple.png', 1),
(2, 'Samsung', 'samsung', 'samsung.png', 1),
(3, 'Google', 'google', 'google.png', 1),
(4, 'OnePlus', 'oneplus', 'oneplus.png', 1),
(5, 'Xiaomi', 'xiaomi', 'xiaomi.png', 1),
(6, 'Realme', 'realme', 'realme.png', 1),
(7, 'boAt', 'boat', 'boat.png', 1),
(8, 'Anker', 'anker', 'anker.png', 1),
(9, 'Spigen', 'spigen', 'spigen.png', 1);

-- 6. Insert Suppliers
INSERT INTO `suppliers` (`id`, `name`, `company_name`, `email`, `phone`, `address`, `gstin`, `is_active`) VALUES
(1, 'Sanjay Gupta', 'Apex Global Electronics Wholesalers', 'orders@apexwholesalers.in', '+91 9820011223', 'Shop 42, Electronic Market, Lamington Road, Mumbai 400007', '27AABCU9603R1ZM', 1),
(2, 'Ramesh Nair', 'QuickFix OEM Spare Parts Hub', 'supplies@quickfixspares.com', '+91 9845099887', 'Plot 18, Industrial Estate, Peenya, Bengaluru 560058', '29AAACQ1234P1Z4', 1),
(3, 'Kavita Joshi', 'Prime Mobile Accessories Direct', 'wholesale@primeaccessories.in', '+91 9811223344', 'Building 12, Nehru Place, New Delhi 110019', '07AABCP5678M1Z8', 1);

-- 7. Insert Products
INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `slug`, `sku`, `barcode`, `model_number`, `short_desc`, `description`, `specifications`, `thumbnail`, `base_price`, `sale_price`, `cost_price`, `has_variants`, `is_featured`, `is_active`, `warranty_months`) VALUES
(1, 1, 1, 'Apple iPhone 15 Pro Max', 'apple-iphone-15-pro-max', 'IPH15PM-BASE', '8901234567890', 'A3106', 'Forged in titanium, Super Retina XDR display with ProMotion, A17 Pro Bionic chip, and 5x optical zoom camera.', '<p>iPhone 15 Pro Max is the first iPhone to feature an aerospace-grade titanium design, using the same alloy that spacecraft use for missions to Mars. The A17 Pro chip brings next-level gaming performance and camera capabilities.</p>', '{"display": "6.7-inch Super Retina XDR OLED", "processor": "A17 Pro", "camera": "48MP Main + 12MP Ultra-wide + 12MP 5x Telephoto", "battery": "4422 mAh", "os": "iOS 17"}', 'iphone15promax.jpg', 159900.00, 149900.00, 125000.00, 1, 1, 1, 12),

(2, 1, 2, 'Samsung Galaxy S24 Ultra 5G', 'samsung-galaxy-s24-ultra', 'SMS24U-BASE', '8901234567891', 'SM-S928B', 'Galaxy AI is here. Titanium exterior, 200MP Quad Telephoto camera with 100x Space Zoom, and built-in S Pen.', '<p>Meet Galaxy S24 Ultra, the ultimate form of Galaxy Ultra with a new titanium exterior and a 6.8-inch flat display. Powered by Snapdragon 8 Gen 3 for Galaxy with integrated AI search and live translate.</p>', '{"display": "6.8-inch Dynamic AMOLED 2X, 120Hz", "processor": "Snapdragon 8 Gen 3 for Galaxy", "camera": "200MP + 50MP + 12MP + 10MP", "battery": "5000 mAh 45W Fast Charging", "spen": "Integrated S Pen"}', 's24ultra.jpg', 134999.00, 129999.00, 110000.00, 1, 1, 1, 12),

(3, 1, 4, 'OnePlus 12 5G', 'oneplus-12-5g', 'OP12-BASE', '8901234567892', 'CPH2573', 'Snapdragon 8 Gen 3, 4th Gen Hasselblad Camera, 2K 120Hz ProXDR display with 5400 nits peak brightness.', '<p>The OnePlus 12 redefines smooth performance with cutting-edge hardware, revolutionary Trinity Engine, and 100W SUPERVOOC ultra-fast flash charging that hits 100% in 26 minutes.</p>', '{"display": "6.82-inch 2K ProXDR 120Hz AMOLED", "processor": "Snapdragon 8 Gen 3", "camera": "50MP Sony LYT-808 + 64MP 3x Periscope + 48MP UW", "battery": "5400 mAh with 100W Wired + 50W Wireless"}', 'oneplus12.jpg', 69999.00, 64999.00, 54000.00, 1, 1, 1, 12),

(4, 1, 3, 'Google Pixel 8 Pro', 'google-pixel-8-pro', 'GP8P-BASE', '8901234567893', 'GC3VE', 'Google Tensor G3, advanced Google AI, Pro camera system with Best Take and Magic Editor.', '<p>Pixel 8 Pro is engineered by Google with state-of-the-art photography features, real-time audio translation, temperature sensor, and guaranteed 7 years of Android OS and security updates.</p>', '{"display": "6.7-inch Super Actua LTPO OLED 120Hz", "processor": "Google Tensor G3", "camera": "50MP Octa PD + 48MP UW + 48MP 5x Telephoto", "battery": "5050 mAh"}', 'pixel8pro.jpg', 106999.00, 98999.00, 85000.00, 0, 1, 1, 12),

(5, 2, 8, 'Anker Prime 67W GaN Wall Charger', 'anker-prime-67w-gan-charger', 'ANK-GAN-67W', '8901234567894', 'A2669', '3-Port ultra-compact high-speed wall charger with PowerIQ 4.0 for smartphones and laptops.', '<p>Charge 3 devices simultaneously at ultra-fast speeds. Powered by Anker GaNPrime technology with active temperature monitoring 3 million times per day.</p>', '{"ports": "2x USB-C + 1x USB-A", "output": "67W Max Total", "technology": "GaNPrime + ActiveShield 2.0"}', 'anker_67w.jpg', 3999.00, 3499.00, 2200.00, 0, 1, 1, 24),

(6, 2, 1, 'Apple 20W USB-C Power Adapter', 'apple-20w-usb-c-power-adapter', 'APL-20W-PWR', '8901234567895', 'MHJE3HN/A', 'Original Apple 20W USB-C power adapter offering fast, efficient charging at home or in the office.', '<p>Compatible with any USB-C enabled device. Pair with iPhone 8 or later for fast charging—50% battery in around 30 minutes.</p>', '{"wattage": "20W", "connector": "USB-C", "compatibility": "iPhone, iPad, AirPods"}', 'apple_20w.jpg', 1900.00, 1699.00, 1200.00, 0, 0, 1, 12),

(7, 3, 7, 'boAt Airdopes 141 ANC TWS Earbuds', 'boat-airdopes-141-anc-tws', 'BOAT-AD-141', '8901234567896', 'AD141ANC', 'Active Noise Cancellation up to 32dB, 42 hours playback, ENx quad mics, and ASAP Charge.', '<p>Block out unwanted ambient rumble with up to 32dB ANC. Enjoy crystal clear calls with quad-mic environmental noise cancellation and beast low-latency gaming mode.</p>', '{"driver": "10mm dynamic drivers", "anc": "Up to 32dB Active Noise Cancellation", "battery": "42 Hours total with case", "ip_rating": "IPX5 Water Resistant"}', 'boat_airdopes141.jpg', 2990.00, 1699.00, 950.00, 0, 1, 1, 12),

(8, 4, 7, 'boAt Stone 352 Bluetooth Speaker', 'boat-stone-352-portable-speaker', 'BOAT-ST-352', '8901234567897', 'Stone352', '10W RMS immersive stereo sound, IPX7 waterproof, 12 hours playback with Type-C charging.', '<p>Take your music anywhere with rugged IPX7 waterproof engineering. Features TWS pairing to connect two Stone 352 units for punchy stereo sound.</p>', '{"output": "10W RMS", "battery": "Up to 12 Hours", "connectivity": "Bluetooth 5.0, AUX, TF Card", "waterproof": "IPX7"}', 'boat_stone352.jpg', 3490.00, 1499.00, 800.00, 0, 0, 1, 12),

(9, 5, 2, 'Samsung Galaxy Watch 6 Classic (47mm)', 'samsung-galaxy-watch-6-classic', 'SM-W6-47MM', '8901234567898', 'SM-R960', 'Iconic rotating bezel, sapphire crystal glass, advanced sleep coaching, ECG, and blood pressure monitoring.', '<p>The smartwatch that knows you best. Timeless design with a 15% thinner rotating bezel and larger screen, packed with body composition analysis and cardiac health sensors.</p>', '{"display": "1.5-inch Super AMOLED Sapphire", "bezel": "Physical Rotating Bezel", "sensors": "BioActive Sensor (Optical Heart + ECG + BIA)", "battery": "425 mAh"}', 'galaxy_watch6.jpg', 36999.00, 29999.00, 24000.00, 0, 1, 1, 12),

(10, 6, 9, 'Spigen Tough Armor Case for iPhone 15 Pro', 'spigen-tough-armor-iphone-15-pro', 'SPG-TA-IP15P', '8901234567899', 'ACS06540', 'Extreme dual-layer drop protection with Air Cushion Technology and reinforced kickstand.', '<p>Built to withstand anything thrown at it. Engineered with impact foam interior, shock-absorbent TPU, and durable polycarbonate shell. Certified Mil-grade protection.</p>', '{"material": "TPU + Polycarbonate + Extreme Impact Foam", "features": "Built-in Kickstand, Air Cushion Corner Pockets", "wireless_charging": "Compatible"}', 'spigen_tougharmor.jpg', 2499.00, 1899.00, 750.00, 0, 0, 1, 24),

(11, 7, 9, 'Spigen Glas.tR EZ Fit Tempered Glass (2-Pack)', 'spigen-glastr-ezfit-tempered-glass', 'SPG-GLAS-EZ', '8901234567900', 'AGL06580', '9H hardness tempered glass with auto-alignment installation tray for bubble-free application.', '<p>Includes an innovative auto-alignment installation tray that makes applying screen protectors effortless and perfectly centered without bubbles or dust.</p>', '{"hardness": "9H Hardness", "coating": "Oleophobic anti-fingerprint", "package": "2 Pieces with Auto-Align Tray"}', 'spigen_glass.jpg', 1699.00, 1299.00, 400.00, 0, 0, 1, 6),

(12, 8, 8, 'Anker 325 Power Bank 20000mAh (PowerCore)', 'anker-325-powerbank-20000mah', 'ANK-PB-20000', '8901234567901', 'A1286', 'Ultra-high 20000mAh capacity power bank with PowerIQ high-speed charging and dual output.', '<p>Massive 20,000mAh capacity provides over 4 full charges for iPhone 14 or Galaxy S23. MultiProtect safety system ensures complete protection for you and your devices.</p>', '{"capacity": "20,000 mAh / 74Wh", "inputs": "USB-C + Micro-USB", "outputs": "2x USB-A PowerIQ Ports", "safety": "MultiProtect Surge and Temp Control"}', 'anker_20000.jpg', 3299.00, 2499.00, 1500.00, 0, 1, 1, 18),

(13, 9, 1, 'Original iPhone 15 Pro OLED Screen Assembly', 'original-iphone-15-pro-oled-assembly', 'REP-DISP-IP15P', '8901234567902', 'OEM-DISP-15P', 'Genuine OEM Super Retina XDR 120Hz ProMotion display module with digitizer and ambient flex.', '<p>Brand new original display replacement part for certified iPhone 15 Pro screen repairs. True Tone and ProMotion calibrated.</p>', '{"type": "Super Retina XDR OLED", "resolution": "2556 x 1179 pixels at 460 ppi", "quality": "Original OEM Grade"}', 'spare_iphone15_screen.jpg', 19500.00, 19500.00, 14000.00, 0, 0, 1, 6),

(14, 9, 2, 'Original Samsung S24 Ultra 5000mAh Battery', 'original-samsung-s24-ultra-battery', 'REP-BAT-S24U', '8901234567903', 'EB-BS928ABY', 'Original Samsung factory replacement lithium-ion polymer battery cell with adhesive pull tabs.', '<p>Genuine Samsung replacement battery providing original battery life and peak 45W fast charging performance.</p>', '{"capacity": "5000 mAh (Typical)", "voltage": "3.88V", "part_number": "GH82-32865A"}', 'spare_s24_battery.jpg', 3200.00, 3200.00, 1800.00, 0, 0, 1, 6);

-- 8. Insert Product Variants (For iPhone 15 Pro Max and Galaxy S24 Ultra)
INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `barcode`, `variant_name`, `attributes`, `additional_price`, `image`, `is_active`) VALUES
(1, 1, 'IPH15PM-256-NAT', '8901234567911', '256GB - Natural Titanium', '{"storage": "256GB", "color": "Natural Titanium"}', 0.00, 'iphone15_natural.jpg', 1),
(2, 1, 'IPH15PM-512-NAT', '8901234567912', '512GB - Natural Titanium', '{"storage": "512GB", "color": "Natural Titanium"}', 20000.00, 'iphone15_natural.jpg', 1),
(3, 1, 'IPH15PM-256-BLK', '8901234567913', '256GB - Black Titanium', '{"storage": "256GB", "color": "Black Titanium"}', 0.00, 'iphone15_black.jpg', 1),
(4, 2, 'SMS24U-256-GRY', '8901234567914', '256GB - Titanium Gray', '{"storage": "256GB", "color": "Titanium Gray"}', 0.00, 's24_gray.jpg', 1),
(5, 2, 'SMS24U-512-GRY', '8901234567915', '512GB - Titanium Gray', '{"storage": "512GB", "color": "Titanium Gray"}', 10000.00, 's24_gray.jpg', 1),
(6, 3, 'OP12-256-BLK', '8901234567916', '256GB / 12GB - Silky Black', '{"storage": "256GB", "ram": "12GB", "color": "Silky Black"}', 0.00, 'op12_black.jpg', 1),
(7, 3, 'OP12-512-GRN', '8901234567917', '512GB / 16GB - Flowy Emerald', '{"storage": "512GB", "ram": "16GB", "color": "Flowy Emerald"}', 5000.00, 'op12_emerald.jpg', 1);

-- 9. Insert Initial Inventory Levels
INSERT INTO `inventory` (`product_id`, `variant_id`, `current_stock`, `min_stock_threshold`, `max_stock_threshold`, `location_rack`) VALUES
(1, 1, 12, 3, 30, 'Rack A-01 (Safe)'),
(1, 2, 8, 2, 20, 'Rack A-01 (Safe)'),
(1, 3, 15, 3, 30, 'Rack A-01 (Safe)'),
(2, 4, 14, 3, 25, 'Rack A-02'),
(2, 5, 9, 2, 20, 'Rack A-02'),
(3, 6, 18, 4, 35, 'Rack A-03'),
(3, 7, 11, 3, 25, 'Rack A-03'),
(4, NULL, 7, 2, 20, 'Rack A-04'),
(5, NULL, 35, 5, 80, 'Shelf B-01'),
(6, NULL, 40, 10, 100, 'Shelf B-02'),
(7, NULL, 28, 5, 60, 'Shelf C-01'),
(8, NULL, 22, 5, 50, 'Shelf C-02'),
(9, NULL, 6, 2, 15, 'Display Showcase 1'),
(10, NULL, 50, 10, 100, 'Hook Rack D-01'),
(11, NULL, 65, 15, 150, 'Hook Rack D-02'),
(12, NULL, 25, 5, 60, 'Shelf B-03'),
(13, NULL, 4, 2, 10, 'Parts Drawer P-01'), -- Spare display
(14, NULL, 5, 2, 12, 'Parts Drawer P-02'); -- Spare battery

-- 10. Record Initial Stock Movement for Audit Integrity
INSERT INTO `stock_movements` (`product_id`, `variant_id`, `movement_type`, `quantity`, `cost_per_unit`, `reference_id`, `reference_type`, `notes`, `created_by`) VALUES
(1, 1, 'purchase_in', 12, 125000.00, 'PO-INIT-001', 'purchase', 'Opening ERP stock initialization', 1),
(1, 2, 'purchase_in', 8, 140000.00, 'PO-INIT-001', 'purchase', 'Opening ERP stock initialization', 1),
(2, 4, 'purchase_in', 14, 110000.00, 'PO-INIT-001', 'purchase', 'Opening ERP stock initialization', 1),
(5, NULL, 'purchase_in', 35, 2200.00, 'PO-INIT-002', 'purchase', 'Opening ERP stock initialization', 1),
(13, NULL, 'purchase_in', 4, 14000.00, 'PO-INIT-003', 'purchase', 'Initial spare parts allocation', 1);

-- 11. Insert Coupons
INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount_amount`, `usage_limit`, `usage_count`, `valid_from`, `valid_until`, `is_active`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 2000.00, 1000.00, 500, 2, '2024-01-01 00:00:00', '2028-12-31 23:59:59', 1),
(2, 'FLAT500', 'fixed', 500.00, 5000.00, 500.00, 200, 0, '2024-01-01 00:00:00', '2028-12-31 23:59:59', 1),
(3, 'FESTIVE15', 'percentage', 15.00, 10000.00, 2500.00, 100, 0, '2024-01-01 00:00:00', '2028-12-31 23:59:59', 1);

-- 12. Insert Customer Addresses
INSERT INTO `addresses` (`id`, `user_id`, `full_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `is_default`, `type`) VALUES
(1, 4, 'John Doe', '9876543213', 'Flat 402, Sunshine Heights', 'MG Road, Indiranagar', 'Bengaluru', 'Karnataka', '560038', 1, 'shipping'),
(2, 5, 'Priya Patel', '9876543214', 'B-12, Shanti Niketan', 'Satellite Road', 'Ahmedabad', 'Gujarat', '380015', 1, 'shipping');

-- 13. Insert Settings
INSERT INTO `settings` (`setting_group`, `setting_key`, `setting_value`, `is_autoload`) VALUES
('general', 'site_name', 'Apex Mobile Hub & ERP', 1),
('general', 'site_tagline', 'Premium Electronics, Smart Repair & Gadget ERP', 1),
('general', 'site_email', 'support@apexmobilehub.com', 1),
('general', 'site_phone', '+91 98765 43210', 1),
('general', 'store_address', 'Showroom 101, Electronics City Tech Park, Bengaluru, Karnataka 560100', 1),
('general', 'store_gstin', '29AABCA1234A1Z5', 1),
('general', 'currency_code', 'INR', 1),
('general', 'currency_symbol', '₹', 1),
('tax', 'gst_rate_percent', '18.00', 1),
('tax', 'cgst_rate_percent', '9.00', 1),
('tax', 'sgst_rate_percent', '9.00', 1),
('payment', 'razorpay_key_id', 'rzp_test_YourKeyHere', 1),
('payment', 'razorpay_key_secret', 'YourSecretKeyHere', 0),
('mail', 'smtp_host', 'smtp.gmail.com', 1),
('mail', 'smtp_port', '587', 1),
('mail', 'smtp_encryption', 'tls', 1),
('mail', 'smtp_username', 'alerts@apexmobilehub.com', 0),
('mail', 'smtp_password', '', 0),
('mail', 'mail_from_name', 'Apex Mobile Hub', 1),
('repair', 'diagnosis_fee', '350.00', 1),
('repair', 'pickup_charge', '150.00', 1);

-- 14. Sample Customer Orders & Items (Historical Data for Analytics)
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `shipping_address_id`, `billing_address_id`, `subtotal`, `discount_amount`, `coupon_id`, `tax_amount`, `shipping_fee`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `razorpay_order_id`, `created_at`) VALUES
(1, 'ORD-2026-1001', 4, 1, 1, 3499.00, 349.90, 1, 566.83, 0.00, 3715.93, 'razorpay', 'paid', 'delivered', 'order_N123456789abc', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'ORD-2026-1002', 5, 2, 2, 1499.00, 0.00, NULL, 269.82, 50.00, 1818.82, 'cod', 'paid', 'shipped', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `variant_name`, `sku`, `unit_price`, `quantity`, `total_price`) VALUES
(1, 1, 5, NULL, 'Anker Prime 67W GaN Wall Charger', NULL, 'ANK-GAN-67W', 3499.00, 1, 3499.00),
(2, 2, 8, NULL, 'boAt Stone 352 Bluetooth Speaker', NULL, 'BOAT-ST-352', 1499.00, 1, 1499.00);

-- 15. Sample Repairs & Tickets
INSERT INTO `repairs` (`id`, `repair_ticket_number`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `device_brand`, `device_model`, `device_imei_serial`, `issue_type`, `issue_description`, `service_mode`, `assigned_technician_id`, `estimated_cost`, `advance_paid`, `final_cost`, `balance_due`, `status`, `customer_approval_status`, `priority`, `created_at`) VALUES
(1, 'REP-2026-8001', 4, 'John Doe', '9876543213', 'john@example.com', 'Apple', 'iPhone 15 Pro', '356789102938475', 'screen_damage', 'Accidental drop cracked the front glass. Display shows vertical lines.', 'shop_visit', 2, 22500.00, 2000.00, 22500.00, 20500.00, 'in_repair', 'approved', 'urgent', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'REP-2026-8002', 5, 'Priya Patel', '9876543214', 'priya@example.com', 'Samsung', 'Galaxy S24 Ultra', '359871234567890', 'battery_replacement', 'Battery draining fast from 80% to 15% within 3 hours. Needs original replacement.', 'pickup', 2, 4500.00, 0.00, 4500.00, 4500.00, 'diagnosis_pending', 'pending', 'normal', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO `repair_estimates` (`id`, `repair_id`, `diagnostic_notes`, `spare_parts_cost`, `labour_cost`, `discount`, `tax_amount`, `total_estimate`, `status`, `sent_at`, `responded_at`) VALUES
(1, 1, 'Front OLED shattered; touch digitizer defective. Replaced with OEM 120Hz display module and restored water-resistant gasket.', 19500.00, 2500.00, 500.00, 1000.00, 22500.00, 'approved', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

INSERT INTO `repair_parts` (`id`, `repair_id`, `product_id`, `part_name`, `quantity`, `unit_cost`, `unit_price`, `added_by`) VALUES
(1, 1, 13, 'Original iPhone 15 Pro OLED Screen Assembly', 1, 14000.00, 19500.00, 2);

-- 16. Sample POS Sale
INSERT INTO `pos_sales` (`id`, `invoice_number`, `cashier_id`, `customer_name`, `customer_phone`, `subtotal`, `tax_amount`, `discount_amount`, `grand_total`, `payment_method`, `amount_paid`, `change_returned`, `status`, `created_at`) VALUES
(1, 'POS-2026-5001', 1, 'Walk-in Customer (Rajesh)', '9900112233', 1899.00, 341.82, 0.00, 2240.82, 'cash', 2500.00, 259.18, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO `pos_sale_items` (`id`, `pos_sale_id`, `product_id`, `product_name`, `sku`, `unit_price`, `quantity`, `tax_rate`, `total_price`) VALUES
(1, 1, 10, 'Spigen Tough Armor Case for iPhone 15 Pro', 'SPG-TA-IP15P', 1899.00, 1, 18.00, 1899.00);

-- 17. Sample Expenses
INSERT INTO `expenses` (`id`, `category`, `title`, `amount`, `payment_method`, `expense_date`, `notes`, `created_by`) VALUES
(1, 'rent', 'Showroom Monthly Lease', 45000.00, 'bank_transfer', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Electronic City Tech Park unit lease', 1),
(2, 'electricity', 'BESCOM Commercial Power Bill', 6800.00, 'upi', DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Showroom & repair lab power consumption', 1),
(3, 'tools', 'Precision Screwdriver & Heat Mat Kit', 4200.00, 'card', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'iFixit Pro Tech Toolkit for technician workbench', 1);

-- 18. Product Reviews
INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review_text`, `is_verified_purchase`, `status`) VALUES
(1, 1, 4, 5, 'Phenomenal Phone & Build Quality!', 'The Natural Titanium feels extraordinarily light compared to 14 Pro Max. The 5x camera lens is crazy sharp.', 1, 'approved'),
(2, 5, 4, 5, 'Best GaN Charger Ever', 'Charges my iPhone and MacBook together without heating up. Super portable size.', 1, 'approved');

