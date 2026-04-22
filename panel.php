<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
$csrf_token = generate_csrf_token();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen px-6 py-6">
    <div class="max-w-7xl mx-auto">

        <!-- Page Header -->
        <div class="mb-5">
            <h1 class="text-3xl font-bold text-white"><i class="fas fa-bolt mr-3 text-blue-400"></i>Hub</h1>
            <p class="text-gray-400 mt-1 text-sm">Your full-power attack hub.</p>
        </div>

        <!-- Plan info banner -->
        <div class="mb-5 flex items-center gap-3 text-sm bg-blue-500/10 border border-blue-500/20 rounded-xl px-5 py-3">
            <i class="fas fa-info-circle text-blue-400"></i>
            <p class="text-blue-300">
                You are on the <strong class="text-white"><?= htmlspecialchars($user['plan']) ?></strong> plan &mdash;
                max <strong class="text-white"><?= $user['max_seconds'] ?>s</strong> duration,
                <strong class="text-white"><?= $user['max_concurrents'] ?></strong> concurrent<?= $user['max_concurrents'] > 1 ? 's' : '' ?>.
                <?php if ($user['plan'] === 'Starter'): ?>
                <a href="store.php" class="text-blue-400 hover:text-blue-300 underline ml-1">Upgrade for more →</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">

            <!-- Send Form -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-4">Send Attack</h2>

                <!-- Layer Tabs -->
                <div class="flex gap-2 mb-6">
                    <button onclick="switchLayer('l4')" id="tab-l4" class="px-4 py-2 rounded-lg font-medium bg-blue-600 text-white transition">Layer 4</button>
                    <button onclick="switchLayer('l7')" id="tab-l7" class="px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition">Layer 7</button>
                </div>

                <!-- Layer 4 Form -->
                <form id="l4-form" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="layer" value="Layer4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Target IPv4</label>
                        <input type="text" name="target" placeholder="e.g. 192.168.1.1" required
                            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Port</label>
                            <input type="number" name="port" value="80" min="1" max="65535"
                                class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Time (seconds)</label>
                            <input type="number" name="time" value="30" min="10" max="<?= $user['max_seconds'] ?>"
                                class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Method</label>
                        <select name="method" id="l4-methods"
                            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Concurrents: <span id="l4-conc-val">1</span></label>
                        <input type="range" name="concurrents" min="1" max="<?= $user['max_concurrents'] ?>" value="1"
                            class="w-full" oninput="document.getElementById('l4-conc-val').textContent=this.value">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition">
                        <i class="fas fa-bolt mr-2"></i>Send Attack
                    </button>
                </form>

                <!-- Layer 7 Form -->
                <form id="l7-form" class="space-y-4 hidden">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="layer" value="Layer7">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Target URL</label>
                        <input type="url" name="target" placeholder="https://example.com" required
                            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Requests</label>
                            <input type="number" name="port" value="64" min="1"
                                class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Time (seconds)</label>
                            <input type="number" name="time" value="30" min="10" max="<?= $user['max_seconds'] ?>"
                                class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Method</label>
                        <select name="method" id="l7-methods"
                            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Concurrents: <span id="l7-conc-val">1</span></label>
                        <input type="range" name="concurrents" min="1" max="<?= $user['max_concurrents'] ?>" value="1"
                            class="w-full" oninput="document.getElementById('l7-conc-val').textContent=this.value">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition">
                        <i class="fas fa-bolt mr-2"></i>Send Attack
                    </button>
                </form>
            </div>

            <!-- Running -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-4">Running</h2>
                <div id="attack-logs" class="space-y-3">
                    <p class="text-gray-400 text-center py-8">Loading...</p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function switchLayer(layer) {
    document.getElementById('l4-form').classList.toggle('hidden', layer !== 'l4');
    document.getElementById('l7-form').classList.toggle('hidden', layer !== 'l7');
    document.getElementById('tab-l4').className = layer === 'l4' 
        ? 'px-4 py-2 rounded-lg font-medium bg-blue-600 text-white transition'
        : 'px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition';
    document.getElementById('tab-l7').className = layer === 'l7'
        ? 'px-4 py-2 rounded-lg font-medium bg-blue-600 text-white transition'
        : 'px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition';
}

async function loadMethods() {
    try {
        const res = await fetch('api/methods.php');
        const methods = await res.json();
        
        const l4Select = document.getElementById('l4-methods');
        const l7Select = document.getElementById('l7-methods');
        
        methods.forEach(m => {
            if (m.layer4) {
                const opt = new Option(m.name + (m.premium ? ' \u2B50' : ''), m.name);
                l4Select.add(opt);
            }
            if (m.layer7) {
                const opt = new Option(m.name + (m.premium ? ' \u2B50' : ''), m.name);
                l7Select.add(opt);
            }
        });
    } catch (err) {
        console.error('Failed to load methods:', err);
    }
}

async function loadAttacks() {
    try {
        const res = await fetch('api/attack.php');
        const attacks = await res.json();
        const container = document.getElementById('attack-logs');
        
        if (!attacks.length) {
            container.innerHTML = '<p class="text-gray-400 text-center py-8">No active attacks</p>';
            return;
        }
        
        container.innerHTML = attacks.map(a => `
            <div class="bg-background border border-gray-700/50 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="text-white font-medium">${escapeHtml(a.target)}</p>
                    <p class="text-gray-400 text-sm">${escapeHtml(a.method)} \u2022 ${escapeHtml(a.layer)} \u2022 ${a.remaining}s remaining</p>
                </div>
                <button onclick="stopAttack('${a.id}')" class="text-red-400 hover:text-red-300 transition">
                    <i class="fas fa-stop-circle text-xl"></i>
                </button>
            </div>
        `).join('');
    } catch (err) {
        console.error('Failed to load attacks:', err);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function stopAttack(id) {
    const formData = new FormData();
    formData.append('action', 'stop');
    formData.append('attack_id', id);
    formData.append('csrf_token', '<?= htmlspecialchars($csrf_token) ?>');
    
    try {
        const res = await fetch('api/attack.php', { method: 'POST', body: formData });
        if (res.ok) {
            showToast('Attack stopped', 'success');
            loadAttacks();
        }
    } catch (err) {
        showToast('Failed to stop attack', 'error');
    }
}

// Form submissions
['l4-form', 'l7-form'].forEach(formId => {
    document.getElementById(formId).addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const res = await fetch('api/attack.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (res.ok) {
                showToast('Attack launched!', 'success');
                loadAttacks();
            } else {
                showToast(data.detail || 'Failed to launch attack', 'error');
            }
        } catch (err) {
            showToast('Connection error', 'error');
        }
    });
});

loadMethods();
loadAttacks();
setInterval(loadAttacks, 5000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
