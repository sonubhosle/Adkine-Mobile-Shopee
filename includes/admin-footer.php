        </main>
    </div>

    <?php require __DIR__ . '/language-script.php'; ?>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('mobileSidebarBackdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }
        lucide.createIcons();
    </script>
</body>
</html>

