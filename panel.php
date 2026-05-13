<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';
$user = require_auth();
$csrf_token = generate_csrf_token();
$is_starter = $user['plan'] === 'Starter';
$is_premium = !$is_starter;
$max_s = $user['max_seconds'];
$max_c = $user['max_concurrents'];
$max_dur_label = $max_s >= 3600 ? floor($max_s/3600).'h' : $max_s.'s';
$accent = $is_starter ? 'green' : 'blue';

// Resolve per-plan feature flags
$allow_schedule = false;
foreach (read_json('plans.json') as $plan) {
    if ($plan['name'] === $user['plan']) {
        $allow_schedule = !empty($plan['allow_schedule']);
        break;
    }
}

$page_title = 'Hub';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen px-4 py-6 lg:px-6">
    <div class="max-w-7xl mx-auto space-y-5">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <?php if ($is_starter): ?>
                        <i class="fas fa-globe text-green-400"></i> Hub
                    <?php else: ?>
                        <i class="fas fa-bolt text-blue-400"></i> Hub
                    <?php endif; ?>
                </h1>
                <p class="text-gray-500 text-sm mt-0.5">
                    <?= $is_starter ? 'Free plan &mdash; basic methods, limited duration. Upgrade to unlock more.' : 'Your full-power attack hub.' ?>
                </p>
            </div>
            <a href="history.php" class="text-xs text-gray-500 hover:text-blue-400 transition flex items-center gap-1.5 border border-gray-700 rounded-lg px-3 py-1.5">
                <i class="fas fa-history"></i> History
            </a>
        </div>

        <?php if ($is_starter): ?>
        <!-- Upgrade banner -->
        <div class="flex items-center gap-3 bg-orange-500/8 border border-orange-500/20 rounded-xl px-5 py-3">
            <i class="fas fa-rocket text-orange-400 shrink-0"></i>
            <p class="text-orange-200 text-sm flex-1">
                You're on the <strong class="text-white">Starter (Free)</strong> plan.
                Upgrade to unlock premium methods, more concurrents, and longer durations.
            </p>
            <a href="store.php" class="shrink-0 text-xs bg-orange-600 hover:bg-orange-700 text-white font-semibold px-4 py-1.5 rounded-lg transition">Upgrade</a>
        </div>
        <?php endif; ?>

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-panel border border-gray-700/50 rounded-xl px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-<?= $accent ?>-600/15 flex items-center justify-center shrink-0">
                    <i class="fas fa-id-badge text-<?= $accent ?>-400 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Plan</p>
                    <p class="text-white font-semibold text-sm"><?= htmlspecialchars($user['plan']) ?></p>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 rounded-xl px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-green-600/15 flex items-center justify-center shrink-0">
                    <i class="fas fa-server text-green-400 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Slots Used</p>
                    <p class="text-white font-semibold text-sm"><span id="stat-running">0</span> / <?= $max_c ?></p>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 rounded-xl px-4 py-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-yellow-600/15 flex items-center justify-center shrink-0">
                    <i class="fas fa-clock text-yellow-400 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Max Duration</p>
                    <p class="text-white font-semibold text-sm"><?= $max_dur_label ?></p>
                </div>
            </div>
            <div class="bg-panel border border-gray-700/50 rounded-xl px-4 py-3 flex items-center gap-3">
                <?php if ($is_starter): ?>
                <div class="w-9 h-9 rounded-lg bg-orange-600/15 flex items-center justify-center shrink-0">
                    <i class="fas fa-lock text-orange-400 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500">Methods</p>
                    <a href="store.php" class="text-orange-400 font-semibold text-xs hover:text-orange-300 transition">Upgrade for Premium →</a>
                </div>
                <?php else: ?>
                <div class="w-9 h-9 rounded-lg bg-purple-600/15 flex items-center justify-center shrink-0">
                    <i class="fas fa-star text-purple-400 text-sm"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Methods</p>
                    <p class="text-white font-semibold text-sm">Premium</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-5">

            <!-- Send Form (order-2 on mobile = shown second; order-1 on desktop = shown left) -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6 order-2 lg:order-1">
                <h2 class="text-base font-bold text-white mb-5 flex items-center gap-2">
                    <i class="fas fa-crosshairs text-<?= $accent ?>-400"></i> Send Attack
                </h2>

                <!-- Layer Tabs -->
                <div class="flex gap-1 mb-5 bg-background rounded-lg p-1">
                    <button onclick="switchLayer('l4')" id="tab-l4"
                        class="flex-1 px-3 py-2 rounded-md font-medium text-sm bg-blue-600 text-white transition">
                        <i class="fas fa-network-wired mr-1"></i> Layer 4
                    </button>
                    <button onclick="switchLayer('l7')" id="tab-l7"
                        class="flex-1 px-3 py-2 rounded-md font-medium text-sm text-gray-400 hover:text-white transition">
                        <i class="fas fa-globe mr-1"></i> Layer 7
                    </button>
                </div>

                <!-- Layer 4 Form -->
                <form id="l4-form" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="layer" value="Layer4">
                    <div>
                        <label class="form-label">Target IPv4</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-crosshairs input-icon"></i>
                            <input type="text" name="target" placeholder="e.g. 192.168.1.1" required class="form-input pl-9">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Port</label>
                            <input type="number" name="port" value="80" min="1" max="65535" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Duration (s)</label>
                            <div class="input-icon-wrap">
                                <input type="number" name="time" value="30" min="10" max="<?= $max_s ?>" class="form-input pr-8">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 text-xs">s</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Method</label>
                        <select name="method" id="l4-methods" onchange="updateMethodDesc('l4')" class="form-input"></select>
                        <p id="l4-method-desc" class="text-gray-600 text-xs mt-1.5 pl-1 italic"></p>
                    </div>
                    <div>
                        <label class="form-label flex items-center justify-between">
                            <span>Concurrents</span>
                            <span class="text-blue-400 font-bold not-italic" id="l4-conc-val">1</span>
                        </label>
                        <input type="range" name="concurrents" min="1" max="<?= $max_c ?>" value="1"
                            class="w-full mt-1" oninput="document.getElementById('l4-conc-val').textContent=this.value">
                        <div class="flex justify-between text-xs text-gray-700 mt-1">
                            <span>1</span><span><?= $max_c ?></span>
                        </div>
                    </div>
                    <button type="submit" class="launch-btn <?= $is_starter ? 'launch-btn-green' : '' ?>">
                        <i class="fas fa-bolt mr-2"></i>Launch Attack
                    </button>
                    <div class="mt-3">
                        <?php if ($allow_schedule): ?>
                        <button type="button" onclick="toggleScheduleL4()" class="text-xs text-gray-600 hover:text-blue-400 flex items-center gap-1 transition">
                            <i class="fas fa-calendar-alt"></i> Schedule for later
                        </button>
                        <div id="schedule-l4" class="hidden mt-2">
                            <label class="form-label">Scheduled Launch Time</label>
                            <input type="datetime-local" name="scheduled_at" class="form-input"
                                min="<?= date('Y-m-d\TH:i', time() + 30) ?>">
                        </div>
                        <?php else: ?>
                        <a href="store.php" class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-orange-400 transition">
                            <i class="fas fa-lock"></i> Schedule for later <span class="text-orange-500/70">(Advanced+)</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Layer 7 Form -->
                <form id="l7-form" class="space-y-4 hidden">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="layer" value="Layer7">
                    <div>
                        <label class="form-label">Target URL</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-globe input-icon"></i>
                            <input type="url" name="target" placeholder="https://example.com" required class="form-input pl-9">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Requests/s</label>
                            <input type="number" name="port" value="64" min="1" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">Duration (s)</label>
                            <div class="input-icon-wrap">
                                <input type="number" name="time" value="30" min="10" max="<?= $max_s ?>" class="form-input pr-8">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-600 text-xs">s</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Method</label>
                        <select name="method" id="l7-methods" onchange="updateMethodDesc('l7')" class="form-input"></select>
                        <p id="l7-method-desc" class="text-gray-600 text-xs mt-1.5 pl-1 italic"></p>
                    </div>
                    <div>
                        <label class="form-label flex items-center justify-between">
                            <span>Concurrents</span>
                            <span class="text-blue-400 font-bold not-italic" id="l7-conc-val">1</span>
                        </label>
                        <input type="range" name="concurrents" min="1" max="<?= $max_c ?>" value="1"
                            class="w-full mt-1" oninput="document.getElementById('l7-conc-val').textContent=this.value">
                        <div class="flex justify-between text-xs text-gray-700 mt-1">
                            <span>1</span><span><?= $max_c ?></span>
                        </div>
                    </div>
                    <button type="submit" class="launch-btn <?= $is_starter ? 'launch-btn-green' : '' ?>">
                        <i class="fas fa-bolt mr-2"></i>Launch Attack
                    </button>
                    <div class="mt-3">
                        <?php if ($allow_schedule): ?>
                        <button type="button" onclick="toggleScheduleL7()" class="text-xs text-gray-600 hover:text-blue-400 flex items-center gap-1 transition">
                            <i class="fas fa-calendar-alt"></i> Schedule for later
                        </button>
                        <div id="schedule-l7" class="hidden mt-2">
                            <label class="form-label">Scheduled Launch Time</label>
                            <input type="datetime-local" name="scheduled_at" class="form-input"
                                min="<?= date('Y-m-d\TH:i', time() + 30) ?>">
                        </div>
                        <?php else: ?>
                        <a href="store.php" class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-orange-400 transition">
                            <i class="fas fa-lock"></i> Schedule for later <span class="text-orange-500/70">(Advanced+)</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($is_starter): ?>
                <!-- Locked premium methods hint -->
                <div class="mt-5 pt-4 border-t border-gray-700/50">
                    <p class="text-xs text-gray-600 mb-2 font-medium uppercase tracking-wide">Locked Premium Methods</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 text-xs bg-gray-800 border border-gray-700 text-gray-500 rounded-md px-2 py-1"><i class="fas fa-lock text-xs"></i> SYN-FLOOD</span>
                        <span class="inline-flex items-center gap-1 text-xs bg-gray-800 border border-gray-700 text-gray-500 rounded-md px-2 py-1"><i class="fas fa-lock text-xs"></i> HTTP-OVH</span>
                        <span class="inline-flex items-center gap-1 text-xs bg-gray-800 border border-gray-700 text-gray-500 rounded-md px-2 py-1"><i class="fas fa-lock text-xs"></i> DNS-AMP</span>
                        <span class="inline-flex items-center gap-1 text-xs bg-gray-800 border border-gray-700 text-gray-500 rounded-md px-2 py-1"><i class="fas fa-lock text-xs"></i> NTP-AMP</span>
                        <a href="store.php" class="inline-flex items-center gap-1 text-xs bg-orange-600/15 border border-orange-500/30 text-orange-400 hover:text-orange-300 rounded-md px-2 py-1 transition font-medium"><i class="fas fa-arrow-right text-xs"></i> Unlock All</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Running Attacks (order-1 on mobile = shown first; order-2 on desktop = shown right) -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6 flex flex-col order-1 lg:order-2">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-base font-bold text-white flex items-center gap-2.5">
                        <span id="attack-pulse" class="status-dot status-idle"></span>
                        Running
                    </h2>
                    <span class="text-xs text-gray-600 flex items-center gap-1">
                        <i class="fas fa-sync-alt text-xs"></i> Live
                    </span>
                </div>
                <div id="attack-logs" class="space-y-3 flex-1">
                    <div class="flex flex-col items-center justify-center py-14 text-center">
                        <i class="fas fa-satellite-dish text-3xl text-gray-700 mb-3"></i>
                        <p class="text-gray-600 text-sm">Loading...</p>
                    </div>
                </div>

                <?php if ($is_starter): ?>
                <!-- Upgrade CTA at bottom -->
                <div class="mt-5 pt-4 border-t border-gray-700/50">
                    <a href="store.php" class="flex items-center justify-center gap-2 w-full bg-orange-600/15 hover:bg-orange-600/25 border border-orange-500/30 text-orange-300 font-medium text-sm py-2.5 rounded-xl transition">
                        <i class="fas fa-rocket"></i> Unlock premium methods &amp; more slots
                    </a>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Quick Launch Favorites -->
        <div id="favorites-section" class="mt-5 bg-panel border border-gray-700/50 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
                    <i class="fas fa-star text-yellow-400"></i> Favorites
                </h3>
                <button onclick="saveFavoriteFromForm()" class="text-xs text-gray-500 hover:text-yellow-400 flex items-center gap-1 transition">
                    <i class="fas fa-plus"></i> Save current
                </button>
            </div>
            <div id="favorites-list" class="flex flex-wrap gap-2">
                <p class="text-gray-600 text-xs">No favorites yet. Fill in the form and click "Save current" to add one.</p>
            </div>
        </div>

        <!-- Scheduled Attacks -->
        <div id="scheduled-section" class="hidden mt-5 bg-panel border border-yellow-700/30 rounded-2xl p-5">
            <h3 class="text-sm font-semibold text-yellow-300 flex items-center gap-2 mb-3">
                <i class="fas fa-calendar-alt"></i> Scheduled Attacks
            </h3>
            <div id="scheduled-list" class="space-y-2"></div>
        </div>

    </div>
