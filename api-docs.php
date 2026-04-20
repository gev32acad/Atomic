<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$api_link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api';
?>

<div class="min-h-screen p-6 text-white">
    <div class="max-w-5xl mx-auto space-y-10">
        <h1 class="text-4xl font-bold text-center">API Reference</h1>

        <!-- API Link -->
        <div>
            <h2 class="text-2xl font-semibold mb-2">API Link</h2>
            <div class="bg-panel border border-gray-700 rounded-lg px-4 py-3 flex items-center justify-between">
                <code class="text-blue-400 break-all"><?= htmlspecialchars($api_link) ?></code>
                <button onclick="copyText('<?= htmlspecialchars($api_link) ?>')">
                    <i class="fas fa-copy text-gray-400 hover:text-white"></i>
                </button>
            </div>
        </div>

        <!-- API Key -->
        <div>
            <h2 class="text-2xl font-semibold mb-2">Your API Key</h2>
            <div class="bg-panel border border-gray-700 rounded-lg px-4 py-3 flex items-center justify-between">
                <code class="text-green-400 break-all"><?= htmlspecialchars($user['api_key'] ?? 'N/A') ?></code>
                <button onclick="copyText('<?= htmlspecialchars($user['api_key'] ?? '') ?>')">
                    <i class="fas fa-copy text-gray-400 hover:text-white"></i>
                </button>
            </div>
        </div>

        <!-- API Fields Table -->
        <div>
            <h2 class="text-2xl font-semibold mb-2">API Fields</h2>
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
            <h2 class="text-2xl font-semibold mb-2">Ongoing Tests</h2>
            <p class="text-sm text-gray-400 mb-2">Use this link to retrieve ongoing tests:</p>
            <div class="bg-panel border border-gray-700 rounded-lg px-4 py-3">
                <code class="break-all text-gray-300"><?= htmlspecialchars($api_link) ?>/attack.php?key=YOUR_API_KEY</code>
            </div>
        </div>
    </div>
</div>

<script>
function copyText(text) {
    navigator.clipboard.writeText(text);
    showToast('Copied to clipboard!', 'success');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
