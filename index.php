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
        <div class="flex gap-4 justify-center">
            <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-lg transition transform hover:scale-105">
                Get Started
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
        <h2 class="text-3xl font-bold text-center text-white mb-12">Pricing Plans</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $plans = [
                ['name' => 'Starter', 'price' => 'Free', 'features' => ['1 Concurrent', '60s Max', 'Basic Methods', 'No API Access']],
                ['name' => 'Standard', 'price' => '$9.99/mo', 'features' => ['3 Concurrents', '120s Max', 'Basic Methods', 'API Access']],
                ['name' => 'Advanced', 'price' => '$19.99/mo', 'features' => ['5 Concurrents', '300s Max', 'Premium Methods', 'API Access'], 'popular' => true],
                ['name' => 'Enterprise', 'price' => '$49.99/mo', 'features' => ['10 Concurrents', '3600s Max', 'All Methods', 'Full API Access']]
            ];
            foreach ($plans as $plan): ?>
            <div class="relative bg-panel border <?= isset($plan['popular']) ? 'border-blue-500' : 'border-gray-700/50' ?> rounded-xl p-6 hover:border-blue-500/50 transition">
                <?php if (isset($plan['popular'])): ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full">Popular</span>
                <?php endif; ?>
                <h3 class="text-xl font-bold text-white mb-2"><?= $plan['name'] ?></h3>
                <p class="text-3xl font-bold text-blue-400 mb-6"><?= $plan['price'] ?></p>
                <ul class="space-y-3">
                    <?php foreach ($plan['features'] as $f): ?>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <i class="fas fa-check text-green-400 text-xs"></i> <?= $f ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <a href="register.php" class="mt-6 block text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                    Choose Plan
                </a>
            </div>
            <?php endforeach; ?>
        </div>
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
