<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_auth();
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$avatar = strtoupper(implode('', array_map(function($n) { return $n[0] ?? ''; }, explode(' ', $user['username']))));
$join_date = date('M j, Y', strtotime($user['join_date']));
$max_hours = floor($user['max_seconds'] / 3600);
$max_minutes = floor(($user['max_seconds'] % 3600) / 60);
$exp_date = $user['expiration_date'] ? date('M j, Y', strtotime($user['expiration_date'])) : 'No expiration';
?>

<div class="p-6">
    <div class="max-w-xl mx-auto">
        
        <!-- Info Notice -->
        <div class="mb-8 flex items-center gap-3 text-sm bg-blue-500/10 border border-blue-500/20 rounded-xl px-6 py-4">
            <i class="fas fa-info-circle text-blue-400"></i>
            <p class="text-blue-300">
                We never request sensitive information. Only your User ID or Username is required when purchasing a plan.
            </p>
        </div>
        
        <!-- Avatar -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 rounded-full bg-blue-600 mx-auto flex items-center justify-center text-2xl font-bold text-white">
                <?= htmlspecialchars($avatar) ?>
            </div>
            <h2 class="text-xl font-semibold text-white mt-2"><?= htmlspecialchars($user['username']) ?></h2>
            <p class="text-sm text-gray-400"><?= htmlspecialchars($user['email']) ?></p>
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
                <p class="text-white font-semibold"><?= htmlspecialchars($user['rule']) ?></p>
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
                <p class="text-white font-semibold"><?= $max_hours ?>h <?= $max_minutes ?>m</p>
            </div>
            <div class="bg-panel border border-gray-600 rounded-lg p-4">
                <p class="text-sm text-gray-400">Expires On</p>
                <p class="text-white font-semibold"><?= $exp_date ?></p>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    showToast('Copied to clipboard!', 'success');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
