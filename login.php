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
<body class="bg-gray-50 text-gray-900 min-h-screen grid grid-cols-1 md:grid-cols-2">

    <!-- Left Column: Corporate Image -->
    <div class="relative hidden md:flex flex-col justify-between bg-blue-900 overflow-hidden text-white p-12">
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop" alt="Corporate Office" class="w-full h-full object-cover opacity-30">
        </div>
        <div class="z-10 mt-8">
            <h1 class="text-4xl font-black tracking-tighter mb-2">VERIDEX</h1>
            <p class="text-sm font-bold tracking-widest uppercase text-blue-200">Verified. Ranked. Trusted.</p>
        </div>
        <div class="z-10 mb-8">
            <p class="text-lg font-light max-w-md leading-relaxed text-blue-100">
                Enterprise Procurement & Supply Chain Platform. Designed for efficiency, transparency, and strategic growth.
            </p>
        </div>
    </div>

    <!-- Right Column: Login Form -->
    <div class="flex flex-col justify-center items-center p-8 sm:p-16 lg:p-24 bg-white relative">
        <div class="w-full max-w-md">
            
            <div class="md:hidden text-center mb-10">
                <h1 class="text-4xl font-black tracking-tighter text-blue-900 mb-1">VERIDEX</h1>
                <p class="text-[10px] font-bold tracking-widest uppercase text-gray-500">Verified. Ranked. Trusted.</p>
            </div>

            <div class="mb-10">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome back</h2>
                <p class="text-gray-500">Please enter your credentials to access the portal.</p>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-r-md text-sm font-medium flex items-center shadow-sm">
                    <i class="fa-solid fa-circle-exclamation mr-3"></i> <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="process_login.php" method="POST" class="space-y-6">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Username / Employee ID</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <i class="fa-regular fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" required autocomplete="off"
                               class="w-full pl-11 py-3 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all shadow-sm"
                               placeholder="Enter your username">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">System Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" required 
                               class="w-full pl-11 py-3 border border-gray-300 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all shadow-sm"
                               placeholder="Enter your password">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3.5 rounded-lg transition-colors shadow-md flex items-center justify-center">
                        Sign In <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-8 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between text-sm">
                <a href="register_vendor.php" class="text-blue-600 hover:text-blue-800 font-semibold mb-4 sm:mb-0 transition-colors">
                    Register as New Vendor
                </a>
                <span class="text-gray-400 text-xs">&copy; 2026 Veridex</span>
            </div>

        </div>
    </div>

</body>
</html>