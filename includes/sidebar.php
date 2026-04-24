<?php if ($logged_in): 
// Sidebar navigation items (single definition to avoid duplication) (#7)
$nav_items = [
    ['href' => 'dashboard.php', 'icon' => 'fa-chart-line', 'label' => 'Dashboard', 'page' => 'dashboard'],
    ['href' => 'panel.php', 'icon' => 'fa-bolt', 'label' => 'Hub', 'page' => 'panel'],
    ['href' => 'history.php', 'icon' => 'fa-history', 'label' => 'History', 'page' => 'history'],
    ['href' => 'hub.php', 'icon' => 'fa-globe', 'label' => 'Free Hub', 'page' => 'hub'],
    ['href' => 'store.php', 'icon' => 'fa-shopping-cart', 'label' => 'Store', 'page' => 'store'],
    ['href' => 'api-docs.php', 'icon' => 'fa-code', 'label' => 'API', 'page' => 'api-docs'],
    ['href' => 'profile.php', 'icon' => 'fa-user', 'label' => 'Profile', 'page' => 'profile'],
];

// Plan badge styling
$plan_name = $user['plan'] ?? 'Starter';
if ($plan_name === 'Starter') {
    $badge_class = 'plan-badge plan-badge-free';
    $badge_icon  = 'fa-user';
} elseif (in_array($plan_name, ['Advanced', 'Enterprise'])) {
    $badge_class = 'plan-badge plan-badge-premium';
    $badge_icon  = 'fa-star';
} else {
    $badge_class = 'plan-badge plan-badge-paid';
    $badge_icon  = 'fa-check-circle';
}

function render_nav_items($nav_items, $current_page, $admin) {
    $html = '';
    foreach ($nav_items as $item) {
        $active = $current_page === $item['page'];
        $classes = $active 
            ? 'bg-blue-600 text-white' 
            : 'text-gray-300 hover:bg-gray-700/50 hover:text-white';
        $html .= '<a href="' . htmlspecialchars($item['href']) . '" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition ' . $classes . '">';
        $html .= '<i class="fas ' . htmlspecialchars($item['icon']) . ' w-5"></i>';
        $html .= '<span>' . htmlspecialchars($item['label']) . '</span></a>';
    }
    if ($admin) {
        $active = $current_page === 'admin';
        $classes = $active 
            ? 'bg-blue-600 text-white' 
            : 'text-gray-300 hover:bg-gray-700/50 hover:text-white';
        $html .= '<a href="admin.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg transition ' . $classes . '">';
        $html .= '<i class="fas fa-shield-alt w-5"></i><span>Admin</span></a>';
    }
    $html .= '<hr class="border-gray-700 my-4">';
    $html .= '<a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-red-400 hover:bg-red-900/20 hover:text-red-300 transition">';
    $html .= '<i class="fas fa-sign-out-alt w-5"></i><span>Logout</span></a>';
    return $html;
}
?>
<!-- Sidebar -->
<aside id="sidebar" class="hidden lg:block fixed left-0 top-[57px] bottom-0 w-64 bg-panel border-r border-gray-700/50 overflow-y-auto z-30">
    <nav class="p-4 space-y-2">
        <!-- Plan badge -->
        <div class="flex items-center justify-between px-4 py-2 mb-1">
            <span class="text-xs text-gray-600 font-medium uppercase tracking-wide">Navigation</span>
            <span class="<?= $badge_class ?>">
                <i class="fas <?= $badge_icon ?> text-xs"></i>
                <?= htmlspecialchars($plan_name) ?>
            </span>
        </div>
        <?= render_nav_items($nav_items, $current_page, $admin) ?>
    </nav>
</aside>

<!-- Mobile sidebar overlay -->
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/50 z-20 lg:hidden" onclick="toggleSidebar()"></div>
<aside id="mobile-sidebar" class="hidden fixed left-0 top-[57px] bottom-0 w-64 bg-panel border-r border-gray-700/50 overflow-y-auto z-30 lg:hidden">
    <nav class="p-4 space-y-2">
        <div class="flex items-center justify-between px-4 py-2 mb-1">
            <span class="text-xs text-gray-600 font-medium uppercase tracking-wide">Navigation</span>
            <span class="<?= $badge_class ?>">
                <i class="fas <?= $badge_icon ?> text-xs"></i>
                <?= htmlspecialchars($plan_name) ?>
            </span>
        </div>
        <?= render_nav_items($nav_items, $current_page, $admin) ?>
    </nav>
</aside>
<?php endif; ?>

<!-- Main Content -->
<main class="<?= $logged_in ? 'lg:ml-64' : '' ?> flex-1 min-h-[calc(100vh-57px)]">
