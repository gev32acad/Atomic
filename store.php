<?php
require_once __DIR__ . '/includes/auth.php';
$logged_in = is_logged_in();
$user = $logged_in ? get_authenticated_user() : null;
$csrf_token = generate_csrf_token();
$plans = read_json('plans.json');
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="min-h-screen p-6">
    <div class="max-w-7xl mx-auto">

        <!-- Page Header -->
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-white mb-3">Choose Your Plan</h1>
            <p class="text-gray-400 text-lg">Upgrade your account with cryptocurrency. Fast, private, secure.</p>
        </div>

        <!-- Crypto Payment Notice -->
        <div class="mb-8 flex items-start gap-3 text-sm bg-blue-500/10 border border-blue-500/20 rounded-xl px-6 py-4 max-w-2xl mx-auto">
            <i class="fas fa-coins text-blue-400 mt-0.5 text-base"></i>
            <div>
                <p class="text-blue-300 font-medium mb-1">Accepted: Bitcoin, Ethereum, Litecoin, Monero</p>
                <p class="text-blue-200/70 text-xs">After payment, submit your order below. An admin will verify and activate your plan within minutes via Telegram.</p>
            </div>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <?php foreach ($plans as $plan):
                $is_free = $plan['price'] == 0;
                $is_current = $user && $user['plan'] === $plan['name'];
            ?>
            <div class="relative bg-panel border <?= (!empty($plan['premium'])) ? 'border-blue-500/60' : 'border-gray-700/50' ?> rounded-2xl p-6 flex flex-col hover:border-blue-500/70 transition">
                <?php if (!empty($plan['premium'])): ?>
                <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full whitespace-nowrap">
                    <i class="fas fa-star mr-1"></i>Premium
                </span>
                <?php endif; ?>

                <div class="mb-4">
                    <h3 class="text-xl font-bold text-white mb-1"><?= htmlspecialchars($plan['name']) ?></h3>
                    <p class="text-gray-400 text-sm"><?= htmlspecialchars($plan['description'] ?? '') ?></p>
                </div>

                <p class="text-3xl font-bold text-blue-400 mb-1">
                    <?= $is_free ? 'Free' : '$' . number_format($plan['price'], 2) ?>
                </p>
                <?php if (!$is_free): ?>
                <p class="text-gray-500 text-xs mb-4">per <?= $plan['duration_days'] ?? 30 ?> days</p>
                <?php else: ?>
                <p class="text-gray-500 text-xs mb-4">forever</p>
                <?php endif; ?>

                <ul class="space-y-2 mb-6 flex-1">
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <i class="fas fa-check text-green-400 text-xs w-4"></i>
                        <?= $plan['max_concurrents'] ?> Concurrent<?= $plan['max_concurrents'] > 1 ? 's' : '' ?>
                    </li>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <i class="fas fa-check text-green-400 text-xs w-4"></i>
                        <?= $plan['max_seconds'] >= 3600 ? floor($plan['max_seconds']/3600).'h' : $plan['max_seconds'].'s' ?> Max Duration
                    </li>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <?php if (!empty($plan['premium'])): ?>
                        <i class="fas fa-check text-green-400 text-xs w-4"></i>
                        <span>Premium Methods</span>
                        <?php else: ?>
                        <i class="fas fa-times text-gray-600 text-xs w-4"></i>
                        <span class="text-gray-500">Basic Methods Only</span>
                        <?php endif; ?>
                    </li>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <?php if (!empty($plan['api_access'])): ?>
                        <i class="fas fa-check text-green-400 text-xs w-4"></i>
                        <span>API Access</span>
                        <?php else: ?>
                        <i class="fas fa-times text-gray-600 text-xs w-4"></i>
                        <span class="text-gray-500">No API Access</span>
                        <?php endif; ?>
                    </li>
                    <?php if (!$is_free): ?>
                    <li class="text-gray-300 text-sm flex items-center gap-2">
                        <i class="fas fa-check text-green-400 text-xs w-4"></i>
                        <span><?= $plan['duration_days'] ?? 30 ?> Days Access</span>
                    </li>
                    <?php endif; ?>
                </ul>

                <?php if ($is_current): ?>
                <div class="w-full text-center bg-gray-700 text-gray-400 font-semibold py-2 rounded-lg text-sm cursor-default">
                    <i class="fas fa-check-circle mr-1 text-green-400"></i> Current Plan
                </div>
                <?php elseif ($is_free): ?>
                <?php if (!$logged_in): ?>
                <a href="register.php" class="w-full block text-center bg-gray-600 hover:bg-gray-500 text-white font-semibold py-2 rounded-lg transition text-sm">
                    Register Free
                </a>
                <?php else: ?>
                <div class="w-full text-center bg-gray-700/50 text-gray-500 font-semibold py-2 rounded-lg text-sm cursor-default">
                    Free Plan
                </div>
                <?php endif; ?>
                <?php elseif (!$logged_in): ?>
                <a href="register.php" class="w-full block text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition text-sm">
                    Register to Buy
                </a>
                <?php else: ?>
                <button onclick="openBuyModal(<?= json_encode($plan['id']) ?>, <?= json_encode($plan['name']) ?>, <?= json_encode((float)$plan['price']) ?>, <?= json_encode((int)($plan['duration_days'] ?? 30)) ?>)"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition text-sm">
                    <i class="fas fa-shopping-cart mr-1"></i> Buy Now
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($logged_in): ?>
        <!-- My Orders -->
        <div class="bg-panel border border-gray-700/50 rounded-2xl p-6">
            <h2 class="text-xl font-bold text-white mb-4"><i class="fas fa-receipt mr-2 text-blue-400"></i>My Orders</h2>
            <div id="my-orders-list">
                <p class="text-gray-400 text-center py-6"><i class="fas fa-spinner fa-spin mr-2"></i>Loading orders...</p>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php if ($logged_in): ?>
