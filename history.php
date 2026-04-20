<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-white">Attack History</h2>
                <div class="flex items-center gap-3">
                    <span id="history-count" class="text-sm text-gray-400"></span>
                    <div class="flex gap-1">
                        <button onclick="prevPage()" id="btn-prev" class="px-3 py-1 rounded bg-gray-700 text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button onclick="nextPage()" id="btn-next" class="px-3 py-1 rounded bg-gray-700 text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-gray-400 border-b border-gray-700">
                        <tr>
                            <th class="px-4 py-3">Target</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Layer</th>
                            <th class="px-4 py-3">Port</th>
                            <th class="px-4 py-3">Duration</th>
                            <th class="px-4 py-3">Started</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody id="history-table" class="text-gray-300">
                        <tr><td colspan="7" class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading history...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;

async function loadHistory(page = 1) {
    try {
        const res = await fetch(`api/history.php?page=${page}`);
        const data = await res.json();
        
        if (!res.ok) {
            document.getElementById('history-table').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Failed to load history</td></tr>';
            return;
        }
        
        currentPage = data.pagination.page;
        totalPages = data.pagination.total_pages;
        
        document.getElementById('history-count').textContent = `${data.pagination.total} total • Page ${currentPage}/${totalPages || 1}`;
        document.getElementById('btn-prev').disabled = currentPage <= 1;
        document.getElementById('btn-next').disabled = currentPage >= totalPages;
        
        const tbody = document.getElementById('history-table');
        
        if (!data.attacks.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-400">No attack history found</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        data.attacks.forEach(a => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';
            
            const statusColors = {
                'running': 'bg-green-600/20 text-green-400',
                'completed': 'bg-gray-600/20 text-gray-400',
                'stopped': 'bg-red-600/20 text-red-400'
            };
            const statusClass = statusColors[a.status] || statusColors.completed;
            
            const startDate = new Date(a.start_time);
            const timeStr = startDate.toLocaleDateString() + ' ' + startDate.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            
            tr.innerHTML = `
                <td class="px-4 py-3 text-white font-mono text-xs">${escapeHtml(a.target)}</td>
                <td class="px-4 py-3"><span class="bg-blue-600/20 text-blue-400 px-2 py-0.5 rounded text-xs">${escapeHtml(a.method)}</span></td>
                <td class="px-4 py-3">${escapeHtml(a.layer)}</td>
                <td class="px-4 py-3">${escapeHtml(String(a.port))}</td>
                <td class="px-4 py-3">${a.time}s</td>
                <td class="px-4 py-3 text-xs">${timeStr}</td>
                <td class="px-4 py-3"><span class="${statusClass} px-2 py-0.5 rounded text-xs">${a.status}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error('Failed to load history:', err);
        document.getElementById('history-table').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Connection error</td></tr>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function prevPage() { if (currentPage > 1) loadHistory(currentPage - 1); }
function nextPage() { if (currentPage < totalPages) loadHistory(currentPage + 1); }

loadHistory();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
