<section class="relative text-white overflow-hidden">
    <style>
        .hero-masonry {
            display: grid;
            grid-template-columns: minmax(0, .85fr) minmax(0, 1.6fr) minmax(0, .95fr);
            grid-template-rows: repeat(2, minmax(0, 1fr));
            grid-template-areas:
                "phone audio offers"
                "repair accessories offers";
            gap: 12px;
            width: 100%;
            height: 420px;
        }
        .hero-offer-card {
            position: relative;
            min-width: 0;
            overflow: hidden;
            border-radius: 20px;
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.15);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.3s cubic-bezier(0.16, 1, 0.3, 1),
                        border-color 0.3s ease;
            animation: heroCardEntrance 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .hero-offer-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.28);
            box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.35);
        }
        .hero-offer-card img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 19px;
            transition: transform 0.65s cubic-bezier(0.16, 1, 0.3, 1), filter 0.5s ease;
            filter: brightness(0.88);
        }
        .hero-offer-card:hover img {
            transform: scale(1.05);
            filter: brightness(0.95);
        }
        .hero-card-overlay {
            position: absolute;
            inset: 0;
            border-radius: 19px;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.55) 45%, rgba(15, 23, 42, 0.08) 100%);
            transition: background 0.3s ease;
            pointer-events: none;
            z-index: 1;
        }
        .hero-offer-card:hover .hero-card-overlay {
            background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.62) 50%, rgba(15, 23, 42, 0.15) 100%);
        }
        .hero-offer-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            background: #ffffff;
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.01em;
            text-shadow: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            transition: background-color 0.2s ease,
                        transform 0.2s cubic-bezier(0.16, 1, 0.3, 1),
                        box-shadow 0.2s ease,
                        color 0.2s ease;
            position: relative;
            z-index: 2;
        }
        .hero-offer-cta:hover {
            background: #f8fafc;
            color: #2563eb;
            transform: translateY(-1px) scale(1.03);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
        }
        .hero-offer-cta:active {
            transform: translateY(0) scale(0.98);
        }
        .hero-offer-cta-special {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }
        .hero-offer-cta-special:hover {
            background: linear-gradient(135deg, #1d4ed8, #4338ca);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.5);
        }
        
        /* Staggered entrance animations */
        @keyframes heroCardEntrance {
            0% {
                opacity: 0;
                transform: translateY(18px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .hero-offer-phone       { grid-area: phone; animation-delay: 0.04s; }
        .hero-offer-repair      { grid-area: repair; animation-delay: 0.10s; }
        .hero-offer-audio       { grid-area: audio; animation-delay: 0.16s; }
        .hero-offer-accessories { grid-area: accessories; animation-delay: 0.22s; }
        .hero-offer-special     { grid-area: offers; animation-delay: 0.28s; }

        @media (max-width: 767px) {
            .hero-masonry {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-template-rows: repeat(2, minmax(0, 1fr)) minmax(0, 1.25fr);
                grid-template-areas:
                    "phone repair"
                    "audio accessories"
                    "offers offers";
                height: 440px;
                gap: 10px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-offer-card,
            .hero-offer-card img,
            .hero-offer-cta,
            .hero-offer-cta svg {
                animation: none !important;
                transition: none !important;
                transform: none !important;
            }
        }
    </style>

    <div class="px-4 sm:px-6 relative z-10 py-4 lg:py-5">
        <h1 class="sr-only">Adkine Mobile Shopee - Flagship Smartphones, Genuine Accessories &amp; Certified Repair Lab</h1>
        <div>
            <div class="hero-masonry">
                <!-- 1. Smartphones Card -->
                <div class="hero-offer-card hero-offer-phone group">
                    <img src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=500&q=80" alt="Shop smartphones" fetchpriority="high">
                    <div class="hero-card-overlay"></div>
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-blue-300 drop-shadow-sm mb-0.5">
                            <i data-lucide="smartphone" class="w-3 h-3 text-blue-400" aria-hidden="true"></i>
                            <span>Flagship</span>
                        </span>
                        <h2 class="text-sm sm:text-base font-black text-white leading-tight drop-shadow-md group-hover:text-blue-100 transition-colors">
                            Smartphones
                        </h2>
                        <a href="<?= url('/shop.php?category=smartphones') ?>" class="hero-offer-cta group/cta" aria-label="Shop Smartphones">
                            <span>Shop phones</span>
                            <i data-lucide="arrow-right" class="w-3 h-3 text-slate-500 group-hover/cta:translate-x-0.5 group-hover/cta:text-blue-600 transition-all" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- 2. Repairs Card -->
                <div class="hero-offer-card hero-offer-repair group">
                    <img src="https://images.unsplash.com/photo-1588508065123-287b28e013da?auto=format&fit=crop&w=500&q=80" alt="Book a phone repair" loading="lazy" decoding="async">
                    <div class="hero-card-overlay"></div>
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-emerald-300 drop-shadow-sm mb-0.5">
                            <i data-lucide="wrench" class="w-3 h-3 text-emerald-400" aria-hidden="true"></i>
                            <span>45-Min Lab</span>
                        </span>
                        <h2 class="text-sm sm:text-base font-black text-white leading-tight drop-shadow-md group-hover:text-emerald-100 transition-colors">
                            Repairs
                        </h2>
                        <a href="<?= url('/repair-book.php') ?>" class="hero-offer-cta group/cta" aria-label="Book a phone repair">
                            <span>Book repair</span>
                            <i data-lucide="arrow-right" class="w-3 h-3 text-slate-500 group-hover/cta:translate-x-0.5 group-hover/cta:text-emerald-600 transition-all" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- 3. Audio & Gadgets Card -->
                <div class="hero-offer-card hero-offer-audio group">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80" alt="Browse headphones and audio" loading="lazy" decoding="async">
                    <div class="hero-card-overlay"></div>
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-violet-300 drop-shadow-sm mb-0.5">
                            <i data-lucide="headphones" class="w-3 h-3 text-violet-400" aria-hidden="true"></i>
                            <span>Hi-Fi Audio</span>
                        </span>
                        <h2 class="text-sm sm:text-base font-black text-white leading-tight drop-shadow-md group-hover:text-violet-100 transition-colors">
                            Audio &amp; ANC
                        </h2>
                        <a href="<?= url('/shop.php?category=earphones-audio') ?>" class="hero-offer-cta group/cta" aria-label="Shop audio and earphones">
                            <span>Shop audio</span>
                            <i data-lucide="arrow-right" class="w-3 h-3 text-slate-500 group-hover/cta:translate-x-0.5 group-hover/cta:text-violet-600 transition-all" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- 4. Accessories Card -->
                <div class="hero-offer-card hero-offer-accessories group">
                    <img src="https://images.unsplash.com/photo-1605236453806-6ff36851218e?auto=format&fit=crop&w=600&q=80" alt="Browse phone accessories" loading="lazy" decoding="async">
                    <div class="hero-card-overlay"></div>
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] font-extrabold uppercase tracking-widest text-amber-300 drop-shadow-sm mb-0.5">
                            <i data-lucide="shield-check" class="w-3 h-3 text-amber-400" aria-hidden="true"></i>
                            <span>Original OEM</span>
                        </span>
                        <h2 class="text-sm sm:text-base font-black text-white leading-tight drop-shadow-md group-hover:text-amber-100 transition-colors">
                            Accessories
                        </h2>
                        <a href="<?= url('/shop.php?category=mobile-covers') ?>" class="hero-offer-cta group/cta" aria-label="Shop phone accessories">
                            <span>Shop gear</span>
                            <i data-lucide="arrow-right" class="w-3 h-3 text-slate-500 group-hover/cta:translate-x-0.5 group-hover/cta:text-amber-600 transition-all" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>

                <!-- 5. Special Offers Card -->
                <div class="hero-offer-card hero-offer-special group">
                    <img src="https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=700&q=85" alt="Discover current mobile shop offers" loading="lazy" decoding="async">
                    <div class="hero-card-overlay"></div>
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-4 sm:p-5">
                        <span class="inline-flex items-center gap-1.5 text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-rose-300 bg-rose-500/20 border border-rose-400/30 px-2.5 py-0.5 rounded-full mb-1.5 backdrop-blur-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                            <span>Limited Drop</span>
                        </span>
                        <h2 class="text-lg sm:text-xl font-black text-white leading-tight drop-shadow-lg group-hover:text-rose-100 transition-colors">
                            Special Deals
                        </h2>
                        <p class="text-[11px] sm:text-xs text-slate-300 mt-0.5 font-medium line-clamp-1 drop-shadow-sm">
                            Save up to 40% on top smartphones &amp; chargers
                        </p>
                        <a href="<?= url('/shop.php?sort=price_low') ?>" class="hero-offer-cta hero-offer-cta-special group/cta mt-2.5" aria-label="View special offers">
                            <span>View offers</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-white/80 group-hover/cta:translate-x-1 group-hover/cta:text-white transition-all" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