</div>

<script>
const csrfToken = <?= json_encode($csrf_token) ?>;
const maxConcurrents = <?= $max_c ?>;
const isPremium = <?= $is_premium ? 'true' : 'false' ?>;
const allowSchedule = <?= $allow_schedule ? 'true' : 'false' ?>;
let methodMeta = {};
let attackTimers = {};

function switchLayer(layer) {
    document.getElementById('l4-form').classList.toggle('hidden', layer !== 'l4');
    document.getElementById('l7-form').classList.toggle('hidden', layer !== 'l7');
    const active = 'flex-1 px-3 py-2 rounded-md font-medium text-sm bg-blue-600 text-white transition';
    const idle   = 'flex-1 px-3 py-2 rounded-md font-medium text-sm text-gray-400 hover:text-white transition';
    document.getElementById('tab-l4').className = layer === 'l4' ? active : idle;
    document.getElementById('tab-l7').className = layer === 'l7' ? active : idle;
}

function updateMethodDesc(prefix) {
    const sel = document.getElementById(prefix + '-methods');
    const desc = document.getElementById(prefix + '-method-desc');
    desc.textContent = methodMeta[sel.value] || '';
}

async function loadMethods() {
    try {
        const res = await fetch('api/methods.php');
        const methods = await res.json();
        const l4 = document.getElementById('l4-methods');
        const l7 = document.getElementById('l7-methods');

        methods.forEach(m => {
            // Free users only see non-premium methods
            if (!isPremium && m.premium) return;

            const descParts = [m.description];
            if (m.premium) descParts.push('— Premium ⭐');
            if (m.amplification) descParts.push('[Amplification]');
            methodMeta[m.name] = descParts.join(' ');

            if (m.layer4) {
                const label = m.name + (m.premium ? ' ⭐' : '');
                l4.add(new Option(label, m.name));
            }
            if (m.layer7) {
                const label = m.name + (m.premium ? ' ⭐' : '');
                l7.add(new Option(label, m.name));
            }
        });
        updateMethodDesc('l4');
        updateMethodDesc('l7');
    } catch (err) {
        console.error('Failed to load methods:', err);
    }
}

