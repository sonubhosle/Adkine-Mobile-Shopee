<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart-functions.php';

session_init();
$cartPreview = cart_get_details();

// Dynamic Technical SEO Setup
$siteName = setting('site_name', 'Adkine Mobile Shopee');
$siteTagline = setting('site_tagline', 'Smartphones, Repairs & Accessories');

$seoTitle = isset($pageTitle) 
    ? e($pageTitle) . ' | ' . e($siteName) 
    : e($siteName) . ' - ' . e($siteTagline);

$seoDesc = !empty($metaDescription)
    ? e($metaDescription)
    : 'Discover flagship smartphones, original OEM accessories, fast chargers, and certified 45-minute smartphone repair services at ' . e($siteName) . '. Genuine warranty and express pan-India delivery.';

$seoRobots = !empty($robotsMeta)
    ? e($robotsMeta)
    : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

$currentRequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentFullUrl = $protocol . '://' . $host . $currentRequestUri;

$seoCanonical = !empty($canonicalUrl) 
    ? e(absolute_url($canonicalUrl)) 
    : e(strtok($currentFullUrl, '?'));

$seoOgType = !empty($ogType) ? e($ogType) : 'website';
$seoOgImage = !empty($ogImage) ? e(absolute_url($ogImage)) : e(absolute_url('/assets/images/og-preview.jpg'));
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= $seoTitle ?></title>
    
    <!-- Technical SEO Metadata -->
    <meta name="description" content="<?= $seoDesc ?>">
    <meta name="robots" content="<?= $seoRobots ?>">
    <link rel="canonical" href="<?= $seoCanonical ?>">

    <!-- Open Graph (Facebook / WhatsApp / LinkedIn) -->
    <meta property="og:type" content="<?= $seoOgType ?>">
    <meta property="og:url" content="<?= $seoCanonical ?>">
    <meta property="og:title" content="<?= $seoTitle ?>">
    <meta property="og:description" content="<?= $seoDesc ?>">
    <meta property="og:image" content="<?= $seoOgImage ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= $seoCanonical ?>">
    <meta name="twitter:title" content="<?= $seoTitle ?>">
    <meta name="twitter:description" content="<?= $seoDesc ?>">
    <meta name="twitter:image" content="<?= $seoOgImage ?>">

    <?php $gscVerification = setting('gsc_verification_code'); ?>
    <?php if (!empty($gscVerification)): ?>
    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="<?= e($gscVerification) ?>">
    <?php endif; ?>

    <?php $gaMeasurementId = setting('ga_measurement_id'); ?>
    <?php if (!empty($gaMeasurementId)): ?>
    <!-- Google Analytics 4 (GA4) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaMeasurementId) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= e($gaMeasurementId) ?>');
    </script>
    <?php endif; ?>

    <!-- Sitewide JSON-LD Structured Data: Organization & WebSite -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": ["MobilePhoneStore", "LocalBusiness"],
                "@id": "<?= e(absolute_url('/#organization')) ?>",
                "name": "<?= e($siteName) ?>",
                "url": "<?= e(absolute_url('/index.php')) ?>",
                "telephone": "<?= e(setting('site_phone', '+91 98765 43210')) ?>",
                "email": "<?= e(setting('site_email', 'support@mobileshop.com')) ?>",
                "priceRange": "₹₹",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "<?= e(setting('store_address', 'Electronics City Tech Park')) ?>",
                    "addressCountry": "IN"
                },
                "openingHoursSpecification": [
                    {
                        "@type": "OpeningHoursSpecification",
                        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                        "opens": "09:30",
                        "closes": "21:30"
                    }
                ]
            },
            {
                "@type": "WebSite",
                "@id": "<?= e(absolute_url('/#website')) ?>",
                "url": "<?= e(absolute_url('/index.php')) ?>",
                "name": "<?= e($siteName) ?>",
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": "<?= e(absolute_url('/shop.php')) ?>?q={search_term_string}",
                    "query-input": "required name=search_term_string"
                }
            }
        ]
    }
    </script>
    <?php if (!empty($pageSchema)): ?>
    <!-- Page Specific JSON-LD Schema -->
    <script type="application/ld+json">
    <?= json_encode($pageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <?php endif; ?>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
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
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Resource Hints & DNS Prefetch -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://translate.google.com">
    <link rel="dns-prefetch" href="https://translate.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        html {
            overflow-x: hidden !important;
            max-width: 100vw;
            width: 100%;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden !important;
            max-width: 100vw;
            width: 100%;
            position: relative;
            top: 0 !important;
        }
        [x-cloak] { display: none !important; }

        /* ── Hide Google Translate banner, balloon, iframes & toolbars ── */
        .goog-te-banner-frame,
        .goog-te-balloon-frame,
        #goog-gt-tt,
        .goog-tooltip,
        .goog-tooltip:hover,
        .goog-text-highlight,
        .skiptranslate,
        .goog-te-gadget,
        iframe.skiptranslate,
        iframe[name=":1.container"],
        iframe[name=":2.container"] {
            display: none !important;
            visibility: hidden !important;
        }
        body { top: 0 !important; }
        body > .skiptranslate { display: none !important; }

        /* ── Hidden host for Google Translate element ── */
        #google_translate_element {
            position: fixed !important;
            top: -9999px !important;
            left: -9999px !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            overflow: hidden !important;
            pointer-events: none !important;
            z-index: -9999 !important;
        }

        /* ── Top Bar Live Marquee Scroller ── */
        @keyframes topBarMarqueeAnim {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .top-marquee-track {
            display: inline-flex;
            width: max-content;
            animation: topBarMarqueeAnim 32s linear infinite;
            will-change: transform;
        }
        .top-marquee-track:hover,
        .top-marquee-track:focus-within,
        .top-marquee-paused {
            animation-play-state: paused !important;
        }

        /* ── WCAG 2.2 Level AA Focus Appearance & Accessibility Defaults ── */
        :focus-visible {
            outline: 2px solid #2563eb !important;
            outline-offset: 2px !important;
        }

        /* ── Cross-Browser & Cross-Device Compatibility Helpers ── */
        .mask-gradient-x {
            -webkit-mask-image: linear-gradient(to right, transparent, black 20px, black calc(100% - 20px), transparent);
            mask-image: linear-gradient(to right, transparent, black 20px, black calc(100% - 20px), transparent);
        }

        .touch-scroll {
            -webkit-overflow-scrolling: touch;
        }

        button, input, select, textarea, a {
            -webkit-tap-highlight-color: transparent;
        }

        input[type="search"]::-webkit-search-decoration,
        input[type="search"]::-webkit-search-cancel-button,
        input[type="search"]::-webkit-search-results-button,
        input[type="search"]::-webkit-search-results-decoration {
            -webkit-appearance: none;
        }

        /* iOS Safari Input Auto-Zoom Prevention */
        @media screen and (max-width: 768px) {
            input[type="text"],
            input[type="search"],
            input[type="password"],
            input[type="email"],
            input[type="tel"],
            input[type="number"],
            select,
            textarea {
                font-size: 16px !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            .top-marquee-track {
                animation: none !important;
                transform: none !important;
            }
        }

        /* ── Custom Language Switcher ── */
        .lang-switcher-wrap {
            position: relative;
            z-index: 60;
        }
        .lang-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px) scale(0.95);
            transform-origin: top right;
            transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                        transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
                        visibility 0.25s;
            pointer-events: none;
            background: #ffffff !important;
            z-index: 9999 !important;
            box-shadow: 0 20px 35px -5px rgba(15, 23, 42, 0.22), 0 10px 15px -5px rgba(15, 23, 42, 0.12) !important;
        }
        .lang-dropdown::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 0;
            right: 0;
            height: 10px;
        }
        .lang-switcher-wrap:hover .lang-dropdown,
        .lang-switcher-wrap:focus-within .lang-dropdown,
        .lang-dropdown.open {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) scale(1) !important;
            pointer-events: auto !important;
        }
        .lang-option {
            transition: background 0.15s ease, transform 0.15s ease, color 0.15s ease;
        }
        .lang-option:hover,
        .lang-option:focus-visible {
            transform: translateX(3px);
            background: #f8fafc;
        }
        .lang-option.active-lang {
            background: #eff6ff !important;
            color: #2563eb !important;
            font-weight: 700;
        }

        .dropdown-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(0.96);
            transform-origin: top;
            transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1), 
                        transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                        visibility 0.22s;
            pointer-events: none;
        }
        .group:hover .dropdown-menu,
        .group:focus-within .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 0;
            right: 0;
            height: 12px;
        }
        @keyframes navSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-nav-down { animation: navSlideDown 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>

    <!-- Google Translate Script & Initialization -->
    <script>
        function googleTranslateElementInit() {
            try {
                if (window.google && google.translate && google.translate.TranslateElement) {
                    new google.translate.TranslateElement({
                        pageLanguage: 'en',
                        includedLanguages: 'en,hi,mr',
                        layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                        autoDisplay: false
                    }, 'google_translate_element');
                }
            } catch(e) {
                console.warn('Google Translate init:', e);
            }
        }
    </script>
    <script defer src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</head>
<body class="flex flex-col min-h-screen text-slate-800 antialiased selection:bg-indigo-500 selection:text-white overflow-x-hidden">

    <!-- WCAG 2.2 Level AA: Bypass Blocks / Skip to Main Content Link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[99999] focus:px-4 focus:py-2.5 focus:bg-blue-600 focus:text-white focus:font-bold focus:text-xs focus:rounded-xl focus:shadow-2xl focus:outline-none focus:ring-4 focus:ring-white">
        Skip to main content
    </a>

    <!-- Hidden Host Element for Google Translate -->
    <div id="google_translate_element" aria-hidden="true"></div>

    <!-- Top Micro Announcement & Utility Bar with Live Scroller -->
    <div class="bg-slate-950 text-slate-300 text-xs py-2 border-b border-slate-800/80 overflow-hidden relative z-40 w-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row items-center justify-between gap-2">
            
            <!-- Continuous Marquee Live Scroller with WCAG Pause Control -->
            <div class="w-full md:flex-1 md:max-w-2xl lg:max-w-3xl flex items-center gap-2.5 overflow-hidden py-0.5 min-w-0">
                <!-- Live Tag / Badge -->
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-[10px] font-black tracking-wider uppercase shrink-0 shadow-2xs select-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping" aria-hidden="true"></span>
                    <span class="relative inline-flex rounded-full w-1.5 h-1.5 bg-emerald-400 -ml-3" aria-hidden="true"></span>
                    <span>UPDATES</span>
                </span>

                <!-- Pause/Resume Marquee Animation Button (WCAG 2.2 SC 2.2.2) -->
                <button type="button" id="marqueeToggleBtn" onclick="toggleMarqueeAnimation()"
                    aria-label="Pause announcement ticker" aria-pressed="false"
                    class="p-1 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 transition focus-visible:ring-2 focus-visible:ring-blue-400 shrink-0">
                    <i data-lucide="pause" id="marqueeToggleIcon" class="w-3 h-3" aria-hidden="true"></i>
                </button>
                
                <!-- Infinite Smooth Ticker Track with Gradient Edge Masks (WebKit + Standard) -->
                <div class="relative flex-1 overflow-hidden mask-gradient-x">
                    <div id="topMarqueeTrack" class="top-marquee-track flex items-center whitespace-nowrap text-[11px] sm:text-xs font-semibold gap-8" role="marquee" aria-live="off">
                        <span class="inline-flex items-center gap-1.5 text-amber-400">
                            <span>⚡</span> Express Smartphone Repair in 45 Mins
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-400">
                            <span>🛡️</span> 100% Genuine OEM Certified Parts with 1-Year Warranty
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-sky-400">
                            <span>🚚</span> Free Express Delivery on Orders Over ₹2,000
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-purple-400">
                            <span>🔥</span> Mega Exchange Bonus: Up to ₹5,000 Extra on Old Phones
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-rose-400">
                            <span>⭐</span> 50,000+ Happy Customers & Certified Repair Lab
                        </span>
                        <span class="text-slate-700">•</span>
                        
                        <!-- Duplicate sequence for smooth seamless infinite loop -->
                        <span class="inline-flex items-center gap-1.5 text-amber-400">
                            <span>⚡</span> Express Smartphone Repair in 45 Mins
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-400">
                            <span>🛡️</span> 100% Genuine OEM Certified Parts with 1-Year Warranty
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-sky-400">
                            <span>🚚</span> Free Express Delivery on Orders Over ₹2,000
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-purple-400">
                            <span>🔥</span> Mega Exchange Bonus: Up to ₹5,000 Extra on Old Phones
                        </span>
                        <span class="text-slate-700">•</span>
                        <span class="inline-flex items-center gap-1.5 text-rose-400">
                            <span>⭐</span> 50,000+ Happy Customers & Certified Repair Lab
                        </span>
                        <span class="text-slate-700">•</span>
                    </div>
                </div>
            </div>

            <!-- Utility Links on Right -->
            <div class="hidden md:flex items-center gap-3 sm:gap-4 text-[11px] sm:text-xs shrink-0">
                <a href="tel:<?= preg_replace('/\s+/', '', setting('site_phone', '+91 98765 43210')) ?>" class="text-slate-300 hover:text-amber-400 transition flex items-center gap-1.5">
                    <i data-lucide="phone-call" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span class="font-semibold"><?= e(setting('site_phone', '+91 98765 43210')) ?></span>
                </a>
                <span class="text-slate-700">|</span>
                <a href="<?= url('/repair-track.php') ?>" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 group">
                    <i data-lucide="wrench" class="w-3.5 h-3.5 text-blue-400 group-hover:rotate-45 transition duration-200"></i>
                    <span>Track Repair</span>
                </a>
                <span class="text-slate-700">|</span>
                <a href="<?= url('/admin/index.php') ?>" class="text-slate-400 hover:text-blue-300 transition flex items-center gap-1.5">
                    <i data-lucide="shield" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Staff ERP</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
        <div class="px-4 sm:px-6">
            <div class="flex items-center justify-between h-20 gap-3 sm:gap-6">
                
                <!-- Left: Brand Logo -->
                <a href="<?= url('/index.php') ?>" class="flex items-center gap-2.5 group shrink-0 select-none">
                    <!-- High-Tech Smartphone + Lightning + 'A' Monogram Badge -->
                    <div class="relative w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-700 via-indigo-600 to-violet-700 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30 ring-1 ring-white/25 group-hover:scale-105 group-hover:shadow-indigo-600/50 transition-all duration-300 shrink-0 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-t from-transparent via-white/10 to-white/20 pointer-events-none"></div>
                        <svg class="w-6 h-6 relative z-10" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="6.5" y="2.5" width="19" height="27" rx="3.8" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <line x1="13" y1="5.5" x2="19" y2="5.5" stroke="white" stroke-width="1.6" stroke-linecap="round" opacity="0.8"/>
                            <path d="M16 8L10.5 22H13.6L14.7 19H17.3L18.4 22H21.5L16 8Z" fill="white"/>
                            <path d="M16 11.5L15.1 16.5H16.9L16 11.5Z" fill="#1e1b4b"/>
                            <path d="M16.8 13.5L13.8 19H17L15 25L21 18H17.5L19.2 13.5H16.8Z" fill="#facc15" filter="drop-shadow(0 1px 2px rgba(0,0,0,0.35))"/>
                            <circle cx="16" cy="26.5" r="1.1" fill="white" opacity="0.85"/>
                        </svg>
                    </div>

                    <!-- Brand Name & Identity -->
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1 leading-none">
                            <span class="text-sm sm:text-base font-black tracking-tight text-slate-900 group-hover:text-blue-600 transition-colors">
                                ADKINE
                            </span>
                            <span class="text-sm sm:text-base font-black tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600">
                                MOBILE
                            </span>
                            <span class="inline-flex items-center px-1 py-0.5 rounded bg-gradient-to-r from-amber-500/15 to-orange-500/15 border border-amber-500/30 text-amber-700 text-[8px] font-black tracking-wider uppercase">
                                SHOPEE
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 text-[9px] font-extrabold uppercase tracking-[0.12em] text-slate-400 mt-0.5">
                            <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Sales <span class="text-slate-300">•</span> Repairs <span class="text-slate-300">•</span> Gadgets</span>
                        </div>
                    </div>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-xs font-bold text-slate-700" aria-label="Main Navigation">

                    <a href="<?= url('/shop.php?category=smartphones') ?>" class="text-[13px] px-3 py-2 rounded-xl hover:text-blue-600 hover:bg-slate-50 transition flex items-center gap-1.5 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5 text-slate-400" aria-hidden="true"></i>
                        <span>Smartphones</span>
                        <span class="px-1.5 py-0.5 rounded-full bg-rose-50 border border-rose-200/60 text-rose-600 text-[9px] font-black uppercase tracking-wide">HOT</span>
                    </a>
                    
                    <!-- Accessories & Catalog Dropdown -->
                    <div class="relative group py-2" id="accessoriesNavGroup">
                        <button type="button" id="accessoriesDropdownBtn" aria-haspopup="true" aria-expanded="false" aria-controls="accessoriesMenu"
                            class="text-[13px] px-3 py-2 rounded-xl hover:text-blue-600 hover:bg-slate-50 transition-all flex items-center gap-1.5 group-hover:bg-slate-50 group-hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600 select-none">
                            <i data-lucide="headphones" class="w-3.5 h-3.5 text-slate-400" aria-hidden="true"></i>
                            <span>Accessories</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 group-hover:rotate-180 group-hover:text-blue-600 transition-transform duration-200" aria-hidden="true"></i>
                        </button>
                        <div id="accessoriesMenu" role="menu" aria-label="Accessories Categories"
                            class="dropdown-menu absolute left-0 top-full mt-1.5 w-64 bg-white/95 backdrop-blur-2xl rounded-2xl shadow-2xl shadow-slate-900/10 border border-slate-100 ring-1 ring-slate-900/5 p-2 z-50">
                            <a href="<?= url('/shop.php') ?>" role="menuitem" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-indigo-50 text-slate-900 font-extrabold hover:text-blue-600 hover:translate-x-1 transition-all duration-150 mb-1 border-b border-slate-100 focus-visible:ring-2 focus-visible:ring-blue-600">
                                <div class="w-7 h-7 rounded-lg bg-indigo-100 text-blue-600 flex items-center justify-center shrink-0" aria-hidden="true">
                                    <i data-lucide="grid" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-black">All Products</div>
                                    <div class="text-[10px] text-slate-500 font-normal">Browse full catalogue</div>
                                </div>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300" aria-hidden="true"></i>
                            </a>
                            
                            <div class="space-y-0.5" role="none">
                                <a href="<?= url('/shop.php?category=chargers-adapters') ?>" role="menuitem" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 hover:translate-x-1 transition-all duration-150 text-xs font-bold focus-visible:ring-2 focus-visible:ring-blue-600">
                                    <i data-lucide="zap" class="w-4 h-4 text-amber-500" aria-hidden="true"></i>
                                    <span>Chargers &amp; GaN</span>
                                </a>
                                <a href="<?= url('/shop.php?category=earphones-audio') ?>" role="menuitem" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 hover:translate-x-1 transition-all duration-150 text-xs font-bold focus-visible:ring-2 focus-visible:ring-blue-600">
                                    <i data-lucide="headphones" class="w-4 h-4 text-indigo-500" aria-hidden="true"></i>
                                    <span>Earphones &amp; Audio</span>
                                </a>
                                <a href="<?= url('/shop.php?category=smartwatches') ?>" role="menuitem" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 hover:translate-x-1 transition-all duration-150 text-xs font-bold focus-visible:ring-2 focus-visible:ring-blue-600">
                                    <i data-lucide="watch" class="w-4 h-4 text-blue-500" aria-hidden="true"></i>
                                    <span>Smartwatches</span>
                                </a>
                                <a href="<?= url('/shop.php?category=mobile-covers') ?>" role="menuitem" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 hover:translate-x-1 transition-all duration-150 text-xs font-bold focus-visible:ring-2 focus-visible:ring-blue-600">
                                    <i data-lucide="shield" class="w-4 h-4 text-purple-500" aria-hidden="true"></i>
                                    <span>Cases &amp; Covers</span>
                                </a>
                                <a href="<?= url('/shop.php?category=spare-parts') ?>" role="menuitem" class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 hover:translate-x-1 transition-all duration-150 text-xs font-bold focus-visible:ring-2 focus-visible:ring-blue-600">
                                    <i data-lucide="cpu" class="w-4 h-4 text-emerald-500" aria-hidden="true"></i>
                                    <span>OEM Spare Parts</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Right Action Hub: Search, Cart, User Profile & Mobile Toggle -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    
                    <!-- Search Input -->
                    <form action="<?= url('/shop.php') ?>" method="GET" role="search" class="hidden md:flex relative items-center w-40 lg:w-52 xl:w-64 2xl:w-72 group">
                        <label for="desktopStoreSearch" class="sr-only">Search phones and accessories</label>
                        <input type="search" id="desktopStoreSearch" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search phones, accessories..."
                            class="w-full pl-9 pr-14 py-2 rounded-xl bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 focus:border-blue-500 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-blue-500/10 transition shadow-2xs">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 group-focus-within:text-blue-600 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none transition-colors" aria-hidden="true"></i>
                        <button type="submit" aria-label="Submit search" class="absolute right-1 top-1/2 -translate-y-1/2 inline-flex items-center px-2 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold transition shadow-xs focus-visible:ring-2 focus-visible:ring-offset-1 focus-visible:ring-blue-600">
                            Go
                        </button>
                    </form>

                    <!-- Book Repair Button -->
                    <a href="<?= url('/repair-book.php') ?>" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-600 text-white font-bold text-xs shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/35 hover:-translate-y-0.5 transition-all focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <i data-lucide="wrench" class="w-3.5 h-3.5" aria-hidden="true"></i>
                        <span>Book Repair</span>
                    </a>

                    <!-- Shopping Cart Trigger Button -->
                    <button type="button" id="cartHeaderTrigger" onclick="toggleCartDrawer(true)"
                        aria-label="View Shopping Cart (<?= $cartPreview['total_items'] ?> items, <?= format_currency($cartPreview['grand_total']) ?>)"
                        aria-haspopup="dialog" aria-expanded="false" aria-controls="cartDrawer"
                        class="relative p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200/90 hover:border-blue-400 text-slate-700 hover:bg-blue-50/40 transition-all flex items-center gap-2 group shadow-2xs focus-visible:ring-2 focus-visible:ring-blue-600">
                        <div class="relative" aria-hidden="true">
                            <i data-lucide="shopping-bag" class="w-5 h-5 text-slate-700 group-hover:text-blue-600 group-hover:scale-105 transition-transform"></i>
                            <span id="cartBadgeCount" class="absolute -top-2 -right-2.5 min-w-[20px] h-5 px-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-[10px] font-black rounded-full flex items-center justify-center ring-2 ring-white shadow-xs">
                                <?= $cartPreview['total_items'] ?>
                            </span>
                        </div>
                        <span class="hidden sm:inline text-xs font-bold text-slate-900 group-hover:text-blue-600 transition" id="cartHeaderTotal" aria-hidden="true">
                            <?= format_currency($cartPreview['grand_total']) ?>
                        </span>
                    </button>
                    </button>

                    <!-- User Account Dropdown -->
                    <?php if (auth()): ?>
                        <div class="relative group py-2">
                            <button type="button" aria-label="User Profile" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-600 text-white font-extrabold text-xs sm:text-sm flex items-center justify-center shadow-sm hover:shadow-md hover:scale-105 ring-2 ring-slate-200 hover:ring-blue-400 transition-all duration-200 select-none">
                                <?= strtoupper(substr(auth()['full_name'], 0, 1)) ?>
                            </button>
                            
                            <div class="dropdown-menu absolute right-0 top-full mt-1.5 w-60 bg-white rounded-2xl shadow-2xl shadow-slate-900/15 border border-slate-200 ring-1 ring-slate-900/5 p-2 z-50">
                                <div class="px-3.5 py-2.5 rounded-xl bg-slate-50/80 mb-1 border border-slate-100 text-xs">
                                    <div class="font-extrabold text-slate-900"><?= e(auth()['full_name']) ?></div>
                                    <div class="text-slate-400 truncate text-[11px]"><?= e(auth()['email']) ?></div>
                                    <span class="inline-block mt-1 px-2 py-0.5 bg-indigo-100 text-blue-700 rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                        <?= e(auth()['role_slug']) ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-0.5">
                                    <?php if (has_role('admin', 'manager')): ?>
                                        <a href="<?= url('/admin/index.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-blue-600 hover:bg-indigo-50 hover:translate-x-1 transition-all duration-150">
                                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Admin ERP Dashboard
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (has_role('technician')): ?>
                                        <a href="<?= url('/technician/index.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-emerald-600 hover:bg-emerald-50 hover:translate-x-1 transition-all duration-150">
                                            <i data-lucide="cpu" class="w-4 h-4"></i> Technician Portal
                                        </a>
                                    <?php endif; ?>

                                    <a href="<?= url('/repair-track.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-blue-600 hover:bg-indigo-50 hover:translate-x-1 transition-all duration-150">
                                        <i data-lucide="search" class="w-4 h-4 text-blue-600"></i>
                                        <span>Track Repair Ticket</span>
                                    </a>

                                    <a href="<?= url('/orders.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 hover:bg-slate-50 hover:translate-x-1 transition-all duration-150">
                                        <i data-lucide="package" class="w-4 h-4 text-slate-400"></i> My Orders
                                    </a>
                                    <a href="<?= url('/my-repairs.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 hover:bg-slate-50 hover:translate-x-1 transition-all duration-150">
                                        <i data-lucide="wrench" class="w-4 h-4 text-slate-400"></i> My Repairs
                                    </a>
                                    <a href="<?= url('/account.php') ?>" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-slate-700 hover:bg-slate-50 hover:translate-x-1 transition-all duration-150">
                                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Profile & Addresses
                                    </a>
                                </div>

                                <div class="border-t border-slate-100 my-1"></div>
                                
                                <form action="<?= url('/logout.php') ?>" method="POST">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs text-rose-600 hover:bg-rose-50 font-bold text-left transition duration-150">
                                        <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="hidden sm:flex items-center gap-2">
                            <a href="<?= url('/login.php') ?>" 
                                class="px-3 py-2 rounded-xl text-slate-700 hover:text-blue-600 font-bold text-xs hover:bg-slate-100 transition">
                                Sign In
                            </a>
                            <a href="<?= url('/register.php') ?>" 
                                class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm shadow-blue-500/20 transition">
                                Register
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- ── Language Switcher Dropdown ── -->
                    <div class="lang-switcher-wrap relative" id="langSwitcherWrap">
                        <button type="button" id="langSwitcherBtn" onclick="toggleLangDropdown()"
                            class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl border border-slate-200/90 hover:border-blue-400 bg-white hover:bg-blue-50/40 text-slate-700 text-xs font-bold transition-all shadow-2xs select-none focus-visible:ring-2 focus-visible:ring-blue-600"
                            aria-label="Select Language, currently English" aria-haspopup="listbox" aria-expanded="false" aria-controls="langDropdown">
                            <span id="langCurrentFlag" class="text-base leading-none" aria-hidden="true">🇮🇳</span>
                            <span id="langCurrentLabel" class="hidden sm:inline text-[11px] font-bold text-slate-700">EN</span>
                            <i data-lucide="chevron-down" id="langChevron" class="w-3 h-3 text-slate-400 transition-transform duration-200" aria-hidden="true"></i>
                        </button>

                        <!-- Dropdown panel -->
                        <div id="langDropdown" role="listbox" aria-label="Language options" tabindex="-1"
                            class="lang-dropdown absolute right-0 top-full mt-2.5 w-52 bg-white rounded-2xl shadow-2xl border border-slate-200/90 ring-1 ring-slate-900/10 p-2 z-[9999]">
                            <div class="px-3 py-1.5 text-[9px] font-extrabold uppercase tracking-widest text-slate-400 border-b border-slate-100 mb-1">
                                🌐 Select Language
                            </div>
                            <button type="button" onclick="switchLanguage('en','🇬🇧','EN','English')"
                                class="lang-option active-lang w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left focus-visible:ring-2 focus-visible:ring-blue-600"
                                data-lang="en" role="option" aria-selected="true">
                                <span class="text-xl leading-none" aria-hidden="true">🇬🇧</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-900">English</div>
                                    <div class="text-[10px] text-slate-500">English</div>
                                </div>
                                <i data-lucide="check" id="check-en" class="w-3.5 h-3.5 text-blue-600 shrink-0 lang-check lang-check-en" aria-hidden="true"></i>
                            </button>
                            <button type="button" onclick="switchLanguage('hi','🇮🇳','हिं','हिन्दी')"
                                class="lang-option w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left focus-visible:ring-2 focus-visible:ring-blue-600"
                                data-lang="hi" role="option" aria-selected="false">
                                <span class="text-xl leading-none" aria-hidden="true">🇮🇳</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-900">हिन्दी</div>
                                    <div class="text-[10px] text-slate-500">Hindi</div>
                                </div>
                                <i data-lucide="check" id="check-hi" class="w-3.5 h-3.5 text-blue-600 shrink-0 lang-check lang-check-hi opacity-0" aria-hidden="true"></i>
                            </button>
                            <button type="button" onclick="switchLanguage('mr','🇮🇳','म','मराठी')"
                                class="lang-option w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left focus-visible:ring-2 focus-visible:ring-blue-600"
                                data-lang="mr" role="option" aria-selected="false">
                                <span class="text-xl leading-none" aria-hidden="true">🇮🇳</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-900">मराठी</div>
                                    <div class="text-[10px] text-slate-500">Marathi</div>
                                </div>
                                <i data-lucide="check" id="check-mr" class="w-3.5 h-3.5 text-blue-600 shrink-0 lang-check lang-check-mr opacity-0" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Menu Hamburger Button -->
                    <button type="button" id="mobileNavToggleBtn" onclick="toggleResponsiveNavbar()" aria-label="Toggle Navigation Menu" aria-expanded="false" aria-controls="responsiveMobileNav"
                        class="lg:hidden p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:text-blue-600 hover:bg-slate-100 transition focus-visible:ring-2 focus-visible:ring-blue-600">
                        <i data-lucide="menu" id="navHamburgerIcon" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Search -->
            <form action="<?= url('/shop.php') ?>" method="GET" role="search" class="md:hidden pb-3">
                <label for="responsiveStoreSearch" class="sr-only">Search phones and accessories</label>
                <div class="relative flex items-center">
                    <i data-lucide="search" class="w-4 h-4 text-blue-600 absolute left-3 pointer-events-none" aria-hidden="true"></i>
                    <input type="search" id="responsiveStoreSearch" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search phones, accessories and more"
                        class="w-full pl-10 pr-24 py-3 rounded-lg bg-white border border-slate-300 text-sm text-slate-900 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-600 shadow-sm">
                    <button type="submit" aria-label="Search" class="absolute right-1.5 inline-flex items-center gap-1.5 px-3 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold transition focus-visible:ring-2 focus-visible:ring-blue-600">
                        <i data-lucide="search" class="w-3.5 h-3.5" aria-hidden="true"></i>
                        <span>Search</span>
                    </button>
                </div>
            </form>

            <!-- Responsive Mobile Menu -->
            <div id="responsiveMobileNav" class="hidden lg:hidden border-t border-slate-100 py-4 space-y-3 bg-white" role="region" aria-label="Mobile Navigation Menu">
                <div class="grid grid-cols-2 gap-2 pt-1">
                    <a href="<?= url('/repair-book.php') ?>" class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-center text-xs font-bold flex items-center justify-center gap-1.5 focus-visible:ring-2 focus-visible:ring-emerald-600">
                        <i data-lucide="wrench" class="w-4 h-4 text-emerald-600" aria-hidden="true"></i> Book Repair
                    </a>
                    <a href="<?= url('/repair-track.php') ?>" class="p-2.5 rounded-xl bg-indigo-50 border border-blue-200 text-blue-800 text-center text-xs font-bold flex items-center justify-center gap-1.5 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <i data-lucide="search" class="w-4 h-4 text-blue-600" aria-hidden="true"></i> Track Ticket
                    </a>
                </div>

                <nav class="divide-y divide-slate-100 pt-1 text-xs font-bold text-slate-700" aria-label="Mobile Categories">
                    <a href="<?= url('/shop.php') ?>" class="flex items-center justify-between py-2.5 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <span class="flex items-center gap-2"><i data-lucide="grid" class="w-4 h-4 text-blue-600" aria-hidden="true"></i> All Products</span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300" aria-hidden="true"></i>
                    </a>
                    <a href="<?= url('/shop.php?category=smartphones') ?>" class="flex items-center justify-between py-2.5 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <span class="flex items-center gap-2"><i data-lucide="smartphone" class="w-4 h-4 text-slate-400" aria-hidden="true"></i> Smartphones</span>
                        <span class="px-1.5 py-0.5 rounded-full bg-rose-50 text-rose-600 text-[9px] font-black">HOT</span>
                    </a>
                    <a href="<?= url('/shop.php?category=chargers-adapters') ?>" class="flex items-center justify-between py-2.5 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <span class="flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-slate-400" aria-hidden="true"></i> Chargers &amp; Adapters</span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300" aria-hidden="true"></i>
                    </a>
                    <a href="<?= url('/shop.php?category=earphones-audio') ?>" class="flex items-center justify-between py-2.5 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <span class="flex items-center gap-2"><i data-lucide="headphones" class="w-4 h-4 text-slate-400" aria-hidden="true"></i> Earphones &amp; Audio</span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300" aria-hidden="true"></i>
                    </a>
                    <a href="<?= url('/shop.php?category=smartwatches') ?>" class="flex items-center justify-between py-2.5 hover:text-blue-600 focus-visible:ring-2 focus-visible:ring-blue-600">
                        <span class="flex items-center gap-2"><i data-lucide="watch" class="w-4 h-4 text-slate-400" aria-hidden="true"></i> Smartwatches</span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300" aria-hidden="true"></i>
                    </a>
                </nav>

                <!-- Mobile Language Switcher -->
                <div class="pt-2 border-t border-slate-100">
                    <div class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400 mb-1.5 px-1">🌐 Language</div>
                    <div class="flex items-center gap-2" role="group" aria-label="Select Mobile Language">
                        <button type="button" onclick="switchLanguage('en','🇬🇧','EN','English')"
                            class="lang-option active-lang flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-blue-400 hover:bg-blue-50 transition focus-visible:ring-2 focus-visible:ring-blue-600"
                            data-lang="en" aria-label="English">
                            <span aria-hidden="true">🇬🇧</span><span>EN</span>
                            <i data-lucide="check" id="m-check-en" class="w-3 h-3 text-blue-600 lang-check lang-check-en" aria-hidden="true"></i>
                        </button>
                        <button type="button" onclick="switchLanguage('hi','🇮🇳','हिं','हिन्दी')"
                            class="lang-option flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-blue-400 hover:bg-blue-50 transition focus-visible:ring-2 focus-visible:ring-blue-600"
                            data-lang="hi" aria-label="Hindi">
                            <span aria-hidden="true">🇮🇳</span><span>हिं</span>
                            <i data-lucide="check" id="m-check-hi" class="w-3 h-3 text-blue-600 lang-check lang-check-hi opacity-0" aria-hidden="true"></i>
                        </button>
                        <button type="button" onclick="switchLanguage('mr','🇮🇳','म','मराठी')"
                            class="lang-option flex-1 flex items-center justify-center gap-1.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:border-blue-400 hover:bg-blue-50 transition focus-visible:ring-2 focus-visible:ring-blue-600"
                            data-lang="mr" aria-label="Marathi">
                            <span aria-hidden="true">🇮🇳</span><span>म</span>
                            <i data-lucide="check" id="m-check-mr" class="w-3 h-3 text-blue-600 lang-check lang-check-mr opacity-0" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <?php if (!auth()): ?>
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                        <a href="<?= url('/login.php') ?>" class="py-2.5 rounded-xl border border-slate-200 text-center text-xs font-bold text-slate-700 hover:bg-slate-50 transition focus-visible:ring-2 focus-visible:ring-blue-600">Sign In</a>
                        <a href="<?= url('/register.php') ?>" class="py-2.5 rounded-xl bg-indigo-600 text-center text-xs font-bold text-white hover:bg-indigo-700 shadow-sm transition focus-visible:ring-2 focus-visible:ring-blue-600">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Flash Notifications Component (WCAG 2.2 Live Region) -->
    <?php
    $successes = flash('success');
    $errors = flash('error');
    $warnings = flash('warning');
    $infos = flash('info');
    ?>
    <?php if (!empty($successes) || !empty($errors) || !empty($warnings) || !empty($infos)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2" aria-live="polite">
        <?php foreach ($successes as $msg): ?>
            <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl shadow-sm text-sm" role="status">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0" aria-hidden="true"></i>
                <span class="font-medium flex-1"><?= e($msg) ?></span>
                <button onclick="this.parentElement.remove()" aria-label="Dismiss notification" class="text-emerald-500 hover:text-emerald-700 focus-visible:ring-2 focus-visible:ring-emerald-600 rounded-md p-1">
                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>
        <?php endforeach; ?>

        <?php foreach ($errors as $msg): ?>
            <div class="flex items-center gap-3 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-sm text-sm" role="alert">
                <i data-lucide="alert-octagon" class="w-5 h-5 text-rose-600 shrink-0" aria-hidden="true"></i>
                <span class="font-medium flex-1"><?= e($msg) ?></span>
                <button onclick="this.parentElement.remove()" aria-label="Dismiss alert" class="text-rose-500 hover:text-rose-700 focus-visible:ring-2 focus-visible:ring-rose-600 rounded-md p-1">
                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>
        <?php endforeach; ?>

        <?php foreach ($warnings as $msg): ?>
            <div class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl shadow-sm text-sm" role="alert">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0" aria-hidden="true"></i>
                <span class="font-medium flex-1"><?= e($msg) ?></span>
                <button onclick="this.parentElement.remove()" aria-label="Dismiss warning" class="text-amber-500 hover:text-amber-700 focus-visible:ring-2 focus-visible:ring-amber-600 rounded-md p-1">
                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>
        <?php endforeach; ?>

        <?php foreach ($infos as $msg): ?>
            <div class="flex items-center gap-3 p-4 bg-indigo-50 border border-blue-200 text-blue-800 rounded-xl shadow-sm text-sm" role="status">
                <i data-lucide="info" class="w-5 h-5 text-blue-600 shrink-0" aria-hidden="true"></i>
                <span class="font-medium flex-1"><?= e($msg) ?></span>
                <button onclick="this.parentElement.remove()" aria-label="Dismiss info notice" class="text-blue-500 hover:text-blue-700 focus-visible:ring-2 focus-visible:ring-blue-600 rounded-md p-1">
                    <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Main Content Area with Skip Target Landmark (WCAG 2.2 SC 2.4.1) -->
    <main id="main-content" tabindex="-1" class="flex-1 focus:outline-none">