<!-- Buy Modal -->
<div id="buy-modal" class="hidden fixed inset-0 bg-black/75 z-50 flex items-center justify-center p-4">
    <div class="bg-panel border border-gray-700 rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-coins mr-2 text-yellow-400"></i>Purchase Plan</h3>
            <button onclick="closeBuyModal()" class="text-gray-400 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>

        <div id="buy-step-1">
            <p class="text-gray-300 mb-5 text-sm">You are purchasing: <span id="modal-plan-name" class="text-blue-400 font-semibold"></span> — <span id="modal-plan-price" class="text-white font-bold"></span> for <span id="modal-plan-days"></span> days.</p>

            <p class="text-gray-400 text-sm mb-4">Select your preferred cryptocurrency:</p>
            <div class="grid grid-cols-2 gap-3 mb-5">
                <button onclick="selectCrypto('BTC')" id="crypto-btn-BTC" class="crypto-btn flex items-center gap-2 border border-gray-700 hover:border-yellow-500 rounded-xl p-3 text-left transition">
                    <span class="text-yellow-400 text-xl"><i class="fab fa-bitcoin"></i></span>
                    <div><p class="text-white text-sm font-medium">Bitcoin</p><p class="text-gray-500 text-xs">BTC</p></div>
                </button>
                <button onclick="selectCrypto('ETH')" id="crypto-btn-ETH" class="crypto-btn flex items-center gap-2 border border-gray-700 hover:border-blue-400 rounded-xl p-3 text-left transition">
                    <span class="text-blue-400 text-xl"><i class="fab fa-ethereum"></i></span>
                    <div><p class="text-white text-sm font-medium">Ethereum</p><p class="text-gray-500 text-xs">ETH</p></div>
                </button>
                <button onclick="selectCrypto('LTC')" id="crypto-btn-LTC" class="crypto-btn flex items-center gap-2 border border-gray-700 hover:border-gray-300 rounded-xl p-3 text-left transition">
                    <span class="text-gray-300 text-xl"><i class="fas fa-coins"></i></span>
                    <div><p class="text-white text-sm font-medium">Litecoin</p><p class="text-gray-500 text-xs">LTC</p></div>
                </button>
                <button onclick="selectCrypto('XMR')" id="crypto-btn-XMR" class="crypto-btn flex items-center gap-2 border border-gray-700 hover:border-orange-400 rounded-xl p-3 text-left transition">
                    <span class="text-orange-400 text-xl"><i class="fas fa-shield-alt"></i></span>
                    <div><p class="text-white text-sm font-medium">Monero</p><p class="text-gray-500 text-xs">XMR – Most Private</p></div>
                </button>
            </div>

            <div id="payment-address-box" class="hidden mb-5 bg-background border border-gray-700 rounded-xl p-4">
                <p class="text-gray-400 text-xs mb-2">Send exactly <strong class="text-white" id="pay-amount"></strong> <span id="pay-currency"></span> to:</p>
                <div class="flex items-center gap-2">
                    <code id="pay-address" class="text-blue-300 text-xs break-all flex-1"></code>
                    <button onclick="copyPayAddress()" class="text-gray-400 hover:text-blue-400 shrink-0"><i class="fas fa-copy"></i></button>
                </div>
                <p class="text-yellow-400 text-xs mt-3"><i class="fas fa-exclamation-triangle mr-1"></i>Send the exact amount. After sending, click "I've Sent Payment" below.</p>
            </div>

            <input type="hidden" id="buy-plan-id">
            <input type="hidden" id="buy-crypto">
            <button onclick="confirmPurchase()" id="buy-confirm-btn" disabled
                class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition">
                <i class="fas fa-paper-plane mr-2"></i>I've Sent Payment – Submit Order
            </button>
        </div>

        <div id="buy-step-2" class="hidden text-center py-4">
            <div class="text-green-400 text-5xl mb-4"><i class="fas fa-check-circle"></i></div>
            <h4 class="text-white text-lg font-bold mb-2">Order Submitted!</h4>
            <p class="text-gray-400 text-sm mb-4">Your order has been submitted. An admin will verify your payment and activate your plan shortly.</p>
            <p class="text-gray-400 text-sm mb-5">Order ID: <code id="order-id-display" class="text-blue-400"></code></p>
            <a href="<?= TELEGRAM_LINK ?>" target="_blank" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl transition text-sm">
                <i class="fab fa-telegram"></i> Contact Support on Telegram
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Crypto exchange rates (approximate, in USD)
const cryptoRates = { BTC: 65000, ETH: 3200, LTC: 80, XMR: 170 };
const cryptoAddresses = {
    BTC: <?= json_encode(CRYPTO_BTC_ADDRESS) ?>,
    ETH: <?= json_encode(CRYPTO_ETH_ADDRESS) ?>,
    LTC: <?= json_encode(CRYPTO_LTC_ADDRESS) ?>,
    XMR: <?= json_encode(CRYPTO_XMR_ADDRESS) ?>
};
const csrfToken = <?= json_encode($csrf_token) ?>;

