<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
$page_title = 'API Docs';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$host = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? '');
// Validate: must look like a hostname, IPv4, or bracketed IPv6
if (!preg_match('/^(\[[\da-fA-F:]+\]|[\w.\-]+(:\d+)?)$/', $host)) {
    $host = 'localhost';
}
$api_link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $host . rtrim(dirname($_SERVER['REQUEST_URI']), '/') . '/api';
?>

<div class="min-h-screen p-4 lg:p-6 text-white">
    <div class="max-w-5xl mx-auto space-y-8">
        <h1 class="text-2xl lg:text-4xl font-bold text-center">API Reference</h1>

        <!-- API Link -->
        <div>
            <h2 class="text-xl lg:text-2xl font-semibold mb-2">API Link</h2>
            <div class="bg-panel border border-gray-700 rounded-lg px-4 py-3 flex items-center justify-between gap-3">
                <code class="text-blue-400 break-all text-sm"><?= htmlspecialchars($api_link) ?></code>
                <button onclick="copyText('<?= htmlspecialchars($api_link) ?>')" class="shrink-0">
                    <i class="fas fa-copy text-gray-400 hover:text-white"></i>
                </button>
            </div>
        </div>

        <!-- API Key -->
        <div>
            <h2 class="text-xl lg:text-2xl font-semibold mb-2">Your API Key</h2>
            <div class="bg-panel border border-gray-700 rounded-lg px-4 py-3 flex items-center justify-between gap-3">
                <code id="api-key-display" class="text-green-400 break-all text-sm blur-sm select-none transition-all duration-200"><?= htmlspecialchars($user['api_key'] ?? 'N/A') ?></code>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="toggleKeyVisibility()" id="toggle-key-btn" title="Show/Hide key">
                        <i id="toggle-key-icon" class="fas fa-eye text-gray-400 hover:text-white"></i>
                    </button>
                    <button onclick="copyText('<?= htmlspecialchars($user['api_key'] ?? '') ?>')" title="Copy key">
                        <i class="fas fa-copy text-gray-400 hover:text-white"></i>
                    </button>
                </div>
            </div>
            <p class="text-xs text-gray-600 mt-1">Click the eye icon to reveal your full key.</p>
        </div>

        <!-- API Fields Table -->
        <div>
            <h2 class="text-xl lg:text-2xl font-semibold mb-2">API Fields</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-300 border border-gray-700 rounded-lg overflow-hidden">
                    <thead class="bg-gray-800 text-gray-100 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">Field</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Value</th>
                            <th class="px-4 py-3">Required</th>
                        </tr>
                    </thead>
                    <tbody class="bg-panel">
                        <tr class="border-t border-gray-700"><td class="px-4 py-3 font-medium text-white">key</td><td class="px-4 py-3">Your API Key</td><td class="px-4 py-3 text-blue-400"><?= substr($user['api_key'] ?? '', 0, 8) ?>...</td><td class="px-4 py-3">&#10004;&#65039;</td></tr>
                        <tr class="border-t border-gray-700"><td class="px-4 py-3 font-medium text-white">ip</td><td class="px-4 py-3">Target IPv4/Subnet or URL</td><td class="px-4 py-3 text-blue-400">74.74.74.8, https://google.com</td><td class="px-4 py-3">&#10004;&#65039;</td></tr>
                        <tr class="border-t border-gray-700"><td class="px-4 py-3 font-medium text-white">port</td><td class="px-4 py-3">Target Port</td><td class="px-4 py-3 text-blue-400">0 - 65535</td><td class="px-4 py-3">&#10004;&#65039;</td></tr>
                        <tr class="border-t border-gray-700"><td class="px-4 py-3 font-medium text-white">time</td><td class="px-4 py-3">Test duration (seconds)</td><td class="px-4 py-3 text-blue-400">30 or longer</td><td class="px-4 py-3">&#10004;&#65039;</td></tr>
                        <tr class="border-t border-gray-700"><td class="px-4 py-3 font-medium text-white">method</td><td class="px-4 py-3">Method requested</td><td class="px-4 py-3 text-blue-400">See available methods</td><td class="px-4 py-3">&#10004;&#65039;</td></tr>
                        <tr class="border-t border-gray-700"><td class="px-4 py-3 font-medium text-white">concurrents</td><td class="px-4 py-3">Concurrents to send</td><td class="px-4 py-3 text-blue-400">1, 2, 3...</td><td class="px-4 py-3">&#10060;</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ongoing Tests -->
        <div>
            <h2 class="text-xl lg:text-2xl font-semibold mb-2">Ongoing Tests</h2>
            <p class="text-sm text-gray-400 mb-2">Use this link to retrieve ongoing tests:</p>
            <div class="bg-panel border border-gray-700 rounded-lg px-4 py-3 overflow-x-auto">
                <code class="break-all text-gray-300 text-sm"><?= htmlspecialchars($api_link) ?>/attack.php?key=YOUR_API_KEY</code>
            </div>
        </div>
    </div>
</div>

<script>
let keyVisible = false;
function toggleKeyVisibility() {
    const el = document.getElementById('api-key-display');
    const icon = document.getElementById('toggle-key-icon');
    keyVisible = !keyVisible;
    el.classList.toggle('blur-sm', !keyVisible);
    el.classList.toggle('select-none', !keyVisible);
    icon.className = keyVisible ? 'fas fa-eye-slash text-gray-400 hover:text-white' : 'fas fa-eye text-gray-400 hover:text-white';
}
function copyText(text) {
    navigator.clipboard.writeText(text);
    showToast('Copied to clipboard!', 'success');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
