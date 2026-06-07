<?php
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Manager Procurement') {
        // Khusus Manager, arahkan ke dashboard
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
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #ef4444; 
            --cyan: #3b82f6;
            --purple: #8b5cf6;
        }

        body {
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at 20% 20%, rgba(239,68,68,0.1), transparent 30%),
                radial-gradient(circle at 80% 15%, rgba(59,130,246,0.1), transparent 28%),
                radial-gradient(circle at 50% 80%, rgba(139,92,246,0.1), transparent 30%),
                linear-gradient(180deg, #050c16 0%, #0a1322 45%, #0b1220 100%);
            color: #ffffff;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Animated Background Grid */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -2;
            opacity: 0.35;
            pointer-events: none;
        }

        /* Glow blobs */
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.35;
            pointer-events: none;
            z-index: -1;
            animation: floatOrb 12s ease-in-out infinite alternate;
        }
        .orb-1 { width: 250px; height: 250px; background: rgba(239,68,68,0.3); top: 10%; left: 15%; }
        .orb-2 { width: 300px; height: 300px; background: rgba(59,130,246,0.2); top: 20%; right: 10%; animation-delay: 2s; }
        .orb-3 { width: 250px; height: 250px; background: rgba(139,92,246,0.2); bottom: 10%; left: 30%; animation-delay: 4s; }

        @keyframes floatOrb {
            0% { transform: translateY(0px) translateX(0px) scale(1); }
            100% { transform: translateY(-30px) translateX(20px) scale(1.05); }
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            border-radius: 28px;
        }

        /* Custom Input */
        .form-control {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff !important;
            transition: 0.25s ease;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(239,68,68,0.15);
            outline: none;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.35); }

        .btn-primary-grad {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            box-shadow: 0 12px 28px rgba(239,68,68,0.3);
            transition: 0.3s ease;
        }
        .btn-primary-grad:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(239,68,68,0.4);
        }

        .brand-gradient {
            background: linear-gradient(90deg, #f87171, #ef4444, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>

    <div class="glow-orb orb-1"></div>
    <div class="glow-orb orb-2"></div>
    <div class="glow-orb orb-3"></div>

    <div class="w-full max-w-sm px-4 z-10 py-8">
        
        <div class="mb-4 flex justify-start">
            <a href="index.php" class="inline-flex items-center gap-2 text-xs font-bold text-white/50 hover:text-white transition-all bg-white/5 border border-white/10 px-4 py-2 rounded-xl hover:bg-white/10 shadow-sm">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>

        <div class="text-center mb-6 transform transition-all duration-500 hover:scale-105">
            <h1 class="text-4xl font-black tracking-tighter mb-1">VERIDEX</h1>
            <p class="text-[10px] font-bold tracking-[0.3em] brand-gradient uppercase">Verified. Ranked. Trusted.</p>
        </div>

        <div class="glass-card p-6 sm:p-8 relative overflow-hidden">
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-200 p-4 mb-6 rounded-xl text-sm font-medium flex items-center shadow-lg">
                    <i class="fa-solid fa-triangle-exclamation mr-3 text-red-400"></i> <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-white/5 border border-white/10 rounded-2xl mb-3 shadow-inner">
                    <i class="fa-solid fa-shield-halved text-xl text-red-400"></i>
                </div>
                <h2 class="text-xl font-black text-white">Secure Login</h2>
                <p class="text-xs text-white/50 mt-1">Masukkan kredensial Anda</p>
            </div>

            <form action="process_login.php" method="POST" class="space-y-4">
                
                <div class="form-group">
                    <label class="block text-[10px] font-bold text-white/60 mb-2 uppercase tracking-widest">Username / ID Pegawai</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <i class="fa-solid fa-user text-white/40 text-xs"></i>
                        </div>
                        <input type="text" name="username" required autocomplete="off"
                               class="form-control w-full pl-10 py-2.5 rounded-xl text-sm font-medium" 
                               placeholder="Masukkan username...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="block text-[10px] font-bold text-white/60 mb-2 uppercase tracking-widest">Password Sistem</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <i class="fa-solid fa-lock text-white/40 text-xs"></i>
                        </div>
                        <input type="password" name="password" required 
                               class="form-control w-full pl-10 py-2.5 rounded-xl text-sm font-medium" 
                               placeholder="Masukkan password...">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-primary-grad w-full py-3.5 rounded-xl font-black text-white uppercase tracking-widest text-xs flex items-center justify-center">
                        Login Dashboard <i class="fa-solid fa-arrow-right-to-bracket ml-3"></i>
                    </button>
                </div>
            </form>

            <div class="pt-6 border-t border-white/10 mt-6 text-center">
                <a href="register_vendor.php" class="text-xs font-bold text-white/60 hover:text-white transition-all">
                    Daftar Vendor Baru / Info Platform
                </a>
            </div>

        </div>

        <div class="mt-8 text-center text-[10px] font-bold text-white/30 tracking-[0.2em] uppercase">
            &copy; 2026 Veridex Platform
        </div>
    </div>

</body>
</html>