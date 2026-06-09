<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ambil Tanggal Hari Ini
if (!isset($hari_ini)) {
    date_default_timezone_set('Asia/Jakarta');
    $hari_ini = date('l, d F Y');
}

// 2. Setup Icon & Warna Berdasarkan Role
$avatar_icon = 'fa-user';
$role_color = 'text-slate-600';
$role_bg = 'bg-slate-100';
$dashboard_link = 'login.php';

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'Manager Procurement':
            $avatar_icon = 'fa-user-tie';
            $role_color = 'text-red-600 bg-red-50 border-red-100';
            $dashboard_link = 'dashboard_manager.php';
            break;
        case 'Procurement':
            $avatar_icon = 'fa-user-gear';
            $role_color = 'text-amber-600 bg-amber-50 border-amber-100';
            $dashboard_link = 'dashboard_procurement.php';
            break;
        case 'Gudang':
            $avatar_icon = 'fa-warehouse';
            $role_color = 'text-indigo-600 bg-indigo-50 border-indigo-100';
            $dashboard_link = 'dashboard_gudang.php';
            break;
        case 'Finance':
            $avatar_icon = 'fa-file-invoice-dollar';
            $role_color = 'text-emerald-600 bg-emerald-50 border-emerald-100';
            $dashboard_link = 'dashboard_finance.php';
            break;
        case 'Vendor':
            $avatar_icon = 'fa-truck-field';
            $role_color = 'text-blue-600 bg-blue-50 border-blue-100';
            $dashboard_link = 'dashboard_vendor.php';
            break;
    }
}

// 3. Load Alpine.js (jika belum diload)
if (!isset($GLOBALS['alpine_loaded'])) {
    $GLOBALS['alpine_loaded'] = true;
    echo '<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>';
}
?>

<!-- UNIFIED HEADER & NAVBAR (Integrated / Connected Dark Theme) -->
<header class="bg-slate-900 text-white border-b border-slate-800 px-6 py-3 shrink-0 flex justify-between items-center gap-4 z-35 w-full">
    
    <!-- Sisi Kiri: Hamburger + Judul Halaman -->
    <div class="flex items-center gap-3">
        <button onclick="toggleSidebar()" class="w-10 h-10 rounded-xl hover:bg-slate-850 text-slate-400 hover:text-white flex items-center justify-center transition" title="Toggle Sidebar">
            <i class="fa-solid fa-bars text-base"></i>
        </button>
        <div>
            <h2 class="text-base md:text-lg font-black text-white uppercase tracking-tighter line-clamp-1">
                <?= htmlspecialchars($page_title ?? 'Veridex System', ENT_QUOTES, 'UTF-8') ?>
            </h2>
            <?php if (isset($page_subtitle) && $page_subtitle !== ''): ?>
                <p class="text-[9px] md:text-[10px] text-slate-450 font-bold tracking-widest uppercase mt-0.5 line-clamp-1">
                    <?= htmlspecialchars($page_subtitle, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sisi Kanan: Date, Badges, Notifikasi, Profile Dropdown -->
    <div class="flex items-center gap-3 md:gap-4 ml-auto">
        
        <!-- Tanggal (Sembunyi di HP kecil) -->
        <div class="hidden sm:block text-right">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider"><?= $hari_ini ?></p>
            <p class="text-[10px] font-bold text-slate-500">Sesi Aktif</p>
        </div>

        <!-- Custom Badge/Metric Slot (Jika ada) -->
        <?php if (isset($navbar_badge) && $navbar_badge !== ''): ?>
            <div class="shrink-0">
                <?= $navbar_badge ?>
            </div>
        <?php endif; ?>

        <!-- Notification Bell Component -->
        <div class="shrink-0">
            <?php include 'navbar_notifications.php'; ?>
        </div>

        <!-- Profile Dropdown (Alpine.js) -->
        <div x-data="{ openProfile: false }" class="relative shrink-0" id="profile-dropdown-wrapper">
            <button @click="openProfile = !openProfile" 
                    class="flex items-center gap-3 px-3 py-1.5 rounded-xl hover:bg-slate-800/60 transition group text-left"
                    title="Menu Pengaturan Profile">
                <!-- Text Info (Hidden on small screens) -->
                <div class="hidden md:block text-right">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-wider">PIC ACCESS</p>
                    <p class="text-xs font-bold text-white max-w-[140px] truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'User Account', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <!-- Avatar Circle -->
                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center border border-slate-700 relative shrink-0 overflow-hidden">
                    <?php if (!empty($_SESSION['profile_photo'])): ?>
                        <img src="<?= htmlspecialchars($_SESSION['profile_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fa-solid <?= $avatar_icon ?> text-xs group-hover:scale-110 transition-transform"></i>
                    <?php endif; ?>
                    <span class="absolute bottom-0 right-0 w-2 h-2 bg-green-500 rounded-full z-10 border border-slate-800"></span>
                </div>
                <!-- Chevron -->
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-500 group-hover:text-white transition-colors"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="openProfile" 
                 x-cloak
                 @click.away="openProfile = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                 class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 py-2.5 z-[999] origin-top-right text-slate-800">
                
                <!-- User Info Card -->
                <div class="px-5 py-3 border-b border-slate-50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-slate-105 text-slate-700 flex items-center justify-center font-bold overflow-hidden">
                        <?php if (!empty($_SESSION['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($_SESSION['profile_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-solid <?= $avatar_icon ?> text-xs"></i>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-black text-slate-850 text-xs uppercase tracking-tight truncate">
                            <?= htmlspecialchars($_SESSION['username'] ?? 'User Account', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider border <?= $role_color ?>">
                            <?= htmlspecialchars($_SESSION['role'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <!-- Dropdown Options -->
                <div class="p-1.5 space-y-0.5">
                    <a href="profile.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-slate-605 hover:text-slate-900 hover:bg-slate-50 transition text-xs font-bold">
                        <i class="fa-solid fa-user-gear text-slate-400 text-sm w-5"></i> Pengaturan Akun
                    </a>
                    
                    <a href="<?= $dashboard_link ?>" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-slate-605 hover:text-slate-900 hover:bg-slate-50 transition text-xs font-bold">
                        <i class="fa-solid fa-gauge-high text-slate-400 text-sm w-5"></i> Dashboard Utama
                    </a>
                </div>

                <!-- Divider -->
                <div class="border-t border-slate-50 my-1"></div>

                <!-- Logout Button -->
                <div class="p-1.5">
                    <a href="logout.php" onclick="confirmLogout(event, 'logout.php')" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-red-650 hover:text-red-700 hover:bg-red-50/70 transition text-xs font-black uppercase tracking-wider">
                        <i class="fa-solid fa-right-from-bracket text-red-450 text-sm w-5"></i> Keluar
                    </a>
                </div>

            </div>
        </div>

    </div>
</header>

<!-- GLOBAL DASHBOARD CARD AESTHETICS (Minimalist & Elegant) -->
<style>
    /* Subtle hover lift for dashboard cards */
    .bg-white.rounded-3xl, 
    .bg-white.rounded-2xl {
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .bg-white.rounded-3xl:hover, 
    .bg-white.rounded-2xl:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
    }
</style>


