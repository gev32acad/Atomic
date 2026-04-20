</main>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const overlay = document.getElementById('sidebar-overlay');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            overlay.classList.toggle('hidden');
            mobileSidebar.classList.toggle('hidden');
        }
        
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
    </script>
</body>
</html>
