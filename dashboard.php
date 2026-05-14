<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-400"></i> Dashboard
            </h1>
            <p class="text-gray-500 text-sm mt-0.5">Welcome back, <strong class="text-gray-300"><?= htmlspecialchars($user['username']) ?></strong></p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-panel border border-gray-700/50 p-5 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-servers" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Active Servers</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-600/15 flex items-center justify-center">
                        <i class="fas fa-server text-blue-400"></i>
                    </div>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 p-5 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-attacks" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Attacks</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-purple-600/15 flex items-center justify-center">
                        <i class="fas fa-database text-purple-400"></i>
                    </div>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 p-5 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-running" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Running Now</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-green-600/15 flex items-center justify-center">
                        <i class="fas fa-bolt text-green-400"></i>
                    </div>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 p-5 rounded-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <p id="stat-users" class="text-3xl font-bold text-white mb-1">-</p>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Registered Users</p>
                        <p class="text-xs text-yellow-400 mt-1.5 font-medium"><span id="stat-paid-users">-</span> <span class="text-gray-500">Paid</span></p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-yellow-600/15 flex items-center justify-center">
                        <i class="fas fa-users text-yellow-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Status + Chart row -->
        <div class="grid lg:grid-cols-3 gap-5">

            <!-- Network Status -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-5">
                <h2 class="text-sm font-bold text-white mb-4 flex items-center gap-2 uppercase tracking-wide">
                    <i class="fas fa-signal text-green-400"></i> Network Status
                </h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">Network</span>
                        <span class="flex items-center gap-1.5 text-green-400 text-sm font-medium">
                            <span class="status-dot status-live"></span> Online
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">API</span>
                        <span class="flex items-center gap-1.5 text-green-400 text-sm font-medium">
                            <span class="status-dot status-live"></span> Operational
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">Attack Layer 4</span>
                        <span class="flex items-center gap-1.5 text-green-400 text-sm font-medium">
                            <span class="status-dot status-live"></span> Ready
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 text-sm">Attack Layer 7</span>
                        <span class="flex items-center gap-1.5 text-green-400 text-sm font-medium">
                            <span class="status-dot status-live"></span> Ready
                        </span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-700/50">
                    <a href="panel.php" class="launch-btn text-sm py-2">
                        <i class="fas fa-bolt mr-2"></i> Go to Hub
                    </a>
                </div>
            </div>

            <!-- Chart (spans 2 cols) -->
            <div class="lg:col-span-2 bg-panel border border-gray-700/50 p-5 rounded-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wide">Attacks (Last 7 Days)</h2>
                        <p class="text-gray-500 text-xs mt-0.5">Total attacks per day</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-sm bg-blue-500"></span>
                        <span class="text-xs text-gray-500">Attacks</span>
                    </div>
                </div>
                <div class="h-48 relative" id="chart-container">
                    <div id="chart-spinner" class="absolute inset-0 flex items-center justify-center text-gray-600">
                        <i class="fas fa-spinner fa-spin text-2xl"></i>
                    </div>
                    <canvas id="attacks-chart" class="hidden"></canvas>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let attacksChart = null;

async function loadDashboard() {
    try {
        const res = await fetch('api/dashboard.php');
        const data = await res.json();
        
        if (res.ok) {
            document.getElementById('stat-servers').textContent = data.active_servers.toLocaleString();
            document.getElementById('stat-attacks').textContent = data.total_attacks.toLocaleString();
            document.getElementById('stat-running').textContent = data.running_attacks.toLocaleString();
            const usersEl = document.getElementById('stat-users');
            if (usersEl) {
                usersEl.textContent = data.registered_users !== null ? data.registered_users.toLocaleString() : '—';
            }
            const paidEl = document.getElementById('stat-paid-users');
            if (paidEl) {
                paidEl.textContent = (data.paid_users !== null && data.paid_users !== undefined) ? data.paid_users.toLocaleString() : '—';
            }
            
            // Show chart, hide spinner
            document.getElementById('chart-spinner').classList.add('hidden');

            const labels = data.attacks_last_7_days.map(d => d.name);
            const values = data.attacks_last_7_days.map(d => d.attacks);
            const hasData = values.some(v => v > 0);

            if (!hasData) {
                document.getElementById('chart-container').innerHTML =
                    '<div class="absolute inset-0 flex flex-col items-center justify-center text-center">' +
                    '<i class="fas fa-chart-bar text-3xl text-gray-700 mb-2"></i>' +
                    '<p class="text-gray-600 text-sm">No attacks in the last 7 days</p>' +
                    '</div>';
                return;
            }

            document.getElementById('attacks-chart').classList.remove('hidden');

            const ctx = document.getElementById('attacks-chart').getContext('2d');
            if (attacksChart) {
                attacksChart.data.labels = labels;
                attacksChart.data.datasets[0].data = values;
                attacksChart.update();
            } else {
                attacksChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Attacks',
                            data: values,
                            backgroundColor: 'rgba(59,130,246,0.5)',
                            borderColor: 'rgba(59,130,246,0.9)',
                            borderWidth: 1,
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ctx.parsed.y + ' attacks' } }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: '#6b7280', font: { size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(255,255,255,0.05)' },
                                ticks: { color: '#6b7280', font: { size: 11 }, precision: 0 }
                            }
                        }
                    }
                });
            }
        }
    } catch (err) {
        console.error('Failed to load dashboard:', err);
    }
}

loadDashboard();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