let modalPlanId = null;
let modalPlanPrice = 0;
let selectedCrypto = null;

function openBuyModal(planId, planName, planPrice, planDays) {
    modalPlanId = planId;
    modalPlanPrice = planPrice;
    document.getElementById('modal-plan-name').textContent = planName;
    document.getElementById('modal-plan-price').textContent = '$' + planPrice.toFixed(2);
    document.getElementById('modal-plan-days').textContent = planDays;
    document.getElementById('buy-plan-id').value = planId;
    document.getElementById('buy-step-1').classList.remove('hidden');
    document.getElementById('buy-step-2').classList.add('hidden');
    document.getElementById('payment-address-box').classList.add('hidden');
    document.getElementById('buy-confirm-btn').disabled = true;
    selectedCrypto = null;
    document.querySelectorAll('.crypto-btn').forEach(b => b.classList.remove('border-blue-500', 'bg-blue-900/20'));
    document.getElementById('buy-modal').classList.remove('hidden');
}

function closeBuyModal() {
    document.getElementById('buy-modal').classList.add('hidden');
}

function selectCrypto(crypto) {
    selectedCrypto = crypto;
    document.getElementById('buy-crypto').value = crypto;
    document.querySelectorAll('.crypto-btn').forEach(b => b.classList.remove('border-blue-500', 'bg-blue-900/20'));
    document.getElementById('crypto-btn-' + crypto).classList.add('border-blue-500', 'bg-blue-900/20');

    const amount = (modalPlanPrice / cryptoRates[crypto]).toFixed(8);
    document.getElementById('pay-amount').textContent = amount;
    document.getElementById('pay-currency').textContent = crypto;
    document.getElementById('pay-address').textContent = cryptoAddresses[crypto];
    document.getElementById('payment-address-box').classList.remove('hidden');
    document.getElementById('buy-confirm-btn').disabled = false;
}

