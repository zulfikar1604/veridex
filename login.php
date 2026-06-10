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
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal | Veridex Enterprise</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            500: '#e11d48', // Rose-600
                            600: '#be123c', // Rose-700
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                        'blob': 'blob 10s infinite',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #020617; /* Slate-950 */
            color: #f8fafc;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .bg-grid {
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.6); /* Slate-900 */
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        .input-premium {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .input-premium:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: #e11d48;
            box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.15);
            outline: none;
            transform: translateY(-2px);
        }
        
        .input-premium::placeholder { color: rgba(255, 255, 255, 0.25); }

        .btn-premium {
            background: linear-gradient(135deg, #e11d48, #9f1239);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-premium::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(225, 29, 72, 0.3);
        }

        .btn-premium:hover::before {
            left: 100%;
        }

        .btn-premium:active {
            transform: translateY(1px);
        }
    </style>
</head>
<body class="antialiased selection:bg-brand-500 selection:text-white flex items-center justify-center">

    <!-- Ambient Background -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-0 left-0 w-full h-full bg-grid"></div>
        <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] bg-brand-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] bg-blue-600/10 rounded-full mix-blend-screen filter blur-[120px] animate-blob" style="animation-delay: 2s;"></div>
    </div>

    <!-- Main Card Container -->
    <div class="w-full max-w-5xl px-4 z-10 py-10 opacity-0 animate-fade-in-up">
        <div class="glass-panel rounded-[2.5rem] overflow-hidden flex flex-col md:flex-row shadow-2xl relative border-t border-l border-white/10 border-b border-r border-black/50">
            
            <!-- Highlight Accent Line -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-brand-500 to-transparent opacity-50"></div>

            <!-- Left: Visual/Branding -->
            <div class="md:w-5/12 p-10 md:p-14 flex flex-col justify-between relative overflow-hidden bg-slate-900/40 border-r border-white/5">
                <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-brand-500/10 rounded-full blur-[60px]"></div>
                
                <div class="relative z-10">
                    <a href="index.php" class="inline-flex items-center gap-3 group mb-10">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-500 to-rose-700 flex items-center justify-center shadow-lg shadow-brand-500/40 ring-1 ring-white/20">
                            <i class="fa-solid fa-layer-group text-white text-xl"></i>
                        </div>
                        <span class="text-3xl font-black tracking-tight text-white uppercase">VERIDEX</span>
                    </a>
                    
                    <h1 class="text-4xl md:text-5xl font-black text-white leading-[1.1] mb-6 tracking-tight">
                        Ekosistem <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-500 to-rose-300">Pengadaan.</span>
                    </h1>
                    
                    <p class="text-slate-400 text-sm font-medium leading-relaxed max-w-sm mb-8">
                        Platform Enterprise Resource Planning terdepan untuk manajemen rantai pasok yang efisien, transparan, dan terintegrasi penuh.
                    </p>
                    
                    <!-- Back Button -->
                    <a href="index.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 hover:text-white text-xs font-bold uppercase tracking-widest transition-all group">
                        <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Kembali ke Beranda
                    </a>
                </div>

                <div class="relative z-10 mt-12 pt-8 border-t border-white/10">
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-3">
                            <div class="w-8 h-8 rounded-full bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-[10px] text-white"><i class="fa-solid fa-building"></i></div>
                            <div class="w-8 h-8 rounded-full bg-slate-700 border-2 border-slate-900 flex items-center justify-center text-[10px] text-white"><i class="fa-solid fa-industry"></i></div>
                            <div class="w-8 h-8 rounded-full bg-slate-600 border-2 border-slate-900 flex items-center justify-center text-[10px] text-white"><i class="fa-solid fa-boxes-stacked"></i></div>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">+500 Mitra Terverifikasi</p>
                    </div>
                </div>
            </div>

            <!-- Right: Login Form -->
            <div class="md:w-7/12 p-10 md:p-16 relative flex flex-col justify-center bg-[#0a0f1c]/60">
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 mb-8 rounded-2xl text-sm font-medium flex items-start gap-3 shadow-lg animate-fade-in-up">
                        <i class="fa-solid fa-triangle-exclamation mt-1"></i>
                        <p class="leading-relaxed"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="mb-10">
                    <h3 class="text-2xl font-bold text-white mb-2">Selamat Datang Kembali</h3>
                    <p class="text-xs text-slate-400 uppercase tracking-widest font-bold">Autentikasi Akses Sistem</p>
                </div>

                <form action="process_login.php" method="POST" class="space-y-6">
                    
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Username / ID Pegawai</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-5 pointer-events-none transition-colors group-focus-within:text-brand-500 text-slate-500">
                                <i class="fa-regular fa-user text-sm"></i>
                            </div>
                            <input type="text" name="username" required autocomplete="off"
                                   class="input-premium w-full pl-12 pr-5 py-4 rounded-2xl text-sm font-bold placeholder-slate-600" 
                                   placeholder="Masukkan username Anda">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Password Keamanan</label>
                            <a href="#" class="text-[10px] font-bold text-brand-500 hover:text-brand-400 transition-colors">Lupa Password?</a>
                        </div>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-5 pointer-events-none transition-colors group-focus-within:text-brand-500 text-slate-500">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input type="password" name="password" required 
                                   class="input-premium w-full pl-12 pr-5 py-4 rounded-2xl text-sm font-bold placeholder-slate-600 tracking-widest" 
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-premium w-full py-4 rounded-2xl font-black text-white text-sm uppercase tracking-widest flex items-center justify-center gap-3">
                            Masuk Ke Sistem <i class="fa-solid fa-arrow-right text-white/70"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-10 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="register_vendor.php" class="text-xs font-bold text-slate-400 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-brand-500 after:transition-all hover:after:w-full">
                        Daftar Sebagai Mitra Vendor
                    </a>
                    <span class="text-[9px] font-black text-slate-600 tracking-[0.2em] uppercase">
                        V.2026 VERIDEX
                    </span>
                </div>

            </div>
            
        </div>
    </div>

</body>
</html>