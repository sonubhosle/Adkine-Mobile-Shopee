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
            height: 400px;
        }
        .hero-offer-card {
            position: relative;
            min-width: 0;
            overflow: hidden;
            padding: 8px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 20px;
            background-color: #fff;
        }
        .hero-offer-card img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 14px;
            transition: transform .5s ease;
        }
        .hero-offer-card > div { text-shadow: 0 1px 5px rgba(0,0,0,.8); }
        .hero-offer-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 8px;
            padding: 6px 12px;
            border-radius: 9999px;
            background: #fff;
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
            text-shadow: none;
            transition: background-color .2s ease, transform .2s ease;
        }
        .hero-offer-cta:hover { background: #e2e8f0; transform: translateY(-1px); }
        .hero-offer-phone { grid-area: phone; }
        .hero-offer-repair { grid-area: repair; }
        .hero-offer-audio { grid-area: audio; }
        .hero-offer-accessories { grid-area: accessories; }
        .hero-offer-special { grid-area: offers; }
        @media (max-width: 767px) {
            .hero-masonry {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                grid-template-rows: repeat(2, minmax(0, 1fr)) minmax(0, 1.15fr);
                grid-template-areas:
                    "phone repair"
                    "audio accessories"
                    "offers offers";
                height: 400px;
            }
        }
    </style>

    <div class="px-6 relative z-10 py-4 lg:py-5">
        <div class="">
            <div class="hero-masonry">
                <div class="hero-offer-card hero-offer-phone group">
                    <img src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=500&q=80" alt="Shop smartphones" fetchpriority="high">
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <h2 class="text-sm sm:text-base font-extrabold text-white leading-tight">Smartphones</h2>
                        <a href="<?= url('/shop.php?category=smartphones') ?>" class="hero-offer-cta">Shop phones</a>
                    </div>
                </div>
                <div class="hero-offer-card hero-offer-repair group">
                    <img src="https://images.unsplash.com/photo-1588508065123-287b28e013da?auto=format&fit=crop&w=500&q=80" alt="Book a phone repair" loading="lazy" decoding="async">
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <h2 class="text-sm sm:text-base font-extrabold text-white leading-tight">Repairs</h2>
                        <a href="<?= url('/repair-book.php') ?>" class="hero-offer-cta">Book a repair</a>
                    </div>
                </div>
                <div class="hero-offer-card hero-offer-audio group">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80" alt="Browse headphones and audio" loading="lazy" decoding="async">
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <h2 class="text-sm sm:text-base font-extrabold text-white leading-tight">Audio</h2>
                        <a href="<?= url('/shop.php?category=earphones-audio') ?>" class="hero-offer-cta">Shop audio</a>
                    </div>
                </div>
                <div class="hero-offer-card hero-offer-accessories group">
                    <img src="https://images.unsplash.com/photo-1605236453806-6ff36851218e?auto=format&fit=crop&w=600&q=80" alt="Browse phone accessories" loading="lazy" decoding="async">
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-3 sm:p-4">
                        <h2 class="text-sm sm:text-base font-extrabold text-white leading-tight">Accessories</h2>
                        <a href="<?= url('/shop.php?category=mobile-covers') ?>" class="hero-offer-cta">Shop accessories</a>
                    </div>
                </div>
                <div class="hero-offer-card hero-offer-special group">
                    <img src="https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=700&q=85" alt="Discover current mobile shop offers" loading="lazy" decoding="async">
                    <div class="absolute bottom-0 left-0 right-0 z-10 p-4 sm:p-5">
                        <h2 class="text-lg sm:text-xl font-black text-white leading-tight">Offers</h2>
                        <a href="<?= url('/shop.php?sort=price_low') ?>" class="hero-offer-cta">View offers</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