function copyPayAddress() {
    const addr = document.getElementById('pay-address').textContent;
    navigator.clipboard.writeText(addr);
    showToast('Address copied!', 'success');
}

async function confirmPurchase() {
    if (!selectedCrypto || !modalPlanId) return;
    const btn = document.getElementById('buy-confirm-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';

    const amount = (modalPlanPrice / cryptoRates[selectedCrypto]).toFixed(8);
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('plan_id', modalPlanId);
    formData.append('crypto', selectedCrypto);
    formData.append('amount', amount);

    try {
        const res = await fetch('api/purchase.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (res.ok) {
            document.getElementById('order-id-display').textContent = data.order_id;
            document.getElementById('buy-step-1').classList.add('hidden');
            document.getElementById('buy-step-2').classList.remove('hidden');
            loadMyOrders();
        } else {
            showToast(data.detail || 'Failed to submit order', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>I\'ve Sent Payment – Submit Order';
        }
    } catch (err) {
        showToast('Connection error', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>I\'ve Sent Payment – Submit Order';
    }
}

async function loadMyOrders() {
    const container = document.getElementById('my-orders-list');
    if (!container) return;
    try {
        const res = await fetch('api/purchase.php');
        const orders = await res.json();
        if (!orders.length) {
            container.innerHTML = '<p class="text-gray-500 text-sm text-center py-6">No orders yet. Purchase a plan above to get started.</p>';
            return;
        }
        const statusColors = { pending: 'text-yellow-400', approved: 'text-green-400', rejected: 'text-red-400' };
        const statusIcons = { pending: 'fa-clock', approved: 'fa-check-circle', rejected: 'fa-times-circle' };
        container.innerHTML = orders.map(o => `
            <div class="flex items-center justify-between bg-background border border-gray-700/50 rounded-xl px-5 py-4 mb-3">
                <div>
                    <p class="text-white font-medium">${escapeHtml(o.plan_name)} Plan</p>
                    <p class="text-gray-400 text-xs mt-0.5">${escapeHtml(o.amount)} ${escapeHtml(o.crypto)} &bull; ${new Date(o.created_at).toLocaleString()}</p>
                    <p class="text-gray-500 text-xs">Order ID: ${escapeHtml(o.id)}</p>
                </div>
                <span class="flex items-center gap-1 text-sm font-medium ${statusColors[o.status] || 'text-gray-400'}">
                    <i class="fas ${statusIcons[o.status] || 'fa-question-circle'}"></i>
                    ${escapeHtml(o.status.charAt(0).toUpperCase() + o.status.slice(1))}
                </span>
            </div>
        `).join('');
    } catch (err) {
        container.innerHTML = '<p class="text-red-400 text-sm text-center py-6">Failed to load orders.</p>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
}

<?php if ($logged_in): ?>
loadMyOrders();
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
