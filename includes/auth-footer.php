                </div>
            </main>

            <!-- Footer -->
            <?php if (!$minimal): ?>
            <div class="shrink-0 py-2 px-6 border-t border-slate-200/60 bg-white/50 flex flex-col sm:flex-row items-center justify-between gap-1 max-w-lg w-full mx-auto">
                <div class="flex items-center gap-1 text-[10px] text-slate-400">
                    <i data-lucide="lock" class="w-3 h-3 text-emerald-500"></i>
                    <span>256-Bit SSL Encrypted</span>
                </div>
                <span class="text-[10px] text-slate-400">&copy; <?= date('Y') ?> Apex Mobile Hub. All rights reserved.</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>

