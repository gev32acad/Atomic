<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user = get_authenticated_user();
$logged_in = $user !== null;
$admin = $logged_in && $user['rule'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(generate_csrf_token()) ?>">
    <title><?= SITE_NAME ?></title>
    <link rel="icon" href="assets/imagens/logo.png">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: '#0a0e1a',
                        panel: '#111827',
                        primary: '#3b82f6',
                        muted: '#374151',
                        'muted-foreground': '#9ca3af',
                        text: '#f3f4f6'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-background text-text font-sans min-h-screen">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-panel/80 backdrop-blur-sm border-b border-gray-700/50">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="flex items-center gap-2">
                    <img src="assets/imagens/logo.png" alt="Logo" class="w-8 h-8" onerror="this.style.display='none'">
                    <span class="text-xl font-bold text-white">ATOMICSTRESSER</span>
                </a>
            </div>
            <div class="flex items-center gap-4">
                <?php if ($logged_in): ?>
                    <button id="sidebar-toggle" class="lg:hidden text-white">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                <?php endif; ?>
                <a href="https://t.me/atomicstresser" target="_blank" class="text-gray-400 hover:text-blue-400 transition">
                    <i class="fab fa-telegram text-xl"></i>
                </a>
            </div>
        </div>
    </header>

    <div class="flex">
