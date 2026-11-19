<?php

?>
<footer class="bg-gray-50 text-gray-800 border-t border-gray-200">
  <div class=" px-4 sm:px-6 lg:px-8 py-12">
    <!-- Top Row: Brand + Socials -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-8">
      <!-- Brand Identity -->
      <div class="md:w-1/3">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-600 flex items-center justify-center text-white shadow-lg">
            <!-- You can replace the SVG with your logo if needed -->
            <svg class="w-6 h-6" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="6.5" y="2.5" width="19" height="27" rx="3.8" stroke="white" stroke-width="2"/>
              <path d="M16 8L10.5 22H13.6L14.7 19H17.3L18.4 22H21.5L16 8Z" fill="white"/>
              <path d="M16 11.5L15.1 16.5H16.9L16 11.5Z" fill="#1e1b4b"/>
              <path d="M16.8 13.5L13.8 19H17L15 25L21 18H17.5L19.2 13.5H16.8Z" fill="#facc15"/>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-extrabold tracking-tight text-gray-900">ADKINE <span class="text-indigo-600">MOBILE</span></h2>
            <p class="text-sm font-medium uppercase text-gray-500 mt-1">Smartphones • Certified Repairs • Gadgets</p>
          </div>
        </div>
        <p class="mt-4 text-sm text-gray-600 max-w-xs">
          Premier electronics retail showroom, certified smartphone chip‑level repair lab, and unified multi‑channel inventory ERP system.
        </p>
        <div class="mt-4 text-xs space-y-1 text-gray-500">
          <div><strong class="text-gray-900">Store Address:</strong> <?= e(setting('store_address', 'Electronics City Tech Park, Bengaluru')) ?></div>
          <div><strong class="text-gray-900">Customer Support:</strong> <?= e(setting('site_phone', '+91 98765 43210')) ?></div>
          <div><strong class="text-gray-900">GSTIN:</strong> <?= e(setting('store_gstin', '29AABCA1234A1Z5')) ?></div>
        </div>
        <!-- Social Icons -->
        <div class="flex gap-3 mt-4 text-gray-500">
          <a href="#" aria-label="Instagram" class="hover:text-gray-900 transition"><i class='bx bxl-instagram' class="w-5 h-5"></i></a>
          <a href="#" aria-label="Facebook" class="hover:text-gray-900 transition"><i class='bx bxl-facebook' class="w-5 h-5"></i></a>
          <a href="#" aria-label="Twitter" class="hover:text-gray-900 transition"><i class='bx bxl-twitter' class="w-5 h-5"></i></a>
        </div>
      </div>

      <!-- Links Columns -->
      <div class="grid grid-cols-2 gap-8 md:w-2/3">
        <!-- Customer Service -->
        <div>
          <h3 class="font-semibold text-gray-900 text-sm uppercase mb-4">Customer Service</h3>
          <ul class="space-y-2 text-sm text-gray-600">
            <li><a href="<?= url('/contact.php') ?>" class="hover:text-gray-900 transition">Contact Us</a></li>
            <li><a href="<?= url('/faq.php') ?>" class="hover:text-gray-900 transition">FAQs</a></li>
            <li><a href="<?= url('/shipping.php') ?>" class="hover:text-gray-900 transition">Shipping</a></li>
            <li><a href="<?= url('/returns.php') ?>" class="hover:text-gray-900 transition">Returns</a></li>
            <li><a href="<?= url('/track-order.php') ?>" class="hover:text-gray-900 transition">Order Tracking</a></li>
          </ul>
        </div>
        <!-- Shopping Categories -->
        <div>
          <h3 class="font-semibold text-gray-900 text-sm uppercase mb-4">Shop</h3>
          <ul class="space-y-2 text-sm text-gray-600">
            <li><a href="<?= url('/shop.php?category=smartphones') ?>" class="hover:text-gray-900 transition"><i class='bx bxs-mobile' class="w-5 h-5 mr-1 inline-block align-middle"></i>Smartphones</a></li>
            <li><a href="<?= url('/shop.php?category=chargers-adapters') ?>" class="hover:text-gray-900 transition"><i class='bx bx-bolt' class="w-5 h-5 mr-1 inline-block align-middle"></i>Fast Chargers &amp; GaN</a></li>
            <li><a href="<?= url('/shop.php?category=earphones-audio') ?>" class="hover:text-gray-900 transition"><i class='bx bx-headphone' class="w-5 h-5 mr-1 inline-block align-middle"></i>TWS &amp; Headphones</a></li>
            <li><a href="<?= url('/shop.php?category=smartwatches') ?>" class="hover:text-gray-900 transition"><i data-lucide="watch" class="w-4 h-4 mr-1 inline-block align-middle"></i>Smartwatches</a></li>
            <li><a href="<?= url('/shop.php?category=mobile-covers') ?>" class="hover:text-gray-900 transition"><i data-lucide="shield" class="w-4 h-4 mr-1 inline-block align-middle"></i>Cases &amp; Protectors</a></li>
          </ul>
        </div>
        <!-- Newsletter -->
        <div class="col-span-2">
          <h3 class="font-semibold text-gray-900 text-sm uppercase mb-4">Stay Updated</h3>
          <form action="<?= url('/subscribe.php') ?>" method="post" class="flex flex-col sm:flex-row gap-2 items-start sm:items-center">
            <label for="footer-email" class="sr-only">Email address</label>
            <input type="email" id="footer-email" name="email" required placeholder="Your email" class="flex-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 transition">
              Subscribe
            </button>
          </form>
          <p class="mt-2 text-xs text-gray-500">We respect your privacy. Unsubscribe any time.</p>
        </div>
        <!-- Payment & Security -->
        <div class="col-span-2">
          <h3 class="font-semibold text-gray-900 text-sm uppercase mb-4">Secure Shopping</h3>
          <div class="flex items-center gap-4 text-gray-500 text-sm">
            <i data-lucide="shield-check" class="w-5 h-5"></i>
            <span>Razorpay • 256‑Bit SSL</span>
            <i data-lucide="credit-card" class="w-5 h-5"></i>
            <span>UPI • Cards • Netbanking</span>
          </div>
        </div>
      </div>
    </div>
    <!-- Bottom Bar -->
    <div class="border-t border-gray-300 mt-10 pt-4 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500">
      <p>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'Apex Mobile Hub & ERP')) ?>. All rights reserved.</p>
      <div class="flex gap-4 mt-2 sm:mt-0">
        <a href="<?= url('/privacy.php') ?>" class="hover:text-gray-900 transition">Privacy Policy</a>
        <a href="<?= url('/terms.php') ?>" class="hover:text-gray-900 transition">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>
