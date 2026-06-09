<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Manager Procurement') {
        header("Location: dashboard_manager.php");
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
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            900: '#050505',
                            800: '#0a0a0a',
                            700: '#141414',
                            600: '#1f1f1f',
                            500: '#2d2d2d',
                        },
                        brand: {
                            500: '#ff3366',
                            600: '#e62e5c',
                        }
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                        'fade-in-up': 'fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #050505;
            color: #f3f4f6;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
        }

        .glass-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
        }

        .form-control {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff !important;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255,255,255,0.05);
            border-color: #ff3366;
            box-shadow: 0 0 0 4px rgba(255, 51, 102, 0.15);
            outline: none;
        }
        
        .form-control::placeholder { color: rgba(255,255,255,0.3); }

        .btn-brand {
            background: linear-gradient(135deg, #ff3366, #b31b3e);
            box-shadow: 0 10px 25px rgba(255, 51, 102, 0.3);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .btn-brand:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(255, 51, 102, 0.4);
        }

        .btn-brand:active {
            transform: translateY(1px);
        }

        .text-gradient-brand {
            background: linear-gradient(to right, #ff3366, #ff8da1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="antialiased selection:bg-brand-500 selection:text-white">

    <!-- Glowing Background Effects -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-brand-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob animation-delay-2000"></div>
        <div class="absolute top-[40%] right-[30%] w-[30%] h-[30%] bg-indigo-600/15 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-4000"></div>
        <div class="absolute inset-0 bg-grid-pattern z-0 opacity-60"></div>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-[900px] px-6 z-10 py-8 animate-fade-in-up">
        
        <div class="glass-card rounded-[32px] overflow-hidden flex flex-col md:flex-row shadow-2xl">
            
            <!-- Left Side: Branding / Welcome Area -->
            <div class="md:w-5/12 bg-dark-800/80 p-8 md:p-12 flex flex-col justify-between border-b md:border-b-0 md:border-r border-white/5 relative overflow-hidden">
                <!-- Subtle internal glow -->
                <div class="absolute -top-20 -left-20 w-64 h-64 bg-brand-500/20 rounded-full blur-[80px] pointer-events-none"></div>
                
                <div class="relative z-10">
                    <a href="index.php" class="flex items-center gap-3 group mb-8 inline-flex">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                            <i class="fa-solid fa-layer-group text-white text-lg"></i>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-white">VERIDEX</span>
                    </a>
                    
                    <h2 class="text-3xl font-bold text-white leading-tight mb-4 hidden md:block">
                        Akses <br>Ekosistem <br><span class="text-gradient-brand">Pengadaan.</span>
                    </h2>
                    <h2 class="text-2xl font-bold text-white leading-tight mb-2 md:hidden">
                        Akses Ekosistem <span class="text-gradient-brand">Pengadaan.</span>
                    </h2>
                    
                    <p class="text-sm text-gray-400 max-w-xs">
                        Masuk untuk mengelola operasional rantai pasok Anda dengan efisiensi dan transparansi tingkat lanjut.
                    </p>
                </div>

                <div class="relative z-10 mt-8 md:mt-12">
                    <a href="index.php" class="inline-flex items-center gap-2 text-xs font-semibold text-gray-400 hover:text-white transition-colors group">
                        <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Kembali ke Beranda
                    </a>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="md:w-7/12 p-8 md:p-12 relative bg-dark-900/60 backdrop-blur-md flex flex-col justify-center">
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 mb-6 rounded-2xl text-sm font-medium flex items-start gap-3 shadow-lg">
                        <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                        <p class="leading-relaxed"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="mb-8">
                    <h3 class="text-xl font-bold text-white mb-1">Secure Login</h3>
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">Silakan masukkan kredensial Anda</p>
                </div>

                <form action="process_login.php" method="POST" class="space-y-5">
                    
                    <div class="space-y-2">
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest">Username / ID Pegawai</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-brand-500 text-gray-500">
                                <i class="fa-solid fa-user text-sm"></i>
                            </div>
                            <input type="text" name="username" required autocomplete="off"
                                   class="form-control w-full pl-11 pr-4 py-3.5 rounded-xl text-sm font-medium" 
                                   placeholder="Contoh: ven_sinarlogam">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest">Password Sistem</label>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-brand-500 text-gray-500">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input type="password" name="password" required 
                                   class="form-control w-full pl-11 pr-4 py-3.5 rounded-xl text-sm font-medium" 
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="btn-brand w-full py-4 rounded-xl font-bold text-white text-sm flex items-center justify-center gap-2 group">
                            Login ke Dashboard <i class="fa-solid fa-arrow-right-to-bracket text-white/70 group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-white/10 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="register_vendor.php" class="text-xs font-semibold text-gray-400 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-brand-500 after:transition-all hover:after:w-full">
                        Daftar sebagai Vendor Baru
                    </a>
                    <span class="text-[10px] font-bold text-gray-600 tracking-[0.1em] uppercase">
                        &copy; 2026 Veridex
                    </span>
                </div>

            </div>
            
        </div>
    </div>

</body>
</html>