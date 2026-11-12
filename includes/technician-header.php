<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($title) ? e($title) . ' | Technician Workbench' : 'Technician Workbench | ' . e(setting('site_name', 'Adkine Mobile Shopee')) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 800: '#1e293b', 900: '#0f172a', 950: '#020617' }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
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
<body class="h-full flex flex-col text-slate-800 antialiased">

    <!-- WCAG 2.2 Skip to Main Content Link -->
    <a href="#techMainContent" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[99999] focus:px-4 focus:py-2.5 focus:bg-blue-600 focus:text-white focus:font-bold focus:text-xs focus:rounded-xl focus:shadow-2xl focus:outline-none focus:ring-4 focus:ring-white">
        Skip to technician workbench content
    </a>

    <!-- Top Technician Navbar -->
    <header class="bg-navy-950 text-white h-16 border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 z-20 shrink-0">
        <div class="flex items-center gap-3">
            <a href="<?= url('/technician/') ?>" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                </div>
                <div>
                    <span class="text-sm font-black text-white">REPAIR <span class="text-emerald-400">LAB</span></span>
                    <span class="block text-[9px] uppercase tracking-wider text-slate-400 -mt-0.5">Technician Portal</span>
                </div>
            </a>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-xs text-slate-300 font-semibold flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span><?= e(auth_user()['full_name'] ?? 'Technician') ?></span>
            </div>

            <a href="<?= url('/') ?>" class="text-xs text-slate-400 hover:text-white transition">
                Storefront
            </a>

            <form action="<?= url('/logout.php') ?>" method="POST" class="inline">
                <?= csrf_field() ?>
                <button type="submit"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-rose-900 text-rose-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
                </button>
            </form>
        </div>
    </header>

    <!-- Main Container -->
    <main id="techMainContent" tabindex="-1" class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 focus:outline-none">
