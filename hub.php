<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto">

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2"><i class="fas fa-globe mr-3 text-blue-400"></i>Free Hub</h1>
            <p class="text-gray-400">Free tools, resources & utilities available to all members.</p>
        </div>

        <!-- Tools Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

            <!-- IP Lookup -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white mb-4"><i class="fas fa-search-location mr-2 text-blue-400"></i>IP Lookup</h2>
                <div class="flex gap-2 mb-4">
                    <input type="text" id="ip-lookup-input" placeholder="Enter IP address (e.g. 8.8.8.8)"
                        class="flex-1 bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 text-sm">
                    <button onclick="lookupIP()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium">
                        <i class="fas fa-search"></i> Lookup
                    </button>
                </div>
                <div id="ip-lookup-result" class="hidden bg-background border border-gray-700/50 rounded-xl p-4 text-sm space-y-2"></div>
                <div class="mt-3">
                    <button onclick="lookupMyIP()" class="text-blue-400 hover:text-blue-300 text-xs transition">
                        <i class="fas fa-crosshairs mr-1"></i>Lookup my own IP
                    </button>
                </div>
            </div>

            <!-- Port Scanner -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white mb-4"><i class="fas fa-door-open mr-2 text-green-400"></i>Port Scanner</h2>
                <p class="text-gray-400 text-xs mb-3">Check if common ports are open on a target host.</p>
                <div class="flex gap-2 mb-4">
                    <input type="text" id="port-host-input" placeholder="IP or hostname"
                        class="flex-1 bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 text-sm">
                    <button onclick="scanPorts()" id="port-scan-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium">
                        <i class="fas fa-wifi"></i> Scan
                    </button>
                </div>
                <div id="port-scan-result" class="hidden">
                    <div class="grid grid-cols-3 gap-2 text-xs" id="port-scan-grid"></div>
                </div>
            </div>

            <!-- DNS Resolver -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white mb-4"><i class="fas fa-server mr-2 text-purple-400"></i>DNS Resolver</h2>
                <div class="flex gap-2 mb-4">
                    <input type="text" id="dns-input" placeholder="Enter domain (e.g. google.com)"
                        class="flex-1 bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 text-sm">
                    <button onclick="resolveDNS()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition text-sm font-medium">
                        <i class="fas fa-arrow-right"></i> Resolve
                    </button>
                </div>
                <div id="dns-result" class="hidden bg-background border border-gray-700/50 rounded-xl p-4 text-sm space-y-1"></div>
            </div>

            <!-- My IP Info -->
            <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
                <h2 class="text-lg font-bold text-white mb-4"><i class="fas fa-fingerprint mr-2 text-yellow-400"></i>My Connection Info</h2>
                <div id="my-ip-info" class="space-y-2">
                    <p class="text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>Loading your IP information...</p>
                </div>
            </div>

        </div>

        <!-- Free Resources -->
        <div class="bg-panel border border-gray-700/50 rounded-2xl p-6 mb-8">
            <h2 class="text-xl font-bold text-white mb-6"><i class="fas fa-tools mr-2 text-blue-400"></i>Free Tools & Resources</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php
                $resources = [
                    ['icon' => 'fa-shield-alt', 'color' => 'text-blue-400', 'title' => 'VPN Checker', 'desc' => 'Check if an IP is a known VPN or proxy.', 'link' => 'https://vpnapi.io', 'label' => 'Open Tool'],
                    ['icon' => 'fa-map-marked-alt', 'color' => 'text-green-400', 'title' => 'IP Geolocation', 'desc' => 'Geolocate any IP address on the map.', 'link' => 'https://www.iplocation.net', 'label' => 'Open Tool'],
                    ['icon' => 'fa-network-wired', 'color' => 'text-yellow-400', 'title' => 'Subnet Calculator', 'desc' => 'Calculate subnets and CIDR ranges.', 'link' => 'https://www.subnet-calculator.com', 'label' => 'Open Tool'],
                    ['icon' => 'fa-terminal', 'color' => 'text-red-400', 'title' => 'Online Ping', 'desc' => 'Ping any host from multiple locations.', 'link' => 'https://ping.pe', 'label' => 'Open Tool'],
                    ['icon' => 'fa-lock', 'color' => 'text-purple-400', 'title' => 'SSL Checker', 'desc' => 'Verify SSL certificates for any domain.', 'link' => 'https://www.ssllabs.com/ssltest/', 'label' => 'Open Tool'],
                    ['icon' => 'fa-search', 'color' => 'text-cyan-400', 'title' => 'WHOIS Lookup', 'desc' => 'Domain registration and ownership info.', 'link' => 'https://whois.domaintools.com', 'label' => 'Open Tool'],
                    ['icon' => 'fa-globe', 'color' => 'text-orange-400', 'title' => 'DNS Propagation', 'desc' => 'Check DNS propagation worldwide.', 'link' => 'https://dnschecker.org', 'label' => 'Open Tool'],
                    ['icon' => 'fa-chart-bar', 'color' => 'text-pink-400', 'title' => 'Shodan Search', 'desc' => 'Search for internet-connected devices.', 'link' => 'https://www.shodan.io', 'label' => 'Open Tool'],
                ];
                foreach ($resources as $r): ?>
                <a href="<?= htmlspecialchars($r['link']) ?>" target="_blank" rel="noopener noreferrer"
                    class="bg-background border border-gray-700/50 rounded-xl p-4 hover:border-blue-500/50 transition group block">
                    <div class="<?= $r['color'] ?> text-2xl mb-3"><i class="fas <?= $r['icon'] ?>"></i></div>
                    <h3 class="text-white font-semibold text-sm mb-1"><?= htmlspecialchars($r['title']) ?></h3>
                    <p class="text-gray-400 text-xs mb-3"><?= htmlspecialchars($r['desc']) ?></p>
                    <span class="text-blue-400 text-xs group-hover:text-blue-300 transition">
                        <?= htmlspecialchars($r['label']) ?> <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Plan Upgrade CTA -->
        <?php if ($user['plan'] === 'Starter'): ?>
        <div class="bg-gradient-to-r from-blue-900/40 to-purple-900/40 border border-blue-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-white font-bold text-lg mb-1"><i class="fas fa-rocket mr-2 text-blue-400"></i>Want more power?</h3>
                <p class="text-gray-300 text-sm">Upgrade to a paid plan for longer durations, more concurrents, premium methods and full API access.</p>
            </div>
            <a href="store.php" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl transition whitespace-nowrap">
                <i class="fas fa-shopping-cart mr-2"></i>View Plans
            </a>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
