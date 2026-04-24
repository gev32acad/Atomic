</main>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const overlay = document.getElementById('sidebar-overlay');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const toggle = document.getElementById('sidebar-toggle');
            const isOpen = !mobileSidebar.classList.contains('hidden');
            overlay.classList.toggle('hidden', isOpen);
            mobileSidebar.classList.toggle('hidden', isOpen);
            if (toggle) toggle.setAttribute('aria-expanded', String(!isOpen));
        }
        
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
    </script>
</body>
</html>
