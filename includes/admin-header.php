<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

session_init();
require_role('admin', 'manager');

$lowCount = (int)(db_fetch_value("SELECT COUNT(*) FROM inventory WHERE current_stock <= min_stock_threshold") ?? 0);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | Admin ERP' : 'Admin ERP Dashboard | ' . e(setting('site_name', 'Apex Mobile Hub')) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Resource Hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        html, body { overflow-x: hidden; }

        /* Prevent iOS Safari 100%+ auto-zoom on input focus when font-size < 16px */
        @media screen and (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }
        .touch-scroll {
            -webkit-overflow-scrolling: touch;
        }
        html { scrollbar-color: #94a3b8 #e2e8f0; scrollbar-width: thin; }
        *::-webkit-scrollbar { width: 9px; height: 9px; }
        *::-webkit-scrollbar-track { background: #e8edf3; }
        *::-webkit-scrollbar-thumb { background: #94a3b8; border: 2px solid #e8edf3; border-radius: 999px; }
        *::-webkit-scrollbar-thumb:hover { background: #64748b; }
        #adminSidebar { scrollbar-color: #475569 #0f172a; scrollbar-width: thin; }
        #adminSidebar::-webkit-scrollbar-track { background: #0f172a; }
        #adminSidebar::-webkit-scrollbar-thumb { background: #475569; border-color: #0f172a; }
        .admin-dashboard { animation: dashboardReveal 260ms ease-out both; }
        .admin-dashboard > .grid > div,
        .admin-dashboard > .grid > section { border-color: #dce4ee; transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease; }
        .admin-dashboard > .grid > div:hover,
        .admin-dashboard > .grid > section:hover { transform: translateY(-2px); border-color: #c5d2e0; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.07); }
        @keyframes dashboardReveal { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        @media (min-width: 1024px) {
            #adminSidebar { transition: width 180ms ease; }
            #adminSidebar.sidebar-collapsed { width: 5rem; }
            #adminSidebar.sidebar-collapsed .flex-1.overflow-y-auto { padding-left: 0.5rem; padding-right: 0.5rem; }
            #adminSidebar.sidebar-collapsed .flex-1.overflow-y-auto nav a { justify-content: center; }
            #adminSidebar.sidebar-collapsed .flex-1.overflow-y-auto nav a span,
            #adminSidebar.sidebar-collapsed .flex-1.overflow-y-auto > div > div:first-child,
            #adminSidebar.sidebar-collapsed .h-16 > a > div:nth-child(2),
            #adminSidebar.sidebar-collapsed #adminProfileText { display: none; }
            #adminSidebar.sidebar-collapsed .p-4.bg-navy-900 { justify-content: center; padding-left: 0.5rem; padding-right: 0.5rem; }
        }
        :focus-visible {
            outline: 2px solid #2563eb !important;
            outline-offset: 2px !important;
        }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="h-full flex overflow-hidden text-slate-800 antialiased">

    <!-- WCAG 2.2 Skip to Main Content Link -->
    <a href="#adminMainContent" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[99999] focus:px-4 focus:py-2.5 focus:bg-blue-600 focus:text-white focus:font-bold focus:text-xs focus:rounded-xl focus:shadow-2xl focus:outline-none focus:ring-4 focus:ring-white">
        Skip to admin content
    </a>

    <!-- Mobile Sidebar Backdrop -->
    <div id="mobileSidebarBackdrop" class="fixed inset-0 bg-slate-900/60 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar Navigation -->
    <aside id="adminSidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-navy-950 text-slate-300 flex flex-col transition-transform duration-200 lg:static lg:translate-x-0 -translate-x-full">
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-6 bg-navy-900 border-b border-slate-800">
            <a href="<?= url('/admin/index.php') ?>" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-sm ring-1 ring-white/20">
                    A
                </div>
                <div>
                    <span class="text-sm font-black text-white tracking-tight">ADKINE <span class="text-blue-400">ERP</span></span>
                    <span class="block text-[10px] uppercase tracking-wider text-slate-400 -mt-0.5">Control Center</span>
                </div>
            </a>
            <button onclick="toggleSidebar()" class="lg:hidden text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Links Scrollable -->
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6 text-sm font-semibold">
            <!-- Group 1: Overview -->
            <div>
                <div class="px-3 mb-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Overview</div>
                <nav class="space-y-1">
                    <a href="<?= url('/admin/index.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition group">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 text-blue-400"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?= url('/admin/pos.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-emerald-400 hover:text-emerald-300 hover:bg-slate-800/60 transition group">
                        <i data-lucide="calculator" class="w-4 h-4 text-emerald-400"></i>
                        <span>POS Cash Register</span>
                        <span class="ml-auto px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 text-[11px] font-bold rounded">Quick</span>
                    </a>
                </nav>
            </div>

            <!-- Group 2: Catalog & Stocks -->
            <div>
                <div class="px-3 mb-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Catalog &amp; Stocks</div>
                <nav class="space-y-1">
                    <a href="<?= url('/admin/products.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="package" class="w-4 h-4 text-slate-400"></i>
                        <span>Products &amp; Variants</span>
                    </a>
                    <a href="<?= url('/admin/categories.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="grid" class="w-4 h-4 text-slate-400"></i>
                        <span>Categories</span>
                    </a>
                    <a href="<?= url('/admin/brands.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="tag" class="w-4 h-4 text-slate-400"></i>
                        <span>Brands</span>
                    </a>
                    <a href="<?= url('/admin/inventory.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="boxes" class="w-4 h-4 text-amber-400"></i>
                        <span>Inventory Levels</span>
                    </a>
                    <a href="<?= url('/admin/suppliers.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="truck" class="w-4 h-4 text-slate-400"></i>
                        <span>Suppliers</span>
                    </a>
                    <a href="<?= url('/admin/purchases.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="shopping-cart" class="w-4 h-4 text-slate-400"></i>
                        <span>Purchases (Stock-In)</span>
                    </a>
                </nav>
            </div>

            <!-- Group 3: Sales & Operations -->
            <div>
                <div class="px-3 mb-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Sales &amp; Operations</div>
                <nav class="space-y-1">
                    <a href="<?= url('/admin/orders.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="shopping-bag" class="w-4 h-4 text-blue-400"></i>
                        <span>Customer Orders</span>
                    </a>
                    <a href="<?= url('/admin/repairs.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="wrench" class="w-4 h-4 text-emerald-400"></i>
                        <span>Mobile Repairs</span>
                    </a>
                    <a href="<?= url('/admin/invoices.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
                        <span>Invoices &amp; Billing</span>
                    </a>
                </nav>
            </div>

            <!-- Group 4: Finance & Analytics -->
            <div>
                <div class="px-3 mb-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Finance &amp; Analytics</div>
                <nav class="space-y-1">
                    <a href="<?= url('/admin/reports.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-purple-400"></i>
                        <span>Reports &amp; Analytics</span>
                    </a>
                    <a href="<?= url('/admin/expenses.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="receipt" class="w-4 h-4 text-rose-400"></i>
                        <span>Store Expenses</span>
                    </a>
                    <a href="<?= url('/admin/coupons.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="percent" class="w-4 h-4 text-amber-400"></i>
                        <span>Coupons &amp; Promos</span>
                    </a>
                    <a href="<?= url('/admin/refunds.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="rotate-ccw" class="w-4 h-4 text-rose-400"></i>
                        <span>Refunds &amp; Returns</span>
                    </a>
                </nav>
            </div>

            <!-- Group 5: Administration -->
            <div>
                <div class="px-3 mb-2 text-xs font-extrabold uppercase tracking-wider text-slate-400">Administration</div>
                <nav class="space-y-1">
                    <a href="<?= url('/admin/customers.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="users" class="w-4 h-4 text-slate-400"></i>
                        <span>Customers CRM</span>
                    </a>
                    <a href="<?= url('/admin/staff.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="shield" class="w-4 h-4 text-blue-400"></i>
                        <span>Staff &amp; Roles</span>
                    </a>
                    <a href="<?= url('/admin/audit-logs.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="activity" class="w-4 h-4 text-slate-400"></i>
                        <span>Security Audit Logs</span>
                    </a>
                    <a href="<?= url('/admin/settings.php') ?>" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                        <i data-lucide="settings" class="w-4 h-4 text-slate-400"></i>
                        <span>Store Settings</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 bg-navy-900 border-t border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                    <?= strtoupper(substr(auth()['full_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="min-w-0" id="adminProfileText">
                    <p class="text-sm font-bold text-white truncate"><?= e(auth()['full_name'] ?? 'Staff') ?></p>
                    <p class="text-xs text-slate-400 capitalize truncate"><?= e(auth()['role_slug'] ?? 'Admin') ?></p>
                </div>
            </div>
            <form action="<?= url('/logout.php') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" title="Logout" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Top App Bar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-20 shrink-0">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="text-xs text-slate-500 font-medium hidden sm:block">
                    ERP Session &bull; <?= date('l, d F Y') ?>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?= url('/index.php') ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-slate-700 transition">
                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Storefront</span>
                </a>

                <a href="<?= url('/admin/pos.php') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition">
                    <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                    <span>POS Terminal</span>
                </a>

                <a href="<?= url('/admin/inventory-low-stock.php') ?>" class="relative p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition" title="Low Stock Alerts">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <?php if ($lowCount > 0): ?>
                        <span class="absolute top-1 right-1 w-4 h-4 bg-rose-600 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                            <?= $lowCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Flash Alerts -->
        <?php
        $successes = flash('success');
        $errors = flash('error');
        $warnings = flash('warning');
        $infos = flash('info');
        ?>
        <?php if (!empty($successes) || !empty($errors) || !empty($warnings) || !empty($infos)): ?>
        <div class="px-4 sm:px-6 pt-4 space-y-2">
            <?php foreach ($successes as $m): ?>
                <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                    <span><?= e($m) ?></span>
                </div>
            <?php endforeach; ?>
            <?php foreach ($errors as $m): ?>
                <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600"></i>
                    <span><?= e($m) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Page Dynamic Body -->
        <main id="adminMainContent" tabindex="-1" class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 focus:outline-none">
