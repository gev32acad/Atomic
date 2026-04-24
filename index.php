<?php
require_once __DIR__ . '/includes/auth.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- Hero Section -->
<section class="relative flex flex-col items-center justify-center text-center px-4 py-24 overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="w-full h-full bg-gradient-to-b from-blue-900/20 to-transparent"></div>
    </div>
    <div class="relative z-10 max-w-4xl mx-auto">
        <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
            The Most Powerful <span class="text-blue-500">IP Stresser</span>
        </h1>
        <p class="text-xl text-gray-300 mb-8 max-w-2xl mx-auto">
            High-performance load testing tool with advanced methods. Test your infrastructure with enterprise-grade power.
        </p>
        <div class="flex gap-4 justify-center flex-wrap">
            <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition transform hover:scale-105">
                Get Started Free
            </a>
            <a href="store.php" class="bg-transparent border border-blue-500 hover:bg-blue-900/30 text-blue-400 hover:text-blue-300 font-semibold px-8 py-3 rounded-lg transition">
                <i class="fas fa-shopping-cart mr-2"></i>Buy a Plan
            </a>
            <a href="#features" class="border border-gray-600 hover:border-blue-500 text-gray-300 hover:text-white font-semibold px-8 py-3 rounded-lg transition">
                Learn More
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-20 px-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-white mb-12">Why Choose AtomicStresser?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $features = [
                ['icon' => 'fa-bolt', 'title' => 'High-Speed Network', 'desc' => 'Enterprise-grade network infrastructure for maximum performance.'],
                ['icon' => 'fa-eye-slash', 'title' => 'Untraceable', 'desc' => 'Advanced anonymization ensures complete privacy.'],
                ['icon' => 'fa-layer-group', 'title' => 'Advanced Methods', 'desc' => 'Layer 4 & Layer 7 attack vectors with premium options.'],
                ['icon' => 'fa-headset', 'title' => '24/7 Support', 'desc' => 'Round-the-clock support via Telegram and Discord.']
            ];
            foreach ($features as $feature): ?>
            <div class="bg-panel border border-gray-700/50 rounded-xl p-6 hover:border-blue-500/50 transition">
                <div class="text-blue-400 text-3xl mb-4"><i class="fas <?= $feature['icon'] ?>"></i></div>
                <h3 class="text-lg font-semibold text-white mb-2"><?= $feature['title'] ?></h3>
                <p class="text-gray-400 text-sm"><?= $feature['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-20 px-4 bg-panel/30">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-white mb-4">Pricing Plans</h2>
        <p class="text-center text-gray-400 mb-12">Upgrade with crypto — instant, private, secure.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $plans_data = read_json('plans.json');
            foreach ($plans_data as $plan):
                $is_free = $plan['price'] == 0;
                $popular = !empty($plan['premium']) && $plan['name'] === 'Advanced';
            ?>
            <div class="relative bg-panel border <?= $popular ? 'border-blue-500' : 'border-gray-700/50' ?> rounded-xl p-6 hover:border-blue-500/50 transition flex flex-col">
                <?php if ($popular): ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full">Popular</span>
                <?php endif; ?>
                <h3 class="text-xl font-bold text-white mb-2"><?= htmlspecialchars($plan['name']) ?></h3>
                <p class="text-3xl font-bold text-blue-400 mb-1"><?= $is_free ? 'Free' : '$' . number_format($plan['price'], 2) ?></p>
                <?php if (!$is_free): ?>
                <p class="text-gray-500 text-xs mb-4">/ <?= $plan['duration_days'] ?? 30 ?> days</p>
                <?php else: ?>
                <p class="text-gray-500 text-xs mb-4">forever</p>
                <?php endif; ?>
                <ul class="space-y-3 flex-1">
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <i class="fas fa-check text-green-400 text-xs"></i> <?= $plan['max_concurrents'] ?> Concurrent<?= $plan['max_concurrents'] > 1 ? 's' : '' ?>
                    </li>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <i class="fas fa-check text-green-400 text-xs"></i> <?= $plan['max_seconds'] >= 3600 ? floor($plan['max_seconds']/3600).'h' : $plan['max_seconds'].'s' ?> Max
                    </li>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <?php if (!empty($plan['premium'])): ?>
                        <i class="fas fa-check text-green-400 text-xs"></i> Premium Methods
                        <?php else: ?>
                        <i class="fas fa-times text-gray-600 text-xs"></i> <span class="text-gray-500">Basic Methods</span>
                        <?php endif; ?>
                    </li>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <?php if (!empty($plan['api_access'])): ?>
                        <i class="fas fa-check text-green-400 text-xs"></i> API Access
                        <?php else: ?>
                        <i class="fas fa-times text-gray-600 text-xs"></i> <span class="text-gray-500">No API Access</span>
                        <?php endif; ?>
                    </li>
                </ul>
                <a href="<?= $is_free ? 'register.php' : 'store.php' ?>" class="mt-6 block text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                    <?= $is_free ? 'Get Started' : 'Buy Now' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-gray-500 text-sm mt-6">
            <i class="fab fa-bitcoin mr-1"></i> Bitcoin &nbsp;
            <i class="fab fa-ethereum mr-1"></i> Ethereum &nbsp;
            <i class="fas fa-coins mr-1"></i> Litecoin &nbsp;
            <i class="fas fa-shield-alt mr-1"></i> Monero
            &mdash; accepted
        </p>
    </div>
</section>

<!-- Methods Section -->
<section class="py-20 px-4">
    <div class="max-w-6xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-white mb-12">Attack Methods</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
                <h3 class="text-xl font-semibold text-blue-400 mb-4"><i class="fas fa-globe"></i> Layer 7 Methods</h3>
                <div class="space-y-3">
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">HTTP-GET</span>
                        <p class="text-gray-400 text-sm">HTTP GET Flood</p>
                    </div>
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">HTTP-POST</span>
                        <p class="text-gray-400 text-sm">HTTP POST Flood</p>
                    </div>
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">HTTP-OVH</span>
                        <span class="ml-2 text-xs bg-yellow-600/20 text-yellow-400 px-2 py-0.5 rounded">Premium</span>
                        <p class="text-gray-400 text-sm">Bypass OVH Protection</p>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-semibold text-blue-400 mb-4"><i class="fas fa-network-wired"></i> Layer 4 Methods</h3>
                <div class="space-y-3">
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">TCP-FLOOD</span>
                        <p class="text-gray-400 text-sm">TCP SYN Flood</p>
                    </div>
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">UDP-FLOOD</span>
                        <p class="text-gray-400 text-sm">UDP Flood Attack</p>
                    </div>
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">SYN-FLOOD</span>
                        <span class="ml-2 text-xs bg-yellow-600/20 text-yellow-400 px-2 py-0.5 rounded">Premium</span>
                        <p class="text-gray-400 text-sm">SYN Flood Attack</p>
                    </div>
                    <div class="bg-panel border border-gray-700/50 rounded-lg p-4">
                        <span class="text-white font-medium">DNS-AMP</span>
                        <span class="ml-2 text-xs bg-yellow-600/20 text-yellow-400 px-2 py-0.5 rounded">Premium</span>
                        <p class="text-gray-400 text-sm">DNS Amplification</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 px-4 bg-panel/30">
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-white mb-12">FAQ</h2>
        <div class="space-y-4">
            <?php
            $faqs = [
                ['q' => 'What is AtomicStresser?', 'a' => 'AtomicStresser is a high-performance IP stresser and load testing tool designed to test the resilience of your network infrastructure.'],
                ['q' => 'What payment methods are accepted?', 'a' => 'We accept cryptocurrency payments (Bitcoin, Ethereum, Litecoin) for maximum privacy and security.'],
                ['q' => 'Is it anonymous?', 'a' => 'Yes, all tests are completely private. We use advanced techniques to protect your identity.'],
                ['q' => 'Is this legal?', 'a' => 'AtomicStresser should only be used for authorized testing of your own infrastructure. Unauthorized use against third-party systems is illegal.']
            ];
            foreach ($faqs as $i => $faq): ?>
            <div class="bg-panel border border-gray-700/50 rounded-xl overflow-hidden">
                <button onclick="toggleFaq(<?= $i ?>)" class="w-full flex items-center justify-between p-5 text-left">
                    <span class="text-white font-medium"><?= $faq['q'] ?></span>
                    <i id="faq-icon-<?= $i ?>" class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </button>
                <div id="faq-<?= $i ?>" class="hidden px-5 pb-5">
                    <p class="text-gray-400"><?= $faq['a'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="border-t border-gray-700/50 py-8 px-4">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <p class="text-gray-400 text-sm">&copy; <?= date('Y') ?> AtomicStresser. All rights reserved.</p>
        <div class="flex items-center gap-4">
            <a href="https://t.me/atomicstresser" target="_blank" class="text-gray-400 hover:text-blue-400 transition">
                <i class="fab fa-telegram text-xl"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                <i class="fab fa-discord text-xl"></i>
            </a>
        </div>
    </div>
</footer>

<script>
function toggleFaq(index) {
    const content = document.getElementById('faq-' + index);
    const icon = document.getElementById('faq-icon-' + index);
    content.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