async function lookupIP(ip) {
    const val = ip || document.getElementById('ip-lookup-input').value.trim();
    if (!val) return showToast('Enter an IP address', 'error');
    const result = document.getElementById('ip-lookup-result');
    result.classList.remove('hidden');
    result.innerHTML = '<p class="text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Looking up...</p>';
    try {
        const res = await fetch(`https://ipapi.co/${encodeURIComponent(val)}/json/`);
        const d = await res.json();
        if (d.error) { result.innerHTML = `<p class="text-red-400">${escapeHtml(d.reason || 'Lookup failed')}</p>`; return; }
        result.innerHTML = `
            <div class="grid grid-cols-2 gap-2">
                <div><span class="text-gray-400">IP:</span> <span class="text-white font-medium">${escapeHtml(d.ip)}</span></div>
                <div><span class="text-gray-400">City:</span> <span class="text-white">${escapeHtml(d.city || 'N/A')}</span></div>
                <div><span class="text-gray-400">Country:</span> <span class="text-white">${escapeHtml(d.country_name || 'N/A')}</span></div>
                <div><span class="text-gray-400">Region:</span> <span class="text-white">${escapeHtml(d.region || 'N/A')}</span></div>
                <div><span class="text-gray-400">ISP:</span> <span class="text-white">${escapeHtml(d.org || 'N/A')}</span></div>
                <div><span class="text-gray-400">Timezone:</span> <span class="text-white">${escapeHtml(d.timezone || 'N/A')}</span></div>
                <div><span class="text-gray-400">Latitude:</span> <span class="text-white">${d.latitude ?? 'N/A'}</span></div>
                <div><span class="text-gray-400">Longitude:</span> <span class="text-white">${d.longitude ?? 'N/A'}</span></div>
            </div>
        `;
    } catch (err) {
        result.innerHTML = '<p class="text-red-400">Failed to fetch IP info.</p>';
    }
}

async function lookupMyIP() {
    try {
        const res = await fetch('https://ipapi.co/json/');
        const d = await res.json();
        if (d.ip) {
            document.getElementById('ip-lookup-input').value = d.ip;
            lookupIP(d.ip);
        }
    } catch (err) {
        showToast('Failed to get your IP', 'error');
    }
}

