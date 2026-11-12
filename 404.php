<?php
/**
 * Custom 404 Page Not Found
 * Ensures proper HTTP 404 status code, noindex directive, and guided customer recovery.
 */
http_response_code(404);
$pageTitle = 'Page Not Found (404)';
$metaDescription = 'The page you are looking for does not exist or has been moved. Explore smartphones, gadget accessories, or certified repairs at Adkine Mobile Shopee.';
$robotsMeta = 'noindex, nofollow';

require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[70vh] flex items-center justify-center px-4 sm:px-6 lg:px-8 py-16 bg-slate-50">
    <div class="max-w-xl w-full text-center space-y-8">
        
        <!-- 404 Illustration Badge -->
        <div class="relative inline-block">
            <div class="w-32 h-32 rounded-3xl bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto shadow-sm">
                <i data-lucide="smartphone-charging" class="w-16 h-16 text-blue-600 animate-pulse"></i>
            </div>
            <span class="absolute -bottom-2 -right-2 px-3 py-1 rounded-full bg-rose-600 text-white text-xs font-black tracking-wider uppercase shadow-md">
                404 ERROR
            </span>
        </div>

        <!-- Headings & Explanation -->
        <div class="space-y-3">
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                Oops! Page Not Found
            </h1>
            <p class="text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
                The link you followed may be broken, expired, or the product may have been relocated. Use the search bar below or explore our popular departments.
            </p>
        </div>

        <!-- Quick Store Search Box -->
        <form action="<?= url('/shop.php') ?>" method="GET" role="search" class="max-w-md mx-auto flex gap-2">
            <div class="relative flex-1">
                <label for="recoverySearchInput" class="sr-only">Search smartphones, chargers, earbuds</label>
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true"></i>
                <input type="search" id="recoverySearchInput" name="q" placeholder="Search smartphones, chargers, earbuds..." 
                       class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-xs">
            </div>
            <button type="submit" aria-label="Search" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition shrink-0 focus-visible:ring-2 focus-visible:ring-blue-600">
                Search
            </button>
        </form>

        <!-- Recovery Action Links -->
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            <a href="<?= url('/index.php') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition shadow-xs">
                <i data-lucide="home" class="w-4 h-4"></i>
                <span>Return to Home</span>
            </a>
            <a href="<?= url('/shop.php') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold transition shadow-xs">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>Browse Products</span>
            </a>
            <a href="<?= url('/repair-book.php') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-blue-600 border border-blue-200 text-xs font-bold transition shadow-xs">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                <span>Book a Repair</span>
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

