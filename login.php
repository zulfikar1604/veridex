<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Manager Procurement') {
        // Khusus Manager, langsung ke halaman Kinerja Vendor
        header("Location: kinerja_vendor.php");
        exit;
    } elseif ($_SESSION['role'] === 'Procurement') {
        header("Location: dashboard_procurement.php");
        exit;
    } elseif ($_SESSION['role'] === 'Gudang') {
        header("Location: dashboard_gudang.php");
        exit;
    } elseif ($_SESSION['role'] === 'Finance') {
        header("Location: dashboard_finance.php");
        exit;
    } elseif ($_SESSION['role'] === 'Vendor') {
        header("Location: dashboard_vendor.php");
        exit;
    } else {
        session_destroy();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal | Veridex</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-900 text-white min-h-screen flex items-center justify-center relative overflow-hidden font-sans selection:bg-red-500/30">

    <!-- Abstract Background & Blobs -->
    <div class="absolute top-[-20%] left-[-10%] w-[70vw] h-[70vw] bg-red-600/10 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-pulse"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[60vw] h-[60vw] bg-rose-900/20 rounded-full mix-blend-screen filter blur-[120px] opacity-60"></div>
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+CjxwYXRoIGQ9Ik0wIDBoNDB2NDBIMHoiIGZpbGw9Im5vbmUiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPgo8L3N2Zz4=')] opacity-50 z-0"></div>

    <div class="w-full max-w-[440px] px-6 z-10 relative">

        <!-- Glassmorphism Card -->
        <div class="bg-white/[0.03] backdrop-blur-xl border border-white/10 p-10 sm:p-12 rounded-[2.5rem] shadow-[0_8px_32px_0_rgba(0,0,0,0.4)] relative">
            
            <!-- Floating Brand Icon -->
            <div class="absolute -top-12 left-1/2 -translate-x-1/2">
                <div class="w-24 h-24 bg-gradient-to-br from-red-600 to-red-900 rounded-[1.5rem] shadow-[0_15px_30px_rgba(220,38,38,0.4)] flex items-center justify-center transform -rotate-6 hover:rotate-0 transition-all duration-300 border border-red-500/30">
                    <i class="fa-solid fa-fingerprint text-4xl text-white"></i>
                </div>
            </div>

            <!-- Card Content -->
            <div class="pt-10 text-center mb-10">
                <h1 class="text-3xl font-black tracking-tight text-white mb-2">VERIDEX</h1>
                <p class="text-[10px] font-bold tracking-[0.2em] text-red-400 uppercase">Secure Enterprise Portal</p>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-300 px-4 py-3 mb-6 rounded-2xl text-sm font-medium flex items-center shadow-inner">
                    <i class="fa-solid fa-shield-cat mr-3 text-red-500 text-lg"></i> <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="process_login.php" method="POST" class="space-y-5">
                
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-widest pl-1">ID Pengguna</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-red-400 text-slate-500">
                            <i class="fa-solid fa-user-astronaut"></i>
                        </div>
                        <input type="text" name="username" required autocomplete="off"
                               class="w-full pl-11 py-3.5 bg-slate-800/50 border border-slate-700/50 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500/50 transition-all placeholder-slate-600 shadow-inner"
                               placeholder="Masukkan ID Anda">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-widest pl-1">Kata Sandi</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-red-400 text-slate-500">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <input type="password" name="password" required 
                               class="w-full pl-11 py-3.5 bg-slate-800/50 border border-slate-700/50 rounded-2xl text-white focus:outline-none focus:ring-2 focus:ring-red-500/50 focus:border-red-500/50 transition-all placeholder-slate-600 shadow-inner"
                               placeholder="Masukkan sandi">
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-4 rounded-2xl transition-all shadow-[0_8px_20px_rgba(220,38,38,0.3)] hover:shadow-[0_12px_25px_rgba(220,38,38,0.4)] flex items-center justify-center transform hover:-translate-y-0.5 border border-red-500/50">
                        Otorisasi Akses <i class="fa-solid fa-bolt ml-3"></i>
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center">
                <a href="register_vendor.php" class="text-xs font-medium text-slate-400 hover:text-white transition-colors flex items-center justify-center">
                    <i class="fa-solid fa-arrow-up-right-from-square mr-2 opacity-50"></i> Pendaftaran Vendor Baru
                </a>
            </div>

        </div>

        <!-- Footer -->
        <div class="mt-12 text-center">
            <p class="text-[10px] font-bold text-slate-600 tracking-[0.2em] uppercase">
                &copy; <?= date('Y') ?> Veridex Platform.
            </p>
        </div>
    </div>

</body>
</html>