function renderAttack(a) {
    const pct = Math.min(100, Math.max(0, (a.remaining / a.time) * 100));
    const layerBadge = a.layer === 'Layer7'
        ? '<span class="badge badge-l7">L7</span>'
        : '<span class="badge badge-l4">L4</span>';
    // Use loaded method metadata to determine premium status dynamically
    const premBadge = a.method && methodMeta[a.method] && methodMeta[a.method].includes('Premium')
        ? '<span class="badge badge-premium">⭐</span>' : '';

    return `
    <div class="attack-card" id="card-${escapeHtml(a.id)}">
        <div class="flex items-start justify-between gap-2 mb-2">
            <div class="flex-1 min-w-0">
                <p class="text-white font-mono text-sm font-medium truncate">${escapeHtml(a.target)}</p>
                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                    ${layerBadge}
                    <span class="badge badge-method">${escapeHtml(a.method)}</span>
                    ${premBadge}
                    <span class="text-gray-500 text-xs">· ${escapeHtml(String(a.port))} · ${escapeHtml(String(a.concurrents))}×</span>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span id="timer-${escapeHtml(a.id)}" class="text-green-400 font-mono font-bold text-sm tabular-nums">${a.remaining}s</span>
                <button onclick="stopAttack('${escapeHtml(a.id)}')" class="stop-btn" title="Stop attack">
                    <i class="fas fa-stop"></i>
                </button>
            </div>
        </div>
        <div class="attack-progress-track">
            <div id="bar-${escapeHtml(a.id)}" class="attack-progress-bar" style="width:${pct}%"></div>
        </div>
    </div>`;
}

