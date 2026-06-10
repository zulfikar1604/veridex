<?php
session_start();
$dashboard_link = "login.php";
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Manager Procurement') $dashboard_link = "dashboard_manager.php";
    elseif ($_SESSION['role'] === 'Procurement') $dashboard_link = "dashboard_procurement.php";
    elseif ($_SESSION['role'] === 'Gudang') $dashboard_link = "dashboard_gudang.php";
    elseif ($_SESSION['role'] === 'Finance') $dashboard_link = "dashboard_finance.php";
    elseif ($_SESSION['role'] === 'Vendor') $dashboard_link = "dashboard_vendor.php";
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Veridex adalah platform manajemen procurement terintegrasi untuk optimasi ROP, Safety Stock, evaluasi SPH, dan otomatisasi 3-Way Matching.">
    <title>Veridex | Enterprise Procurement Intelligence</title>
    
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
                        'fade-in-up': 'fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards',
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
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #050505;
        }
        ::-webkit-scrollbar-thumb {
            background: #1f1f1f;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #333;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-header {
            background: rgba(5, 5, 5, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-card {
            background: linear-gradient(145deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 51, 102, 0.3);
            box-shadow: 0 12px 40px 0 rgba(255, 51, 102, 0.1);
        }

        .text-gradient {
            background: linear-gradient(to right, #ffffff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .text-gradient-brand {
            background: linear-gradient(to right, #ff3366, #ff8da1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at center, black 40%, transparent 80%);
        }

        /* Reveal on scroll */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="antialiased selection:bg-brand-500 selection:text-white">

    <!-- Glowing Background Effects -->
    <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-brand-600/20 rounded-full mix-blend-screen filter blur-[100px] animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-[35%] h-[35%] bg-indigo-600/20 rounded-full mix-blend-screen filter blur-[120px] animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-[50%] h-[50%] bg-purple-600/15 rounded-full mix-blend-screen filter blur-[150px] animate-blob animation-delay-4000"></div>
        <div class="absolute inset-0 bg-grid-pattern z-0 opacity-50"></div>
    </div>

    <!-- HEADER -->
    <header class="fixed top-0 inset-x-0 z-50 glass-header transition-all duration-300">
        <nav class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <!-- Brand Logo -->
            <a href="#" class="flex items-center gap-3 group relative">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/30 group-hover:shadow-brand-500/50 transition-all duration-300">
                    <i class="fa-solid fa-layer-group text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white">VERIDEX</span>
            </a>

            <!-- Navigation -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#about" class="text-sm font-medium text-gray-400 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-brand-500 after:transition-all hover:after:w-full">Tentang Kami</a>
                <a href="#features" class="text-sm font-medium text-gray-400 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-brand-500 after:transition-all hover:after:w-full">Fitur</a>
                <a href="#simulator" class="text-sm font-medium text-gray-400 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-brand-500 after:transition-all hover:after:w-full">Simulator ROP</a>
                <a href="#p2p" class="text-sm font-medium text-gray-400 hover:text-white transition-colors relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-0 after:h-0.5 after:bg-brand-500 after:transition-all hover:after:w-full">Edukasi P2P</a>
            </div>

            <!-- Login CTA -->
            <a href="<?= $dashboard_link ?>" class="relative group overflow-hidden rounded-full p-[1px]">
                <span class="absolute inset-0 bg-gradient-to-r from-brand-500 to-purple-600 rounded-full opacity-70 group-hover:opacity-100 transition-opacity duration-300"></span>
                <div class="relative flex items-center gap-2 bg-dark-900 px-6 py-2.5 rounded-full transition-all duration-300 group-hover:bg-opacity-0">
                    <span class="text-sm font-semibold text-white">Akses Platform</span>
                    <i class="fa-solid fa-arrow-right text-white text-xs transition-transform group-hover:translate-x-1"></i>
                </div>
            </a>
        </nav>
    </header>

    <main class="relative z-10">
        
        <!-- HERO SECTION -->
        <section class="min-h-screen flex items-center pt-24 pb-20 relative">
            <div class="max-w-7xl mx-auto px-6 w-full grid lg:grid-cols-2 gap-16 items-center">
                
                <div class="space-y-8 animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 text-xs font-medium text-gray-300">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-500 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                        </span>
                        Sistem Pengadaan Terintegrasi Next-Gen
                    </div>
                    
                    <h1 class="text-5xl lg:text-7xl font-bold tracking-tight leading-[1.1]">
                        Ubah Pengadaan Menjadi <br>
                        <span class="text-gradient-brand">Keunggulan Bisnis.</span>
                    </h1>
                    
                    <p class="text-lg text-gray-400 max-w-xl leading-relaxed font-light">
                        Veridex menyatukan manajemen persediaan, evaluasi vendor AI, dan otomasi 3-Way Matching dalam satu ekosistem digital yang tak tertandingi.
                    </p>
                    
                    <div class="flex flex-wrap gap-4 pt-4">
                        <a href="#simulator" class="flex items-center gap-3 bg-white dark:bg-[#1e293b] text-black px-8 py-4 rounded-full font-semibold hover:bg-gray-100 transition-all duration-300 hover:scale-105 hover:shadow-[0_0_30px_rgba(255,255,255,0.3)]">
                            <i class="fa-solid fa-calculator"></i> Simulasi ROP
                        </a>
                        <a href="#features" class="flex items-center gap-3 glass px-8 py-4 rounded-full font-semibold text-white hover:bg-white dark:bg-[#1e293b]/10 transition-all duration-300">
                            Pelajari Fitur <i class="fa-solid fa-chevron-down text-xs"></i>
                        </a>
                    </div>

                    <div class="pt-8 flex items-center gap-6 border-t border-white/5">
                        <div class="flex -space-x-3">
                            <img class="w-10 h-10 rounded-full border-2 border-dark-900 object-cover" src="https://i.pravatar.cc/100?img=3" alt="User">
                            <img class="w-10 h-10 rounded-full border-2 border-dark-900 object-cover" src="https://i.pravatar.cc/100?img=4" alt="User">
                            <img class="w-10 h-10 rounded-full border-2 border-dark-900 object-cover" src="https://i.pravatar.cc/100?img=5" alt="User">
                            <div class="w-10 h-10 rounded-full border-2 border-dark-900 bg-dark-700 flex items-center justify-center text-xs font-bold text-gray-300">+50</div>
                        </div>
                        <p class="text-sm text-gray-400">Dipercaya oleh <strong class="text-white">50+</strong> Enterprise di Indonesia</p>
                    </div>
                </div>

                <!-- Abstract UI Representation -->
                <div class="relative lg:h-[600px] flex items-center justify-center animate-fade-in-up" style="animation-delay: 0.2s;">
                    <div class="absolute inset-0 bg-gradient-to-tr from-brand-500/20 to-purple-600/20 rounded-[40px] blur-3xl"></div>
                    
                    <div class="glass-card w-full h-[500px] rounded-[30px] p-6 relative overflow-hidden flex flex-col gap-6">
                        <!-- Top Bar Mock -->
                        <div class="flex justify-between items-center pb-4 border-b border-white/5">
                            <div class="flex gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <div class="glass px-4 py-1.5 rounded-full text-[10px] font-semibold text-gray-400 tracking-widest uppercase">Veridex Console</div>
                        </div>

                        <!-- Content Mock -->
                        <div class="flex-1 space-y-4">
                            <!-- Alert Card -->
                            <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-center justify-between transform transition hover:scale-[1.02]">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center text-red-400">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-white">Stok Kritis: SKU-9281</h4>
                                        <p class="text-xs text-red-400/80">Stok di bawah Reorder Point</p>
                                    </div>
                                </div>
                                <button class="px-3 py-1.5 bg-red-500 text-white text-[10px] font-bold rounded-lg shadow-[0_0_15px_rgba(239,68,68,0.5)]">Buat RFQ</button>
                            </div>

                            <!-- Stat Cards Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="glass-card rounded-2xl p-5 border border-white/5">
                                    <p class="text-xs text-gray-400 mb-2">Total PO Aktif</p>
                                    <div class="flex items-end justify-between">
                                        <h3 class="text-3xl font-bold text-white">124</h3>
                                        <span class="text-xs text-green-400 flex items-center gap-1"><i class="fa-solid fa-arrow-up"></i> 12%</span>
                                    </div>
                                </div>
                                <div class="glass-card rounded-2xl p-5 border border-white/5">
                                    <p class="text-xs text-gray-400 mb-2">Efisiensi Biaya</p>
                                    <div class="flex items-end justify-between">
                                        <h3 class="text-3xl font-bold text-white">18.5%</h3>
                                        <span class="text-xs text-green-400 flex items-center gap-1"><i class="fa-solid fa-arrow-up"></i> 4%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor Score Mock -->
                            <div class="glass-card rounded-2xl p-5 border border-white/5 space-y-3">
                                <div class="flex justify-between items-center">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Top Vendor Matching</h4>
                                    <span class="text-xs text-brand-500 font-medium">AI Evaluated</span>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-200">PT Sinar Logam</span>
                                            <span class="text-green-400 font-semibold">98%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-dark-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-400 rounded-full w-[98%]"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-gray-200">CV Baja Utama</span>
                                            <span class="text-yellow-400 font-semibold">82%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-dark-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-yellow-500 to-amber-400 rounded-full w-[82%]"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Floating Elements -->
                    <div class="absolute -right-6 top-20 glass-card p-4 rounded-xl border border-white/10 shadow-2xl flex items-center gap-3 animate-blob">
                        <div class="w-8 h-8 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400">3-Way Matching</p>
                            <p class="text-sm font-bold text-white">Verified</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- VALUE PROPOSITION / ABOUT -->
        <section id="about" class="py-24 relative z-10 border-t border-white/5">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-3xl mx-auto mb-20 reveal">
                    <h2 class="text-brand-500 font-semibold tracking-widest text-sm uppercase mb-4">Filosofi Kami</h2>
                    <h3 class="text-3xl md:text-5xl font-bold mb-6">Membangun Standar Baru <br><span class="text-gray-500">dalam Pengadaan Digital.</span></h3>
                    <p class="text-gray-400 text-lg leading-relaxed">Kami percaya bahwa pengadaan yang luar biasa bukan hanya soal harga terendah, melainkan tentang transparansi, akurasi data, dan keandalan sistem operasi.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Pillar 1 -->
                    <div class="glass-card p-10 rounded-[32px] reveal group">
                        <div class="w-16 h-16 rounded-2xl bg-dark-700 border border-white/10 flex items-center justify-center mb-8 group-hover:bg-brand-500/20 group-hover:text-brand-500 transition-colors">
                            <i class="fa-solid fa-shield-check text-2xl text-gray-300 group-hover:text-brand-500 transition-colors"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-white mb-4">Verified Trust</h4>
                        <p class="text-gray-400 leading-relaxed">Kurasi vendor berlapis memastikan Anda hanya bermitra dengan entitas tervalidasi yang memenuhi standar kepatuhan hukum dan kapabilitas operasional.</p>
                    </div>

                    <!-- Pillar 2 -->
                    <div class="glass-card p-10 rounded-[32px] reveal group" style="transition-delay: 100ms;">
                        <div class="w-16 h-16 rounded-2xl bg-dark-700 border border-white/10 flex items-center justify-center mb-8 group-hover:bg-purple-500/20 transition-colors">
                            <i class="fa-solid fa-chart-pie text-2xl text-gray-300 group-hover:text-purple-400 transition-colors"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-white mb-4">Data-Driven Rank</h4>
                        <p class="text-gray-400 leading-relaxed">Algoritma mengevaluasi performa vendor secara real-time berdasarkan akurasi waktu pengiriman, kualitas, dan daya saing harga untuk setiap kuotasi.</p>
                    </div>

                    <!-- Pillar 3 -->
                    <div class="glass-card p-10 rounded-[32px] reveal group" style="transition-delay: 200ms;">
                        <div class="w-16 h-16 rounded-2xl bg-dark-700 border border-white/10 flex items-center justify-center mb-8 group-hover:bg-blue-500/20 transition-colors">
                            <i class="fa-solid fa-lock text-2xl text-gray-300 group-hover:text-blue-400 transition-colors"></i>
                        </div>
                        <h4 class="text-2xl font-bold text-white mb-4">Flawless Matching</h4>
                        <p class="text-gray-400 leading-relaxed">Sistem 3-Way Matching mengeliminasi penipuan dan human-error dengan memvalidasi kesesuaian antara PO, Dokumen Penerimaan, dan Invoice.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES SECTION -->
        <section id="features" class="py-24 relative z-10 border-t border-white/5 bg-dark-800/50">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-20 reveal">
                    <div class="max-w-2xl">
                        <h2 class="text-brand-500 font-semibold tracking-widest text-sm uppercase mb-4">Fitur Platform</h2>
                        <h3 class="text-3xl md:text-5xl font-bold">Orkestrasi Penuh <br><span class="text-gray-500">Rantai Pasok Anda.</span></h3>
                    </div>
                    <p class="text-gray-400 text-lg max-w-md">Solusi menyeluruh dari manajemen gudang hingga kontrol finansial, dirancang untuk Enterprise modern.</p>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Feat 1 -->
                    <div class="glass-card rounded-[32px] p-8 md:p-12 reveal flex flex-col md:flex-row gap-8 items-center group">
                        <div class="flex-1 space-y-4">
                            <div class="w-12 h-12 rounded-xl bg-brand-500/10 text-brand-500 flex items-center justify-center text-xl mb-6">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-white">Intelligent Inventory</h4>
                            <p class="text-gray-400">Pemantauan stok real-time, kalkulasi Reorder Point (ROP) otomatis, dan deteksi dini barang kritis untuk mencegah operasional terhenti.</p>
                        </div>
                        <div class="w-full md:w-48 h-48 rounded-2xl bg-dark-700 border border-white/5 relative overflow-hidden group-hover:border-brand-500/30 transition-colors flex items-center justify-center">
                            <!-- Minimalist graph visualization -->
                            <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-brand-500/20 to-transparent"></div>
                            <svg class="w-full h-24 stroke-brand-500 fill-none" viewBox="0 0 100 50" preserveAspectRatio="none">
                                <path d="M0,40 Q10,30 20,40 T40,20 T60,35 T80,10 T100,25" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Feat 2 -->
                    <div class="glass-card rounded-[32px] p-8 md:p-12 reveal flex flex-col md:flex-row gap-8 items-center group" style="transition-delay: 100ms;">
                        <div class="flex-1 space-y-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-xl mb-6">
                                <i class="fa-solid fa-robot"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-white">AI Bidding Evaluation</h4>
                            <p class="text-gray-400">Menganalisis Surat Penawaran Harga (SPH) dari puluhan vendor dalam hitungan detik dan merekomendasikan opsi paling optimal secara objektif.</p>
                        </div>
                        <div class="w-full md:w-48 h-48 rounded-2xl bg-dark-700 border border-white/5 flex flex-col gap-2 p-4 justify-center group-hover:border-purple-500/30 transition-colors">
                            <div class="h-8 w-full bg-dark-600 rounded-lg overflow-hidden flex">
                                <div class="w-[85%] h-full bg-purple-500/80"></div>
                            </div>
                            <div class="h-8 w-full bg-dark-600 rounded-lg overflow-hidden flex">
                                <div class="w-[60%] h-full bg-purple-500/50"></div>
                            </div>
                            <div class="h-8 w-full bg-dark-600 rounded-lg overflow-hidden flex">
                                <div class="w-[45%] h-full bg-purple-500/30"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Feat 3 -->
                    <div class="glass-card rounded-[32px] p-8 md:p-12 reveal flex flex-col md:flex-row gap-8 items-center group">
                        <div class="flex-1 space-y-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl mb-6">
                                <i class="fa-solid fa-barcode"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-white">Warehouse Scanning</h4>
                            <p class="text-gray-400">Integrasi pemindai barcode untuk penerimaan barang (Inbound) yang cepat dan bebas kesalahan pencatatan manual.</p>
                        </div>
                        <div class="w-full md:w-48 h-48 rounded-2xl bg-dark-700 border border-white/5 flex items-center justify-center group-hover:border-blue-500/30 transition-colors relative overflow-hidden">
                            <i class="fa-solid fa-qrcode text-6xl text-dark-500"></i>
                            <div class="absolute top-0 left-0 w-full h-1 bg-blue-400 shadow-[0_0_20px_rgba(96,165,250,1)] animate-[pulse_2s_ease-in-out_infinite]"></div>
                        </div>
                    </div>

                    <!-- Feat 4 -->
                    <div class="glass-card rounded-[32px] p-8 md:p-12 reveal flex flex-col md:flex-row gap-8 items-center group" style="transition-delay: 100ms;">
                        <div class="flex-1 space-y-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-6">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-white">Automated Finance AP</h4>
                            <p class="text-gray-400">Mencegah kebocoran anggaran dengan 3-Way Matching otomatis antara Purchase Order, Delivery Order, dan Invoice sebelum pembayaran.</p>
                        </div>
                        <div class="w-full md:w-48 h-48 rounded-2xl bg-dark-700 border border-white/5 flex items-center justify-center gap-3 p-4 group-hover:border-emerald-500/30 transition-colors">
                            <div class="w-10 h-14 bg-dark-600 rounded flex flex-col p-1 gap-1"><div class="w-full h-2 bg-emerald-500/20 rounded"></div><div class="w-1/2 h-2 bg-emerald-500/20 rounded"></div></div>
                            <i class="fa-solid fa-link text-emerald-500/50"></i>
                            <div class="w-10 h-14 bg-dark-600 rounded flex flex-col p-1 gap-1"><div class="w-full h-2 bg-emerald-500/40 rounded"></div><div class="w-1/2 h-2 bg-emerald-500/40 rounded"></div></div>
                            <i class="fa-solid fa-link text-emerald-500/50"></i>
                            <div class="w-10 h-14 bg-dark-600 rounded flex flex-col p-1 gap-1"><div class="w-full h-2 bg-emerald-500/80 rounded"></div><div class="w-1/2 h-2 bg-emerald-500/80 rounded"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- SIMULATOR SECTION -->
        <section id="simulator" class="py-24 relative z-10 border-t border-white/5">
            <div class="max-w-6xl mx-auto px-6">
                <div class="glass-card rounded-[40px] p-8 md:p-16 relative overflow-hidden reveal">
                    <!-- Decorative glowing sphere -->
                    <div class="absolute -right-32 -top-32 w-96 h-96 bg-brand-500/20 rounded-full blur-[100px]"></div>
                    
                    <div class="grid lg:grid-cols-2 gap-16 relative z-10">
                        <div class="space-y-6">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-white dark:bg-[#1e293b]/5 border border-white/10 text-xs font-semibold text-gray-300">
                                <i class="fa-solid fa-calculator text-brand-500"></i> Interactive Tool
                            </div>
                            <h2 class="text-4xl font-bold text-white leading-tight">Simulasikan <br>Kebutuhan ROP Anda</h2>
                            <p class="text-gray-400 text-lg leading-relaxed">
                                Reorder Point (ROP) mencegah bisnis Anda dari kehabisan stok. Masukkan data operasional harian untuk melihat bagaimana algoritma menentukan titik pemesanan ulang yang ideal.
                            </p>
                            
                            <div class="space-y-5 pt-4">
                                <div class="glass p-4 rounded-2xl flex gap-4 items-start">
                                    <div class="w-8 h-8 rounded-full bg-dark-700 flex items-center justify-center shrink-0 mt-1">1</div>
                                    <div>
                                        <h5 class="text-white font-semibold mb-1">Demand (Permintaan)</h5>
                                        <p class="text-sm text-gray-400">Rata-rata konsumsi item per hari.</p>
                                    </div>
                                </div>
                                <div class="glass p-4 rounded-2xl flex gap-4 items-start">
                                    <div class="w-8 h-8 rounded-full bg-dark-700 flex items-center justify-center shrink-0 mt-1">2</div>
                                    <div>
                                        <h5 class="text-white font-semibold mb-1">Lead Time (Waktu Tunggu)</h5>
                                        <p class="text-sm text-gray-400">Durasi dari PO dibuat hingga barang tiba di gudang.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calculator Interface -->
                        <div class="bg-dark-900 border border-white/10 rounded-[32px] p-8 shadow-2xl relative">
                            <div class="space-y-6">
                                <!-- Inputs -->
                                <div>
                                    <label class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                                        <span>Permintaan Harian (Unit)</span>
                                        <span id="val-demand" class="text-white">10</span>
                                    </label>
                                    <input type="range" id="calc-demand" min="1" max="100" value="10" oninput="updateSimulator()" class="w-full h-2 bg-dark-700 rounded-lg appearance-none cursor-pointer accent-brand-500">
                                </div>

                                <div>
                                    <label class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                                        <span>Lead Time (Hari)</span>
                                        <span id="val-leadtime" class="text-white">5</span>
                                    </label>
                                    <input type="range" id="calc-leadtime" min="1" max="30" value="5" oninput="updateSimulator()" class="w-full h-2 bg-dark-700 rounded-lg appearance-none cursor-pointer accent-brand-500">
                                </div>

                                <div>
                                    <label class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
                                        <span>Safety Stock (Unit)</span>
                                        <span id="val-safetystock" class="text-white">15</span>
                                    </label>
                                    <input type="range" id="calc-safetystock" min="0" max="200" value="15" oninput="updateSimulator()" class="w-full h-2 bg-dark-700 rounded-lg appearance-none cursor-pointer accent-brand-500">
                                </div>

                                <div class="h-px w-full bg-white dark:bg-[#1e293b]/10 my-6"></div>

                                <!-- Result -->
                                <div class="flex items-end justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Reorder Point</p>
                                        <div class="flex items-baseline gap-2">
                                            <span id="calc-result" class="text-5xl font-black text-brand-500 font-mono tracking-tighter">65</span>
                                            <span class="text-gray-500 font-medium">Unit</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div id="calc-status" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-xs font-bold text-green-400">
                                            <span class="w-2 h-2 rounded-full bg-green-400 animate-ping"></span> Stok Aman
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- P2P CYCLE ROADMAP -->
        <section id="p2p" class="py-24 relative z-10 border-t border-white/5">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <div class="max-w-3xl mx-auto mb-20 reveal">
                    <h2 class="text-brand-500 font-semibold tracking-widest text-sm uppercase mb-4">Edukasi P2P</h2>
                    <h3 class="text-3xl md:text-5xl font-bold mb-6">Siklus Procure-to-Pay</h3>
                    <p class="text-gray-400 text-lg">Alur kerja digital mulus yang mengeliminasi bottleneck birokrasi, mendigitalkan kertas kerja, dan mempercepat pengambilan keputusan.</p>
                </div>

                <!-- Roadmap Nodes -->
                <div class="relative max-w-5xl mx-auto">
                    <!-- Connecting Line -->
                    <div class="hidden lg:block absolute top-1/2 left-0 w-full h-0.5 bg-dark-700 -translate-y-1/2 z-0">
                        <div class="h-full bg-gradient-to-r from-brand-500 via-purple-500 to-blue-500 w-full opacity-50"></div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 lg:gap-6 relative z-10">
                        <!-- Step 1 -->
                        <div class="glass-card p-8 rounded-3xl reveal text-left relative group">
                            <div class="absolute -inset-0.5 bg-gradient-to-br from-brand-500 to-purple-600 rounded-3xl opacity-0 group-hover:opacity-100 transition duration-500 blur-sm pointer-events-none"></div>
                            <div class="relative bg-dark-800 p-8 rounded-3xl h-full border border-white/5">
                                <span class="text-5xl font-black text-dark-600 absolute top-4 right-4">01</span>
                                <div class="w-12 h-12 rounded-xl bg-dark-700 border border-white/10 flex items-center justify-center text-brand-500 mb-6">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </div>
                                <h4 class="text-xl font-bold text-white mb-3">Planning</h4>
                                <p class="text-sm text-gray-400 leading-relaxed">Sistem mendeteksi stok mencapai ROP dan otomatis memberikan notifikasi untuk pembuatan Request for Quotation (RFQ).</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="glass-card p-8 rounded-3xl reveal text-left relative group" style="transition-delay: 100ms;">
                            <div class="absolute -inset-0.5 bg-gradient-to-br from-purple-500 to-blue-600 rounded-3xl opacity-0 group-hover:opacity-100 transition duration-500 blur-sm pointer-events-none"></div>
                            <div class="relative bg-dark-800 p-8 rounded-3xl h-full border border-white/5">
                                <span class="text-5xl font-black text-dark-600 absolute top-4 right-4">02</span>
                                <div class="w-12 h-12 rounded-xl bg-dark-700 border border-white/10 flex items-center justify-center text-purple-400 mb-6">
                                    <i class="fa-solid fa-handshake"></i>
                                </div>
                                <h4 class="text-xl font-bold text-white mb-3">Sourcing</h4>
                                <p class="text-sm text-gray-400 leading-relaxed">Vendor mengajukan Surat Penawaran Harga (SPH). AI merekomendasikan pilihan terbaik berdasarkan harga, kualitas, & SLA.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="glass-card p-8 rounded-3xl reveal text-left relative group" style="transition-delay: 200ms;">
                            <div class="absolute -inset-0.5 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-3xl opacity-0 group-hover:opacity-100 transition duration-500 blur-sm pointer-events-none"></div>
                            <div class="relative bg-dark-800 p-8 rounded-3xl h-full border border-white/5">
                                <span class="text-5xl font-black text-dark-600 absolute top-4 right-4">03</span>
                                <div class="w-12 h-12 rounded-xl bg-dark-700 border border-white/10 flex items-center justify-center text-blue-400 mb-6">
                                    <i class="fa-solid fa-file-signature"></i>
                                </div>
                                <h4 class="text-xl font-bold text-white mb-3">Procurement</h4>
                                <p class="text-sm text-gray-400 leading-relaxed">Persetujuan digital multi-tier dari manajemen untuk penerbitan Purchase Order (PO) resmi ke pihak vendor.</p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="glass-card p-8 rounded-3xl reveal text-left relative group" style="transition-delay: 300ms;">
                            <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500 to-green-500 rounded-3xl opacity-0 group-hover:opacity-100 transition duration-500 blur-sm pointer-events-none"></div>
                            <div class="relative bg-dark-800 p-8 rounded-3xl h-full border border-white/5">
                                <span class="text-5xl font-black text-dark-600 absolute top-4 right-4">04</span>
                                <div class="w-12 h-12 rounded-xl bg-dark-700 border border-white/10 flex items-center justify-center text-emerald-400 mb-6">
                                    <i class="fa-solid fa-money-check-dollar"></i>
                                </div>
                                <h4 class="text-xl font-bold text-white mb-3">Payment</h4>
                                <p class="text-sm text-gray-400 leading-relaxed">Penerimaan barang fisik (Inbound) dan pencocokan 3-Way Matching otomatis sebelum persetujuan invoice Finance.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA SECTION -->
        <section class="py-24 relative z-10">
            <div class="max-w-5xl mx-auto px-6">
                <div class="glass-card rounded-[40px] p-12 md:p-20 text-center relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-brand-500/10 to-purple-600/10 group-hover:opacity-100 transition-opacity opacity-50"></div>
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50"></div>
                    
                    <div class="relative z-10 max-w-2xl mx-auto space-y-8">
                        <h2 class="text-4xl md:text-5xl font-bold text-white">Siap Mengoptimasi <br>Pengadaan Anda?</h2>
                        <p class="text-gray-400 text-lg">Bergabung dengan ekosistem Veridex dan rasakan kontrol, transparansi, dan efisiensi level enterprise.</p>
                        
                        <div class="pt-4">
                            <a href="<?= $dashboard_link ?>" class="inline-flex items-center gap-3 px-8 py-4 bg-white dark:bg-[#1e293b] text-dark-900 rounded-full font-bold hover:bg-gray-200 transition-all transform hover:scale-105 shadow-[0_0_40px_rgba(255,255,255,0.2)]">
                                Akses Dashboard Sekarang <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="relative z-10 border-t border-white/5 bg-dark-900 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <div>
                    <a href="#" class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center">
                            <i class="fa-solid fa-layer-group text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold tracking-tight text-white">VERIDEX</span>
                    </a>
                    <p class="text-gray-400 max-w-xs text-sm leading-relaxed">
                        Enterprise Procurement Intelligence Platform. Merestrukturisasi kompleksitas rantai pasok menjadi kesederhanaan operasional.
                    </p>
                </div>
                <div class="flex md:justify-end items-center gap-6">
                    <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center text-gray-400 hover:text-white hover:bg-white dark:bg-[#1e293b]/10 transition-colors">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center text-gray-400 hover:text-white hover:bg-white dark:bg-[#1e293b]/10 transition-colors">
                        <i class="fa-brands fa-twitter"></i>
                    </a>
                    <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center text-gray-400 hover:text-white hover:bg-white dark:bg-[#1e293b]/10 transition-colors">
                        <i class="fa-solid fa-envelope"></i>
                    </a>
                </div>
            </div>
            
            <div class="border-t border-white/5 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
                <p>&copy; 2026 Veridex Platform. Hak Cipta Dilindungi.</p>
                <div class="flex gap-6">
                    <a href="#" class="hover:text-gray-300 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-gray-300 transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts for Interactivity & Animations -->
    <script>
        // Reveal on Scroll
        function reveal() {
            var reveals = document.querySelectorAll(".reveal");
            for (var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var elementTop = reveals[i].getBoundingClientRect().top;
                var elementVisible = 100;
                if (elementTop < windowHeight - elementVisible) {
                    reveals[i].classList.add("active");
                }
            }
        }
        window.addEventListener("scroll", reveal);
        reveal(); // Trigger on load

        // Simulator Logic
        function updateSimulator() {
            const demand = parseFloat(document.getElementById("calc-demand").value);
            const leadtime = parseFloat(document.getElementById("calc-leadtime").value);
            const safetystock = parseFloat(document.getElementById("calc-safetystock").value);
            
            // Update labels
            document.getElementById("val-demand").innerText = demand;
            document.getElementById("val-leadtime").innerText = leadtime;
            document.getElementById("val-safetystock").innerText = safetystock;

            // Calculate
            const rop = (demand * leadtime) + safetystock;
            
            // Animate number count up (simple version)
            document.getElementById("calc-result").innerText = rop;

            // Update status
            const statusEl = document.getElementById("calc-status");
            if (rop < 40) {
                statusEl.className = "inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-xs font-bold text-green-400";
                statusEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-400 animate-ping"></span> Stok Aman';
            } else if (rop < 80) {
                statusEl.className = "inline-flex items-center gap-2 px-3 py-1 rounded-full bg-yellow-500/10 border border-yellow-500/20 text-xs font-bold text-yellow-400";
                statusEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-yellow-400 animate-ping"></span> Stok Siaga';
            } else {
                statusEl.className = "inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-xs font-bold text-red-400";
                statusEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400 animate-ping"></span> Perlu Restock';
            }
        }
        
        // Initialize Simulator
        document.addEventListener('DOMContentLoaded', updateSimulator);
    </script>
</body>
</html>
