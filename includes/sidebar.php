<?php if ($logged_in): ?>
<!-- Sidebar -->
<aside id="sidebar" class="hidden lg:block fixed left-0 top-[57px] bottom-0 w-64 bg-panel border-r border-gray-700/50 overflow-y-auto z-30">
    <nav class="p-4 space-y-2">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'dashboard' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-chart-line w-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="panel.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'panel' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-rocket w-5"></i>
            <span>Panel</span>
        </a>
        <a href="api-docs.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'api-docs' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-code w-5"></i>
            <span>API</span>
        </a>
        <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'profile' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-user w-5"></i>
            <span>Profile</span>
        </a>
        <?php if ($admin): ?>
        <a href="admin.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'admin' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-shield-alt w-5"></i>
            <span>Admin</span>
        </a>
        <?php endif; ?>
        <hr class="border-gray-700 my-4">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<!-- Mobile sidebar overlay -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/50 z-20 lg:hidden" onclick="toggleSidebar()"></div>
<aside id="mobile-sidebar" class="hidden fixed left-0 top-[57px] bottom-0 w-64 bg-panel border-r border-gray-700/50 overflow-y-auto z-30 lg:hidden">
    <nav class="p-4 space-y-2">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'dashboard' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-chart-line w-5"></i>
            <span>Dashboard</span>
        </a>
        <a href="panel.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'panel' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-rocket w-5"></i>
            <span>Panel</span>
        </a>
        <a href="api-docs.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'api-docs' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-code w-5"></i>
            <span>API</span>
        </a>
        <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'profile' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-user w-5"></i>
            <span>Profile</span>
        </a>
        <?php if ($admin): ?>
        <a href="admin.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition <?= $current_page === 'admin' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700/50 hover:text-white' ?>">
            <i class="fas fa-shield-alt w-5"></i>
            <span>Admin</span>
        </a>
        <?php endif; ?>
        <hr class="border-gray-700 my-4">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition">
            <i class="fas fa-sign-out-alt w-5"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>
<?php endif; ?>

<!-- Main Content -->
<main class="<?= $logged_in ? 'lg:ml-64' : '' ?> flex-1 min-h-[calc(100vh-57px)]">