function clearAttackTimers() {
    Object.values(attackTimers).forEach(t => clearInterval(t));
    attackTimers = {};
}

function startAttackTimer(id, remaining, total) {
    let r = remaining;
    attackTimers[id] = setInterval(() => {
        r--;
        if (r <= 0) {
            clearInterval(attackTimers[id]);
            delete attackTimers[id];
            loadAttacks();
            return;
        }
        const timerEl = document.getElementById('timer-' + id);
        const barEl   = document.getElementById('bar-'   + id);
        if (timerEl) timerEl.textContent = r + 's';
        if (barEl)   barEl.style.width = Math.max(0, (r / total) * 100) + '%';
    }, 1000);
}

async function loadAttacks() {
    try {
        const res = await fetch('api/attack.php');
        const attacks = await res.json();
        const container = document.getElementById('attack-logs');
        const pulse = document.getElementById('attack-pulse');
        const statEl = document.getElementById('stat-running');

        if (statEl) statEl.textContent = attacks.length;

        if (!attacks.length) {
            clearAttackTimers();
            if (pulse) { pulse.className = 'status-dot status-idle'; }
            container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-14 text-center">
                    <i class="fas fa-satellite-dish text-3xl text-gray-700 mb-3"></i>
                    <p class="text-gray-600 text-sm">No active attacks</p>
                    <p class="text-gray-700 text-xs mt-1">Launch an attack to see it here.</p>
                </div>`;
            return;
        }

        if (pulse) { pulse.className = 'status-dot status-live'; }

        const currentIds = new Set(Object.keys(attackTimers));
        const newIds = new Set(attacks.map(a => a.id));
        const idsChanged = [...newIds].some(id => !currentIds.has(id)) || [...currentIds].some(id => !newIds.has(id));

        if (idsChanged) {
            clearAttackTimers();
            container.innerHTML = attacks.map(renderAttack).join('');
            attacks.forEach(a => startAttackTimer(a.id, a.remaining, a.time));
        }
    } catch (err) {
        console.error('Failed to load attacks:', err);
    }
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = String(text ?? '');
    return d.innerHTML;
}

// Manual poll fallback (also called after stop for immediate refresh)
async function loadAttacks() {
    try {
        const res = await fetch('api/attack.php');
        const attacks = await res.json();
        updateAttackDisplay(attacks);
    } catch (err) {
        console.error('Failed to load attacks:', err);
    }
}

async function stopAttack(id) {
    const btn = document.querySelector(`#card-${id} .stop-btn`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
    const fd = new FormData();
    fd.append('action', 'stop');
    fd.append('attack_id', id);
    fd.append('csrf_token', csrfToken);
    try {
        const res = await fetch('api/attack.php', { method: 'POST', body: fd });
        if (res.ok) {
            showToast('Attack stopped', 'success');
            clearInterval(attackTimers[id]);
            delete attackTimers[id];
            // SSE will pick up the change within 1s; also trigger immediate poll
            loadAttacks();
        } else {
            showToast('Failed to stop attack', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-stop"></i>'; }
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

['l4-form', 'l7-form'].forEach(formId => {
    document.getElementById(formId).addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Launching...';
        try {
            const res = await fetch('api/attack.php', { method: 'POST', body: new FormData(this) });
            const data = await res.json();
            if (res.ok) {
                const msg = data.attack?.status === 'scheduled' ? 'Attack scheduled!' : 'Attack launched!';
                showToast(msg, 'success');
                if (data.attack?.status !== 'scheduled') loadAttacks();
                else loadScheduled();
            } else {
                showToast(data.detail || 'Failed to launch attack', 'error');
            }
        } catch (err) {
            showToast('Connection error', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt mr-2"></i>Launch Attack';
    });
});

// =================== SCHEDULE TOGGLES ===================
function toggleScheduleL4() {
    document.getElementById('schedule-l4').classList.toggle('hidden');
}
function toggleScheduleL7() {
    document.getElementById('schedule-l7').classList.toggle('hidden');
}

// =================== SCHEDULED ATTACKS ===================
async function loadScheduled() {
    try {
        const res = await fetch('api/schedule.php');
        const list = await res.json();
        const container = document.getElementById('scheduled-list');
        if (!container) return;
        if (!list.length) {
            container.innerHTML = '';
            document.getElementById('scheduled-section')?.classList.add('hidden');
            return;
        }
        document.getElementById('scheduled-section')?.classList.remove('hidden');
        container.innerHTML = list.map(s => `
            <div class="flex items-center justify-between bg-background border border-yellow-700/30 rounded-xl px-4 py-3">
                <div>
                    <p class="text-white text-sm font-mono">${escapeHtml(s.target)}</p>
                    <p class="text-gray-500 text-xs">${escapeHtml(s.method)} · ${escapeHtml(s.layer)} · ${escapeHtml(String(s.time))}s</p>
                    <p class="text-yellow-400 text-xs mt-0.5"><i class="fas fa-clock mr-1"></i>${new Date(s.scheduled_at).toLocaleString()}</p>
                </div>
                <button class="cancel-sched-btn text-gray-600 hover:text-red-400 transition text-sm" data-id="${escapeHtml(s.id)}" title="Cancel">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        container.querySelectorAll('.cancel-sched-btn').forEach(btn => {
            btn.addEventListener('click', () => cancelScheduled(btn.dataset.id));
        });
    } catch (err) { /* silent */ }
}

async function cancelScheduled(id) {
    const fd = new FormData();
    fd.append('csrf_token', csrfToken);
    const res = await fetch('api/schedule.php', {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({id})
    });
    if (res.ok) { showToast('Scheduled attack cancelled', 'success'); loadScheduled(); }
}

// =================== FAVORITES ===================
async function loadFavorites() {
    try {
        const res = await fetch('api/favorites.php');
        const favs = await res.json();
        const container = document.getElementById('favorites-list');
        if (!container) return;
        if (!favs.length) {
            container.innerHTML = '<p class="text-gray-600 text-xs">No favorites yet. Fill in the form and click "Save current" to add one.</p>';
            return;
        }
        container.innerHTML = favs.map(f => `
            <div class="flex items-center gap-1">
                <button class="fav-apply-btn flex items-center gap-1.5 bg-background border border-gray-700 hover:border-blue-500 text-gray-300 hover:text-white text-xs rounded-lg px-3 py-1.5 transition"
                    data-fav='${escapeHtml(JSON.stringify(f))}'>
                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                    ${escapeHtml(f.name)}
                </button>
                <button class="fav-delete-btn text-gray-700 hover:text-red-400 transition text-xs" data-id="${escapeHtml(f.id)}" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        container.querySelectorAll('.fav-apply-btn').forEach(btn => {
            btn.addEventListener('click', () => applyFavorite(JSON.parse(btn.dataset.fav)));
        });
        container.querySelectorAll('.fav-delete-btn').forEach(btn => {
            btn.addEventListener('click', () => deleteFavorite(btn.dataset.id));
        });
    } catch (err) { /* silent */ }
}

function applyFavorite(f) {
    if (f.layer === 'Layer7') {
        switchLayer('l7');
        const form = document.getElementById('l7-form');
        if (form.querySelector('[name=target]')) form.querySelector('[name=target]').value = f.target || '';
        if (form.querySelector('[name=port]'))   form.querySelector('[name=port]').value   = f.port || 64;
        if (form.querySelector('[name=time]'))   form.querySelector('[name=time]').value   = f.time || 30;
        const methSel = document.getElementById('l7-methods');
        if (methSel) for (let o of methSel.options) if (o.value === f.method) { methSel.value = f.method; break; }
        const rangeEl = form.querySelector('[name=concurrents]');
        if (rangeEl) { rangeEl.value = f.concurrents || 1; document.getElementById('l7-conc-val').textContent = rangeEl.value; }
    } else {
        switchLayer('l4');
        const form = document.getElementById('l4-form');
        if (form.querySelector('[name=target]')) form.querySelector('[name=target]').value = f.target || '';
        if (form.querySelector('[name=port]'))   form.querySelector('[name=port]').value   = f.port || 80;
        if (form.querySelector('[name=time]'))   form.querySelector('[name=time]').value   = f.time || 30;
        const methSel = document.getElementById('l4-methods');
        if (methSel) for (let o of methSel.options) if (o.value === f.method) { methSel.value = f.method; break; }
        const rangeEl = form.querySelector('[name=concurrents]');
        if (rangeEl) { rangeEl.value = f.concurrents || 1; document.getElementById('l4-conc-val').textContent = rangeEl.value; }
    }
}

async function saveFavoriteFromForm() {
    const activeForm = document.getElementById('l7-form').classList.contains('hidden')
        ? document.getElementById('l4-form') : document.getElementById('l7-form');
    const layer    = activeForm.querySelector('[name=layer]')?.value || 'Layer4';
    const target   = activeForm.querySelector('[name=target]')?.value?.trim();
    const port     = activeForm.querySelector('[name=port]')?.value;
    const time     = activeForm.querySelector('[name=time]')?.value;
    const method   = layer === 'Layer4'
        ? document.getElementById('l4-methods')?.value
        : document.getElementById('l7-methods')?.value;
    const concurrents = activeForm.querySelector('[name=concurrents]')?.value || 1;

    if (!target) { showToast('Enter a target first', 'error'); return; }

    const name = prompt('Name for this favorite:', target);
    if (!name) return;

    const fd = new FormData();
    fd.append('csrf_token', csrfToken);
    fd.append('name', name);
    fd.append('target', target);
    fd.append('port', port || 80);
    fd.append('method', method || '');
    fd.append('layer', layer);
    fd.append('time', time || 30);
    fd.append('concurrents', concurrents);

    const res = await fetch('api/favorites.php', { method: 'POST', body: fd });
    if (res.ok) { showToast('Favorite saved!', 'success'); loadFavorites(); }
    else { const d = await res.json(); showToast(d.detail || 'Failed to save', 'error'); }
}

async function deleteFavorite(id) {
    const res = await fetch('api/favorites.php', {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({id})
    });
    if (res.ok) { showToast('Favorite removed', 'success'); loadFavorites(); }
}

// =================== SSE (Server-Sent Events) ===================
let evtSource = null;

function updateAttackDisplay(attacks) {
    const container = document.getElementById('attack-logs');
    const pulse = document.getElementById('attack-pulse');
    const statEl = document.getElementById('stat-running');

    if (statEl) statEl.textContent = attacks.length;

    if (!attacks.length) {
        clearAttackTimers();
        if (pulse) pulse.className = 'status-dot status-idle';
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <i class="fas fa-satellite-dish text-3xl text-gray-700 mb-3"></i>
                <p class="text-gray-600 text-sm">No active attacks</p>
                <p class="text-gray-700 text-xs mt-1">Launch an attack to see it here.</p>
            </div>`;
        return;
    }

    if (pulse) pulse.className = 'status-dot status-live';

    const currentIds = new Set(Object.keys(attackTimers));
    const newIds     = new Set(attacks.map(a => a.id));
    const idsChanged = [...newIds].some(id => !currentIds.has(id)) || [...currentIds].some(id => !newIds.has(id));

    if (idsChanged) {
        clearAttackTimers();
        container.innerHTML = attacks.map(renderAttack).join('');
        attacks.forEach(a => startAttackTimer(a.id, a.remaining, a.time));
    }
}

function connectSSE() {
    if (evtSource) { evtSource.close(); evtSource = null; }
    evtSource = new EventSource('api/attacks-sse.php');

    evtSource.onmessage = function(e) {
        try { updateAttackDisplay(JSON.parse(e.data)); } catch(_) {}
    };
    evtSource.onerror = function() {
        evtSource.close(); evtSource = null;
        setTimeout(connectSSE, 3000);
    };
}

loadMethods();
connectSSE();
loadFavorites();
if (allowSchedule) loadScheduled();

</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
