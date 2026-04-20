<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-panel border border-gray-700/50 p-6 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-servers" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-sm text-gray-400">Active Servers</p>
                    </div>
                    <i class="fas fa-server text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 p-6 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-attacks" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-sm text-gray-400">Total Attacks</p>
                    </div>
                    <i class="fas fa-database text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 p-6 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-running" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-sm text-gray-400">Running Attacks</p>
                    </div>
                    <i class="fas fa-bolt text-blue-400 text-xl"></i>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 p-6 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-users" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-sm text-gray-400">Registered Users</p>
                    </div>
                    <i class="fas fa-users text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Chart -->
        <div class="bg-panel border border-gray-700/50 p-8 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-white mb-1">Attacks Chart</h2>
                    <p class="text-gray-400 text-sm">Showing total attacks per day for the last 7 days</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-sm text-gray-400">Attacks</span>
                </div>
            </div>
            <div class="h-64 flex items-end gap-2" id="chart-container">
                <p class="text-gray-400 w-full text-center">Loading chart...</p>
            </div>
        </div>
    </div>
</div>

<script>
async function loadDashboard() {
    try {
        const res = await fetch('api/dashboard.php');
        const data = await res.json();
        
        if (res.ok) {
            document.getElementById('stat-servers').textContent = data.active_servers.toLocaleString();
            document.getElementById('stat-attacks').textContent = data.total_attacks.toLocaleString();
            document.getElementById('stat-running').textContent = data.running_attacks.toLocaleString();
            document.getElementById('stat-users').textContent = data.registered_users.toLocaleString();
            
            // Render chart
            const container = document.getElementById('chart-container');
            const maxAttacks = Math.max(...data.attacks_last_7_days.map(d => d.attacks), 1);
            container.innerHTML = '';
            
            data.attacks_last_7_days.forEach(day => {
                const height = Math.max((day.attacks / maxAttacks) * 100, 5);
                const bar = document.createElement('div');
                bar.className = 'flex-1 flex flex-col items-center gap-2';
                bar.innerHTML = `
                    <span class="text-xs text-gray-400">${day.attacks}</span>
                    <div class="w-full rounded-t-lg relative" style="height: ${height}%">
                        <div class="absolute inset-0 bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg opacity-80"></div>
                    </div>
                    <span class="text-xs text-gray-400">${day.name}</span>
                `;
                container.appendChild(bar);
            });
        }
    } catch (err) {
        console.error('Failed to load dashboard:', err);
    }
}

loadDashboard();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
