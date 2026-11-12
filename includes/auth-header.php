<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

session_init();
$minimal = $minimal ?? false;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . e(setting('site_name', 'Adkine Mobile Shopee')) : 'Authentication | ' . e(setting('site_name', 'Adkine Mobile Shopee')) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        html, body {
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        /* Prevent iOS Safari 100%+ auto-zoom on input focus when font-size < 16px */
        @media screen and (max-width: 768px) {
            input, select, textarea {
                font-size: 16px !important;
            }
        }

        @keyframes authFloat  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
        @keyframes authPulse  { 0%,100%{opacity:.3;transform:scale(1)} 50%{opacity:.55;transform:scale(1.1)} }
        @keyframes authOrbit  { from{transform:rotate(0deg) translateX(100px) rotate(0deg)} to{transform:rotate(360deg) translateX(100px) rotate(-360deg)} }
        @keyframes authOrbit2 { from{transform:rotate(220deg) translateX(80px) rotate(-220deg)} to{transform:rotate(580deg) translateX(80px) rotate(-580deg)} }
        @keyframes authScan   { 0%{top:10%} 50%{top:80%} 100%{top:10%} }
        @keyframes authWave   { 0%,100%{transform:scaleY(1)} 50%{transform:scaleY(1.7)} }
        @keyframes authPing   { 0%{transform:scale(1);opacity:.8} 100%{transform:scale(2.2);opacity:0} }
        @keyframes authSlideUp{ 0%{opacity:0;transform:translateY(16px)} 100%{opacity:1;transform:translateY(0)} }
        @keyframes authShimmer{ 0%{background-position:-200% 0} 100%{background-position:200% 0} }

        .auth-float   { animation: authFloat 4s ease-in-out infinite; }
        .auth-pulse   { animation: authPulse 3s ease-in-out infinite; }
        .auth-orbit   { animation: authOrbit 9s linear infinite; }
        .auth-orbit2  { animation: authOrbit2 7s linear infinite; }
        .auth-scan    { animation: authScan 3.5s ease-in-out infinite; }
        .auth-wave    { animation: authWave .9s ease-in-out infinite; }
        .auth-slide-1 { animation: authSlideUp .5s .1s ease both; }
        .auth-slide-2 { animation: authSlideUp .5s .3s ease both; }
        .auth-slide-3 { animation: authSlideUp .5s .5s ease both; }
        .auth-shimmer {
            background: linear-gradient(90deg,transparent 0%,rgba(255,255,255,.07) 50%,transparent 100%);
            background-size: 200% 100%;
            animation: authShimmer 2.4s linear infinite;
        }
        .glass { background:rgba(255,255,255,.04); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,.08); }
        .auth-input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.12); }

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
<body class="h-full lg:h-screen lg:overflow-hidden bg-slate-950 antialiased selection:bg-blue-500 selection:text-white">

    <!-- WCAG 2.2 Skip to Auth Form -->
    <a href="#authFormContent" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[99999] focus:px-4 focus:py-2.5 focus:bg-blue-600 focus:text-white focus:font-bold focus:text-xs focus:rounded-xl focus:shadow-2xl focus:outline-none focus:ring-4 focus:ring-white">
        Skip to authentication form
    </a>

    <div class="h-full lg:h-screen grid grid-cols-1 <?= $minimal ? '' : 'lg:grid-cols-12' ?> lg:overflow-hidden">

        <?php if (!$minimal): ?>
        <!-- LEFT PANEL — Dark animated showcase -->
        <div class="hidden lg:flex lg:col-span-5 relative flex-col h-full overflow-hidden bg-slate-950 border-r border-white/[0.06] select-none">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_30%_20%,rgba(59,130,246,0.15),transparent)] pointer-events-none"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_70%_80%,rgba(99,102,241,0.12),transparent)] pointer-events-none"></div>
            <div class="absolute inset-0 opacity-[0.035] bg-[linear-gradient(rgba(255,255,255,.2)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.2)_1px,transparent_1px)] [background-size:28px_28px] pointer-events-none"></div>

            <div class="relative z-10 flex flex-col h-full px-8 xl:px-10 py-7">
                <div>
                    <a href="<?= url('/index.php') ?>" class="inline-flex items-center gap-2.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-violet-700 flex items-center justify-center shadow-lg shadow-indigo-500/30 ring-1 ring-white/20 group-hover:scale-105 transition duration-200">
                            <svg class="w-5 h-5" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="6.5" y="2.5" width="19" height="27" rx="3.8" stroke="white" stroke-width="2"/>
                                <path d="M16 8L10.5 22H13.6L14.7 19H17.3L18.4 22H21.5L16 8Z" fill="white"/>
                                <path d="M16 11.5L15.1 16.5H16.9L16 11.5Z" fill="#1e1b4b"/>
                                <path d="M16.8 13.5L13.8 19H17L15 25L21 18H17.5L19.2 13.5H16.8Z" fill="#facc15"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 leading-none">
                                <span class="text-base font-black tracking-tight text-white">ADKINE <span class="text-blue-400">MOBILE</span></span>
                                <span class="px-1 py-0.2 rounded bg-amber-400/20 text-amber-300 text-[8px] font-black uppercase">SHOPEE</span>
                            </div>
                            <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400 block mt-1">Smartphones · Certified Repairs · Spares</span>
                        </div>
                    </a>
                    <div class="inline-flex items-center gap-1.5 mt-3 px-2.5 py-1 rounded-full glass text-[10px] font-semibold text-blue-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>System Online · ERP v1.0.0</span>
                    </div>
                </div>

                <!-- Animated Phone Illustration -->
                <div class="flex-1 flex items-center justify-center relative">
                    <div class="absolute w-56 h-56 bg-blue-600/12 rounded-full blur-3xl auth-pulse pointer-events-none"></div>
                    <div class="absolute w-40 h-40 bg-indigo-600/10 rounded-full blur-2xl auth-pulse pointer-events-none" style="animation-delay:.7s"></div>

                    <div class="absolute auth-orbit" style="top:50%;left:50%;margin:-4px">
                        <div class="w-2 h-2 rounded-full bg-blue-400/70 shadow-md shadow-blue-400/50"></div>
                    </div>
                    <div class="absolute auth-orbit2" style="top:50%;left:50%;margin:-3px">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-400/60 shadow-sm shadow-indigo-400/50"></div>
                    </div>

                    <div class="auth-float relative" style="width:140px">
                        <div class="relative mx-auto" style="width:140px;height:280px">
                            <div class="absolute inset-0 rounded-[2.2rem] bg-gradient-to-b from-slate-700 via-slate-800 to-slate-900 border border-white/[0.09] shadow-2xl overflow-hidden">
                                <div class="absolute inset-0 auth-shimmer rounded-[2.2rem]"></div>
                                <div class="absolute rounded-[1.8rem] overflow-hidden bg-gradient-to-b from-slate-950 to-slate-900 border border-white/[0.05]" style="top:12px;left:7px;right:7px;bottom:12px">
                                    <div class="flex items-center justify-between px-3 pt-2.5 pb-1">
                                        <span class="text-white text-[6px] font-bold">9:41</span>
                                        <div class="flex gap-1 items-center">
                                            <div class="flex gap-px items-end" style="height:9px">
                                                <div class="w-0.5 bg-white/50 rounded-full auth-wave" style="height:30%;animation-delay:0s"></div>
                                                <div class="w-0.5 bg-white/50 rounded-full auth-wave" style="height:55%;animation-delay:.12s"></div>
                                                <div class="w-0.5 bg-white/50 rounded-full auth-wave" style="height:80%;animation-delay:.24s"></div>
                                                <div class="w-0.5 bg-white/50 rounded-full auth-wave" style="height:100%;animation-delay:.36s"></div>
                                            </div>
                                            <div class="rounded-sm border border-white/30 flex items-center px-px" style="width:12px;height:6px">
                                                <div class="bg-emerald-400 rounded-sm" style="height:100%;width:65%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-2.5 pt-1.5 grid grid-cols-3 gap-1">
                                        <div class="aspect-square rounded-md bg-gradient-to-br from-blue-600 to-blue-700 opacity-80"></div>
                                        <div class="aspect-square rounded-md bg-gradient-to-br from-rose-500 to-pink-600 opacity-80"></div>
                                        <div class="aspect-square rounded-md bg-gradient-to-br from-emerald-500 to-teal-600 opacity-80"></div>
                                        <div class="aspect-square rounded-md bg-gradient-to-br from-amber-400 to-orange-500 opacity-80"></div>
                                        <div class="aspect-square rounded-md bg-gradient-to-br from-violet-500 to-purple-600 opacity-80"></div>
                                        <div class="aspect-square rounded-md bg-gradient-to-br from-sky-400 to-blue-500 opacity-80"></div>
                                    </div>
                                    <div class="absolute left-0 right-0 h-px bg-gradient-to-r from-transparent via-blue-400/50 to-transparent auth-scan pointer-events-none"></div>
                                    <div class="absolute bottom-2.5 left-2 right-2 flex justify-between items-center bg-white/[0.05] border border-white/[0.07] rounded-lg px-2 py-1">
                                        <div class="w-1 h-1 rounded-full bg-white/30"></div>
                                        <div class="rounded-full bg-white/25" style="width:18px;height:3px"></div>
                                        <div class="w-1 h-1 rounded-full bg-white/30"></div>
                                    </div>
                                </div>
                                <div class="absolute rounded-l-sm bg-slate-600" style="right:-1px;top:70px;width:2px;height:28px"></div>
                                <div class="absolute rounded-l-sm bg-slate-600" style="right:-1px;top:106px;width:2px;height:18px"></div>
                                <div class="absolute rounded-r-sm bg-slate-600" style="left:-1px;top:56px;width:2px;height:18px"></div>
                                <div class="absolute rounded-r-sm bg-slate-600" style="left:-1px;top:80px;width:2px;height:28px"></div>
                            </div>
                            <div class="absolute bg-slate-950 rounded-full z-20 flex items-center justify-center gap-1" style="top:16px;left:50%;transform:translateX(-50%);width:38px;height:11px">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-800 border border-slate-700"></div>
                                <div class="w-1 h-1 rounded-full" style="background:rgba(59,130,246,.5)"></div>
                            </div>
                            <div class="absolute inset-0 rounded-[2.2rem] pointer-events-none" style="background:linear-gradient(135deg,rgba(255,255,255,.06) 0%,transparent 55%,rgba(255,255,255,.02) 100%)"></div>
                        </div>

                        <!-- Floating bubbles -->
                        <div class="auth-slide-1 absolute flex items-center gap-2 glass rounded-xl px-2.5 py-1.5 shadow-xl" style="top:-20px;left:-80px;min-width:150px">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-white text-[9px] font-bold leading-none">Order Confirmed</p>
                                <p class="text-slate-400 text-[8px] mt-0.5">₹1,49,900 · iPhone 15 Pro</p>
                            </div>
                            <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" style="animation:authPing 1.4s cubic-bezier(0,0,.2,1) infinite"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>

                        <div class="auth-slide-2 absolute flex items-center gap-2 glass rounded-xl px-2.5 py-1.5 shadow-xl" style="bottom:-16px;left:-85px;min-width:155px">
                            <div class="w-6 h-6 rounded-lg bg-blue-500/20 border border-blue-500/30 text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                            </div>
                            <div>
                                <p class="text-white text-[9px] font-bold leading-none">Repair In Progress</p>
                                <p class="text-slate-400 text-[8px] mt-0.5">Screen fixed · Est. 2 hrs</p>
                            </div>
                        </div>

                        <div class="auth-slide-3 absolute flex items-center gap-2 glass rounded-xl px-2.5 py-1.5 shadow-xl" style="top:50%;right:-85px;transform:translateY(-50%);min-width:130px">
                            <div class="w-6 h-6 rounded-lg bg-amber-500/20 border border-amber-500/30 text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            </div>
                            <div>
                                <div class="flex gap-px mb-0.5">
                                    <?php for($s=0;$s<5;$s++): ?>
                                    <svg class="text-amber-400 fill-current" style="width:7px;height:7px" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-slate-400 text-[8px]">4.9 · 50K+ Reviews</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 pb-1">
                    <div class="flex items-center gap-2.5 p-2 rounded-xl glass">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        </div>
                        <div>
                            <h4 class="text-[11px] font-bold text-white">Genuine OEM Certified</h4>
                            <p class="text-[9px] text-slate-500">Authorized flagship devices & verified parts.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 p-2 rounded-xl glass">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                            <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
                        </div>
                        <div>
                            <h4 class="text-[11px] font-bold text-white">Live Repair Workbench</h4>
                            <p class="text-[9px] text-slate-500">Diagnostic tracking, quotes & part allocation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- RIGHT PANEL — Form Area -->
        <div class="<?= $minimal ? 'col-span-12' : 'lg:col-span-7' ?> flex flex-col h-full <?= $minimal ? '' : 'lg:overflow-hidden' ?> overflow-y-auto bg-slate-50">
            <!-- Top bar -->
            <div class="shrink-0 py-2.5 px-4 sm:px-6 flex items-center <?= $minimal ? 'justify-end' : 'justify-between' ?> <?= $minimal ? '' : 'border-b border-slate-200/60 bg-white/80 backdrop-blur-sm' ?>">
                <?php if (!$minimal): ?>
                <a href="<?= url('/index.php') ?>" class="inline-flex items-center gap-2 group">
                    <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                    </div>
                    <span class="font-black text-slate-900 text-sm tracking-tight">APEX<span class="text-blue-600">MOBILE</span></span>
                </a>
                <?php endif; ?>

                <a href="<?= url('/index.php') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 transition px-2 py-1 rounded-lg hover:bg-slate-100">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Back to Storefront</span>
                </a>
            </div>

            <!-- Form Container -->
            <main id="authFormContent" tabindex="-1" class="flex-1 flex flex-col justify-center px-4 sm:px-6 lg:px-10 max-w-lg w-full mx-auto py-3 focus:outline-none">
                <?php
                $successes = flash('success');
                $errors = flash('error');
                ?>
                <?php if (!empty($successes) || !empty($errors)): ?>
                <div class="mb-3 space-y-2">
                    <?php foreach ($successes as $m): ?>
                    <div class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span><?= e($m) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php foreach ($errors as $m): ?>
                    <div class="flex items-center gap-2 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium">
                        <i data-lucide="alert-octagon" class="w-4 h-4 text-rose-600 shrink-0"></i>
                        <span><?= e($m) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/70 border border-slate-200/80 p-6 sm:p-7">
