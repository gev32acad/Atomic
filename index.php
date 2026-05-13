<?php
require_once __DIR__ . '/includes/auth.php';
$page_title = 'Home';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- Hero Section -->
<section class="hero-section relative flex flex-col items-center justify-center text-center px-4 py-32 overflow-hidden">
    <!-- Animated background orbs -->
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
    <!-- Grid overlay -->
    <div class="hero-grid"></div>

    <div class="relative z-10 max-w-4xl mx-auto">
        <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 leading-tight">
            The Most Powerful<br>
            <span class="hero-gradient-text">IP Stresser</span>
        </h1>
        <p class="text-lg md:text-xl text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
            Enterprise-grade network stress testing with advanced Layer&nbsp;4 &amp; Layer&nbsp;7 methods.<br class="hidden md:block"> Instant setup. Crypto payments. 24/7 support.
        </p>

        <div class="flex gap-3 justify-center flex-wrap mb-14">
            <a href="register.php" class="hero-btn-primary">
                <i class="fas fa-rocket mr-2"></i>Get Started Free
            </a>
            <a href="store.php" class="hero-btn-secondary">
                <i class="fas fa-shopping-cart mr-2"></i>View Plans
            </a>
        </div>

        <!-- Stats row -->
        <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto">
            <div class="hero-stat">
                <span class="hero-stat-value text-blue-400" style="font-size:1.1rem;padding-top:4px">Best</span>
                <span class="hero-stat-label">Bypasses</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value">99.9<span class="text-blue-400">%</span></span>
                <span class="hero-stat-label">Uptime</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value">24<span class="text-blue-400">/7</span></span>
                <span class="hero-stat-label">Support</span>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-24 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="section-label">Why NetStress?</div>
        <h2 class="section-title">Built for Performance</h2>
        <p class="section-sub">Everything you need to stress-test your infrastructure at scale.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mt-14">
            <?php
            $features = [
                ['icon' => 'fa-bolt',        'color' => 'text-yellow-400', 'bg' => 'bg-yellow-400/10', 'title' => 'High-Speed Network',  'desc' => 'Enterprise-grade infrastructure delivering maximum throughput.'],
                ['icon' => 'fa-eye-slash',   'color' => 'text-purple-400', 'bg' => 'bg-purple-400/10', 'title' => 'Untraceable',         'desc' => 'Advanced anonymization and IP masking for complete privacy.'],
                ['icon' => 'fa-layer-group', 'color' => 'text-blue-400',   'bg' => 'bg-blue-400/10',   'title' => 'Advanced Methods',    'desc' => 'Layer 4 & Layer 7 vectors with premium bypass options.'],
                ['icon' => 'fa-headset',     'color' => 'text-green-400',  'bg' => 'bg-green-400/10',  'title' => '24/7 Support',        'desc' => 'Round-the-clock help via Telegram and Discord.'],
            ];
            foreach ($features as $f): ?>
            <div class="feature-card group">
                <div class="feature-icon-wrap <?= $f['bg'] ?> <?= $f['color'] ?>">
                    <i class="fas <?= $f['icon'] ?>"></i>
                </div>
                <h3 class="text-white font-semibold mt-4 mb-2"><?= $f['title'] ?></h3>
                <p class="text-gray-500 text-sm leading-relaxed"><?= $f['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-24 px-4 relative overflow-hidden">
    <div class="pricing-bg-glow"></div>
    <div class="max-w-6xl mx-auto relative z-10">
        <div class="section-label">Pricing</div>
        <h2 class="section-title">Choose Your Plan</h2>
        <p class="section-sub">Upgrade instantly with crypto &mdash; private, secure, no KYC.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mt-14">
            <?php
            $plans_data = read_json('plans.json');
            foreach ($plans_data as $plan):
                $is_free = $plan['price'] == 0;
                $popular = !empty($plan['popular']);
            ?>
            <div class="pricing-card <?= $popular ? 'pricing-card-popular' : '' ?> flex flex-col">
                <?php if ($popular): ?>
                <div class="pricing-popular-badge">⭐ Most Popular</div>
                <?php endif; ?>

                <div class="mb-5">
                    <h3 class="text-lg font-bold text-white mb-3"><?= htmlspecialchars($plan['name']) ?></h3>
                    <div class="flex items-end gap-1">
                        <span class="text-4xl font-extrabold <?= $popular ? 'text-blue-400' : 'text-white' ?>">
                            <?= $is_free ? 'Free' : '$' . number_format($plan['price'], 2) ?>
                        </span>
                        <?php if (!$is_free): ?>
                        <span class="text-gray-500 text-sm mb-1.5">/ <?= $plan['duration_days'] ?? 30 ?>d</span>
                        <?php endif; ?>
                    </div>
                </div>

                <ul class="space-y-2.5 flex-1 mb-6">
                    <li class="pricing-feature">
                        <i class="fas fa-check text-green-400 text-xs shrink-0"></i>
                        <span><?= $plan['max_concurrents'] ?> Concurrent<?= $plan['max_concurrents'] > 1 ? 's' : '' ?></span>
                    </li>
                    <li class="pricing-feature">
                        <i class="fas fa-check text-green-400 text-xs shrink-0"></i>
                        <span><?= $plan['max_seconds'] >= 3600 ? floor($plan['max_seconds']/3600).'h' : $plan['max_seconds'].'s' ?> Max Duration</span>
                    </li>
                    <li class="pricing-feature <?= empty($plan['premium']) ? 'opacity-40' : '' ?>">
                        <i class="fas <?= !empty($plan['premium']) ? 'fa-check text-green-400' : 'fa-times text-gray-600' ?> text-xs shrink-0"></i>
                        <span>Premium Methods</span>
                    </li>
                    <li class="pricing-feature <?= empty($plan['api_access']) ? 'opacity-40' : '' ?>">
                        <i class="fas <?= !empty($plan['api_access']) ? 'fa-check text-green-400' : 'fa-times text-gray-600' ?> text-xs shrink-0"></i>
                        <span>API Access</span>
                    </li>
                </ul>

                <a href="<?= $is_free ? 'register.php' : 'store.php' ?>"
                   class="<?= $popular ? 'pricing-btn-primary' : 'pricing-btn-secondary' ?>">
                    <?= $is_free ? 'Get Started' : 'Buy Now' ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center justify-center gap-5 mt-8 text-gray-600 text-sm flex-wrap">
            <span><i class="fab fa-bitcoin mr-1 text-yellow-500"></i>Bitcoin</span>
            <span><i class="fab fa-ethereum mr-1 text-purple-400"></i>Ethereum</span>
            <span><i class="fas fa-coins mr-1 text-gray-400"></i>Litecoin</span>
            <span><i class="fas fa-shield-alt mr-1 text-orange-400"></i>Monero</span>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="border-t border-gray-700/30 py-10 px-4">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <img src="assets/imagens/logo.png" alt="Logo" class="w-6 h-6 opacity-60" onerror="this.style.display='none'">
            <p class="text-gray-500 text-sm">&copy; <?= date('Y') ?> NetStress. All rights reserved.</p>
        </div>
        <div class="flex items-center gap-5">
            <a href="https://t.me/netstressme" target="_blank" class="text-gray-500 hover:text-blue-400 transition text-sm flex items-center gap-1.5">
                <i class="fab fa-telegram"></i> Telegram
            </a>
            <a href="#" class="text-gray-500 hover:text-indigo-400 transition text-sm flex items-center gap-1.5">
                <i class="fab fa-discord"></i> Discord
            </a>
        </div>
    </div>
</footer>

<?php include __DIR__ . '/includes/footer.php'; ?>
