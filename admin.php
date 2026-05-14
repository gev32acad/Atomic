<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_admin();
$csrf_token = generate_csrf_token();
$page_title = 'Admin Panel';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen p-3 sm:p-6 overflow-x-hidden">
    <div class="max-w-7xl mx-auto">
        
        <!-- Tabs -->
        <div class="flex flex-nowrap gap-2 mb-6 overflow-x-auto pb-1 -mx-1 px-1">
            <button onclick="switchAdminTab('users')" id="admin-tab-users" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-blue-600 text-white transition shrink-0">
                <i class="fas fa-users"></i> Users
            </button>
            <button onclick="switchAdminTab('plans')" id="admin-tab-plans" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0">
                <i class="fas fa-credit-card"></i> Plans
            </button>
            <button onclick="switchAdminTab('orders')" id="admin-tab-orders" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0">
                <i class="fas fa-receipt"></i> Orders
                <span id="orders-badge" class="hidden bg-yellow-500 text-black text-xs rounded-full px-1.5 py-0.5 font-bold"></span>
            </button>
            <button onclick="switchAdminTab('attacks')" id="admin-tab-attacks" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0">
                <i class="fas fa-history"></i> Attacks
            </button>
            <button onclick="switchAdminTab('methods')" id="admin-tab-methods" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0">
                <i class="fas fa-bomb"></i> Methods
            </button>
            <button onclick="switchAdminTab('servers')" id="admin-tab-servers" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0">
                <i class="fas fa-server"></i> Servers
            </button>
            <button onclick="switchAdminTab('blacklist')" id="admin-tab-blacklist" class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0">
                <i class="fas fa-ban"></i> Blacklist
            </button>
        </div>
        
        <!-- Users Tab -->
        <div id="admin-users" class="admin-tab-content">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-semibold text-white">User Management</h3>
                    <div class="flex gap-2">
                        <input type="text" id="user-search" placeholder="Search users…"
                            oninput="filterUsers(this.value)"
                            class="bg-background border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-blue-500 w-44">
                        <button onclick="showAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                            <i class="fas fa-plus mr-1"></i> Add User
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Plan</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Role</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Joined</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="users-table" class="text-gray-300">
                            <tr><td colspan="5" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading users...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Plans Tab -->
        <div id="admin-plans" class="admin-tab-content hidden">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Plan Management</h3>
                    <button onclick="showAddPlanModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Plan
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3 hidden md:table-cell">Price</th>
                                <th class="px-4 py-3 hidden lg:table-cell">Days</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Concurrents</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Max Seconds</th>
                                <th class="px-4 py-3">Premium</th>
                                <th class="px-4 py-3 hidden lg:table-cell">API</th>
                                <th class="px-4 py-3 hidden lg:table-cell">Schedule</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="plans-table" class="text-gray-300">
                            <tr><td colspan="9" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading plans...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Orders Tab -->
        <div id="admin-orders" class="admin-tab-content hidden">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-semibold text-white">Order Management</h3>
                    <div class="flex flex-wrap gap-2 items-center">
                        <input type="text" id="order-search" placeholder="Search user / plan…"
                            oninput="filterOrdersSearch(this.value)"
                            class="bg-background border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-blue-500 w-44">
                        <button onclick="filterOrders('all')" id="order-filter-all" class="text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white transition">All</button>
                        <button onclick="filterOrders('pending')" id="order-filter-pending" class="text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition">Pending</button>
                        <button onclick="filterOrders('approved')" id="order-filter-approved" class="text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition">Approved</button>
                        <button onclick="filterOrders('rejected')" id="order-filter-rejected" class="text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition">Rejected</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Order ID</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Plan</th>
                                <th class="px-4 py-3 hidden md:table-cell">Payment</th>
                                <th class="px-4 py-3 hidden lg:table-cell">TX Hash</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 hidden md:table-cell">Date</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table" class="text-gray-300">
                            <tr><td colspan="8" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading orders...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Methods Tab -->
        <div id="admin-methods" class="admin-tab-content hidden">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Method Management</h3>
                    <button onclick="showAddMethodModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Method
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3 hidden md:table-cell">Description</th>
                                <th class="px-4 py-3">Layer</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Category</th>
                                <th class="px-4 py-3">Premium</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="methods-table" class="text-gray-300">
                            <tr><td colspan="6" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading methods...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Servers Tab -->
        <div id="admin-servers" class="admin-tab-content hidden">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Backend Server Management</h3>
                    <button onclick="showAddServerModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Server
                    </button>
                </div>
                <p class="text-gray-500 text-xs mb-4">
                    Add backend attack servers. The API URL supports placeholders:
                    <code class="text-blue-400">{host}</code>, <code class="text-blue-400">{port}</code>,
                    <code class="text-blue-400">{time}</code>, <code class="text-blue-400">{method}</code>,
                    <code class="text-blue-400">{concurrents}</code>
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Layer</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Methods</th>
                                <th class="px-4 py-3 hidden lg:table-cell">Max Slots</th>
                                <th class="px-4 py-3 hidden lg:table-cell">API URL</th>
                                <th class="px-4 py-3 hidden md:table-cell">Enabled</th>
                                <th class="px-4 py-3 hidden md:table-cell">Status</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="servers-table" class="text-gray-300">
                            <tr><td colspan="8" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading servers...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attacks Tab -->
        <div id="admin-attacks" class="admin-tab-content hidden">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <h3 class="text-lg font-semibold text-white">All Attacks</h3>
                    <input type="text" id="attacks-admin-search" placeholder="Search user / target / method…"
                        oninput="filterAdminAttacks(this.value)"
                        class="bg-background border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-gray-300 placeholder-gray-600 focus:outline-none focus:border-blue-500 w-60">
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Target</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Layer</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Duration</th>
                                <th class="px-4 py-3 hidden md:table-cell">Started</th>
                            </tr>
                        </thead>
                        <tbody id="admin-attacks-table" class="text-gray-300">
                            <tr><td colspan="6" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading attacks...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Blacklist Tab -->
        <div id="admin-blacklist" class="admin-tab-content hidden">
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">IP/URL Blacklist</h3>
                    <button onclick="showAddBlacklistModal()" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-plus mr-1"></i> Add Entry
                    </button>
                </div>
                <p class="text-gray-500 text-xs mb-4">Targets on this list cannot be attacked from any account.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-gray-400 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Value</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Note</th>
                                <th class="px-4 py-3 hidden md:table-cell">Added</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="blacklist-table" class="text-gray-300">
                            <tr><td colspan="5" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading blacklist...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal -->
<div id="admin-modal" class="hidden fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
    <div class="bg-panel border border-gray-700 rounded-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 id="modal-title" class="text-lg font-semibold text-white"></h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="modal-form" class="space-y-4">
            <div id="modal-fields"></div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                Save
            </button>
        </form>
    </div>
</div>

<script src="assets/js/admin.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
