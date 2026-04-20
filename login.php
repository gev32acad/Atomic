<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) { header('Location: dashboard.php'); exit; }
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="flex justify-center bg-background px-4 pt-24">
    <div class="w-full max-w-md bg-panel p-8 rounded-xl shadow-xl border border-gray-700/50">
        <h1 class="text-2xl font-bold text-center text-white">Sign In</h1>
        <p class="text-center text-gray-300 mb-6">Sign in to access all features.</p>
        
        <form id="login-form" class="space-y-6">
            <div class="flex items-center gap-2 border border-gray-700 rounded px-3 py-2 bg-background">
                <i class="fas fa-user text-gray-400"></i>
                <input type="text" name="username" placeholder="Username" required
                    class="w-full bg-transparent text-white placeholder-gray-500 focus:outline-none">
            </div>
            
            <div class="flex items-center gap-2 border border-gray-700 rounded px-3 py-2 bg-background">
                <i class="fas fa-lock text-gray-400"></i>
                <input type="password" name="password" placeholder="Password" required
                    class="w-full bg-transparent text-white placeholder-gray-500 focus:outline-none">
            </div>
            
            <button type="submit" id="login-btn"
                class="w-full py-2 rounded font-semibold bg-blue-600 text-white hover:bg-blue-700 transition disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed">
                Login
            </button>
        </form>
        
        <p class="text-sm text-center text-gray-400 mt-4">
            Don't have an account? <a href="register.php" class="text-blue-400 hover:underline">Sign up</a>
        </p>
    </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('login-btn');
    btn.disabled = true;
    btn.textContent = 'Verifying...';
    
    const formData = new FormData(this);
    
    try {
        const res = await fetch('api/login.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Logged in successfully!', 'success');
            setTimeout(() => window.location.href = 'dashboard.php', 500);
        } else {
            showToast(data.detail || 'Login failed', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Login';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