async function loadMyIPInfo() {
    const container = document.getElementById('my-ip-info');
    try {
        const res = await fetch('https://ipapi.co/json/');
        const d = await res.json();
        container.innerHTML = `
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="text-gray-400">Your IP:</span> <span class="text-white font-bold">${escapeHtml(d.ip)}</span></div>
                <div><span class="text-gray-400">Country:</span> <span class="text-white">${escapeHtml(d.country_name || 'N/A')}</span></div>
                <div><span class="text-gray-400">ISP:</span> <span class="text-white">${escapeHtml(d.org || 'N/A')}</span></div>
                <div><span class="text-gray-400">Timezone:</span> <span class="text-white">${escapeHtml(d.timezone || 'N/A')}</span></div>
            </div>
        `;
    } catch (err) {
        container.innerHTML = '<p class="text-red-400 text-sm">Failed to load connection info.</p>';
    }
}

async function scanPorts() {
    const host = document.getElementById('port-host-input').value.trim();
    if (!host) return showToast('Enter a host or IP', 'error');

    const btn = document.getElementById('port-scan-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const commonPorts = [21, 22, 23, 25, 53, 80, 110, 143, 443, 445, 3306, 3389, 5432, 6379, 8080, 8443, 27017];
    const result = document.getElementById('port-scan-result');
    const grid = document.getElementById('port-scan-grid');
    result.classList.remove('hidden');
    grid.innerHTML = commonPorts.map(p => `
        <div id="port-${p}" class="bg-background border border-gray-700/50 rounded-lg px-2 py-1.5 text-center">
            <span class="text-gray-300">${p}</span>
            <span id="port-status-${p}" class="block text-xs text-gray-500 mt-0.5"><i class="fas fa-spinner fa-spin text-xs"></i></span>
        </div>
    `).join('');

    // Use online port scanner API via ipapi (fallback: just show "check manually" message)
    for (const port of commonPorts) {
        const statusEl = document.getElementById('port-status-' + port);
        const portEl = document.getElementById('port-' + port);
        // We simulate by fetching a known open-port checker
        try {
            const res = await fetch(`https://portchecker.co/checkJson?host=${encodeURIComponent(host)}&port=${port}`, { signal: AbortSignal.timeout(3000) });
            const d = await res.json();
            if (d.isOpen || d.open) {
                statusEl.textContent = 'Open';
                statusEl.className = 'block text-xs text-green-400 mt-0.5 font-bold';
                portEl.classList.add('border-green-500/40');
            } else {
                statusEl.textContent = 'Closed';
                statusEl.className = 'block text-xs text-red-400/60 mt-0.5';
            }
        } catch {
            statusEl.textContent = 'N/A';
            statusEl.className = 'block text-xs text-gray-600 mt-0.5';
        }
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-wifi"></i> Scan';
}

async function resolveDNS() {
    const domain = document.getElementById('dns-input').value.trim();
    if (!domain) return showToast('Enter a domain', 'error');

    const result = document.getElementById('dns-result');
    result.classList.remove('hidden');
    result.innerHTML = '<p class="text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Resolving...</p>';

    try {
        const res = await fetch(`https://dns.google/resolve?name=${encodeURIComponent(domain)}&type=A`);
        const d = await res.json();

        if (!d.Answer || !d.Answer.length) {
            result.innerHTML = '<p class="text-yellow-400 text-sm">No A records found for this domain.</p>';
            return;
        }

        result.innerHTML = '<p class="text-gray-400 mb-2 text-xs font-medium">A Records:</p>' +
            d.Answer.filter(r => r.type === 1).map(r =>
                `<div class="flex items-center justify-between"><span class="text-white">${escapeHtml(r.data)}</span><span class="text-gray-500 text-xs">TTL: ${r.TTL}s</span></div>`
            ).join('');

        // Also get AAAA
        try {
            const res6 = await fetch(`https://dns.google/resolve?name=${encodeURIComponent(domain)}&type=AAAA`);
            const d6 = await res6.json();
            if (d6.Answer && d6.Answer.length) {
                result.innerHTML += '<p class="text-gray-400 mt-3 mb-2 text-xs font-medium">AAAA Records (IPv6):</p>' +
                    d6.Answer.filter(r => r.type === 28).map(r =>
                        `<div class="flex items-center justify-between"><span class="text-white text-xs break-all">${escapeHtml(r.data)}</span><span class="text-gray-500 text-xs ml-2">TTL: ${r.TTL}s</span></div>`
                    ).join('');
            }
        } catch {}
    } catch (err) {
        result.innerHTML = '<p class="text-red-400 text-sm">Failed to resolve DNS.</p>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
}

loadMyIPInfo();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
