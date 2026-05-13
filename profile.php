<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
$page_title = 'Profile';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$avatar = strtoupper(implode('', array_map(function($n) { return isset($n[0]) ? $n[0] : ''; }, preg_split('/[\s_]+/', $user['username']))));
$join_date = date('M j, Y', strtotime($user['join_date']));
$s = $user['max_seconds'];
if ($s < 60) {
    $dur_display = $s . 's';
} elseif ($s < 3600) {
    $dur_display = floor($s / 60) . 'm';
} else {
    $h = floor($s / 3600);
    $m = floor(($s % 3600) / 60);
    $dur_display = $h . 'h' . ($m > 0 ? ' ' . $m . 'm' : '');
}
$exp_date = $user['expiration_date'] ? date('M j, Y', strtotime($user['expiration_date'])) : 'No expiration';
?>

<div class="p-6">
    <div class="max-w-xl mx-auto">
        
        <!-- Info Notice -->
        <div class="mb-8 flex items-center gap-3 text-sm bg-blue-500/10 border border-blue-500/20 rounded-xl px-6 py-4">
            <i class="fas fa-info-circle text-blue-400"></i>
            <p class="text-blue-300">
                We never request sensitive information. Only your User ID or Username is required when purchasing a plan.
                <a href="store.php" class="underline hover:text-blue-200 ml-1">Buy a plan →</a>
            </p>
        </div>
        
        <!-- Avatar -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 rounded-full bg-blue-600 mx-auto flex items-center justify-center text-2xl font-bold text-white">
                <?= htmlspecialchars($avatar) ?>
            </div>
            <h2 class="text-xl font-semibold text-white mt-2"><?= htmlspecialchars($user['username']) ?></h2>
        </div>
        
        <!-- User ID -->
        <div class="bg-panel border border-gray-600 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-400">User ID</span>
                <button onclick="copyToClipboard('<?= htmlspecialchars($user['id']) ?>')" class="text-sm text-gray-400 hover:text-blue-400">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <code class="block text-sm text-white break-words"><?= htmlspecialchars($user['id']) ?></code>
        </div>
        
        <!-- Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Plan</p>
                <p class="text-white font-semibold"><?= htmlspecialchars($user['plan']) ?></p>
            </div>
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Role</p>
                <p class="text-white font-semibold"><?= htmlspecialchars($user['role']) ?></p>
            </div>
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Member Since</p>
                <p class="text-white font-semibold"><?= $join_date ?></p>
            </div>
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Max Concurrents</p>
                <p class="text-white font-semibold"><?= $user['max_concurrents'] ?></p>
            </div>
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Max Duration</p>
                <p class="text-white font-semibold"><?= htmlspecialchars($dur_display) ?></p>
            </div>
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Expires On</p>
                <p class="text-white font-semibold"><?= $exp_date ?></p>
            </div>
        </div>

        <!-- API Key Section -->
        <div class="mt-6 bg-panel border border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-400 font-medium">API Key</span>
                <div class="flex gap-2">
                    <button id="btn-copy-apikey" onclick="copyApiKey()" class="text-sm text-gray-400 hover:text-blue-400 transition" title="Copy API key">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button onclick="regenerateApiKey()" class="text-sm text-gray-400 hover:text-green-400 transition flex items-center gap-1" title="Generate a new API key">
                        <i class="fas fa-sync-alt"></i> Generate
                    </button>
                </div>
            </div>
            <?php if (!empty($user['api_key'])): ?>
                <code id="apikey-display" class="block text-sm text-white break-all"><?= htmlspecialchars(substr($user['api_key'], 0, 10)) ?>••••••••••••••••••••••••</code>
            <?php else: ?>
                <p id="apikey-display" class="text-sm text-gray-500 italic">No API key yet — click Generate to create one.</p>
            <?php endif; ?>
            <p class="text-xs text-gray-600 mt-2">Use the API key in the <code class="text-blue-400">X-Api-Key</code> header to access the API. Generating a new key invalidates the previous one.</p>
        </div>
    </div>
</div>

<script>
const csrfToken = <?= json_encode(generate_csrf_token()) ?>;

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    showToast('Copied to clipboard!', 'success');
}

function copyApiKey() {
    const display = document.getElementById('apikey-display');
    const key = display.dataset.fullKey || '';
    if (!key) { showToast('No API key to copy', 'error'); return; }
    navigator.clipboard.writeText(key);
    showToast('API key copied!', 'success');
}

async function regenerateApiKey() {
    if (!confirm('Generate a new API key? The current key will be invalidated.')) return;
    try {
        const fd = new FormData();
        fd.append('action', 'regenerate_api_key');
        fd.append('csrf_token', csrfToken);
        const res = await fetch('api/profile.php', {method: 'POST', body: fd});
        const data = await res.json();
        if (!res.ok) { showToast(data.detail || 'Error', 'error'); return; }
        const display = document.getElementById('apikey-display');
        if (display && display.tagName === 'P') {
            const code = document.createElement('code');
            code.id = 'apikey-display';
            code.className = 'block text-sm text-white break-all';
            display.replaceWith(code);
        }
        const keyEl = document.getElementById('apikey-display');
        keyEl.dataset.fullKey = data.api_key;
        keyEl.textContent = data.api_key.slice(0, 10) + '••••••••••••••••••••••••';
        showToast('New API key generated!', 'success');
    } catch (e) {
        showToast('Connection error', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
