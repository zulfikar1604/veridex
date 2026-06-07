<?php
session_start();
// Jika sudah login, berikan shortcut ke dashboard yang sesuai
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Veridex adalah platform manajemen procurement terintegrasi untuk optimasi ROP (Reorder Point), Safety Stock, evaluasi SPH, dan otomatisasi 3-Way Matching.">
    <title>Veridex | Intelligent Procurement & Vendor Performance System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            scroll-behavior: smooth;
        }
        
        .glow-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
        }

        .glass-header {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .gradient-text {
            background: linear-gradient(135deg, #ef4444 0%, #fca5a5 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gradient-bg-dark {
            background: linear-gradient(180deg, #090f1a 0%, #0f172a 100%);
        }

        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .step-node {
            transition: all 0.3s ease;
        }
        .step-node:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Simulator Card Glass style */
        .simulator-glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden">

    <!-- HEADER / NAVIGATION -->
    <header class="fixed top-0 inset-x-0 z-50 glass-header text-white">
        <nav class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center" aria-label="Main Navigation">
            <!-- Brand Logo -->
            <a href="#" class="flex items-center gap-2 group">
                <span class="text-2xl font-black text-red-500 tracking-tighter">VERIDEX</span>
            </a>

            <!-- Menu Navigation -->
            <div class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-350">
                <a href="#about" class="hover:text-red-500 transition-colors">Tentang Kami</a>
                <a href="#p2p" class="hover:text-red-500 transition-colors">Edukasi P2P</a>
                <a href="#simulator" class="hover:text-red-500 transition-colors">Simulator ROP</a>
                <a href="#features" class="hover:text-red-500 transition-colors">Fitur Utama</a>
            </div>

            <!-- Login CTA -->
            <a href="<?= $dashboard_link ?>" 
               id="nav-login-btn"
               class="bg-gradient-to-r from-red-600 to-red-700 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider hover:from-red-500 hover:to-red-600 shadow-md shadow-red-500/20 hover:shadow-lg hover:shadow-red-500/30 transition-all active:scale-[0.98]">
                Masuk Sistem <i class="fa-solid fa-arrow-right-to-bracket ml-2"></i>
            </a>
        </nav>
    </header>

    <main>
        
        <!-- HERO SECTION -->
        <section class="relative min-h-[90vh] flex items-center pt-24 pb-16 gradient-bg-dark text-white overflow-hidden">
            <!-- Glow Orbs -->
            <div class="glow-orb w-[400px] h-[400px] bg-red-600 top-1/4 left-[-100px] opacity-15"></div>
            <div class="glow-orb w-[500px] h-[500px] bg-blue-600 bottom-1/4 right-[-100px] opacity-10"></div>
            
            <!-- Matrix dots bg -->
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:30px_30px] opacity-40 pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10 w-full">
                <!-- Left: Headline & Description -->
                <div class="lg:col-span-7 space-y-6">
                    <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-500/10 border border-red-500/20 text-red-400">
                        <i class="fa-solid fa-shield-halved mr-2"></i> Smart Procurement Platform
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight uppercase leading-none">
                        Verified.<br>Ranked.<br><span class="gradient-text">Trusted.</span>
                    </h1>
                    <p class="text-sm sm:text-base text-slate-400 font-medium leading-relaxed max-w-xl">
                        Veridex memodernisasi rantai pasok perusahaan melalui optimasi stok kritis (Safety Stock & ROP), analisis penawaran vendor terautomasi, serta verifikasi pembayaran aman dengan 3-Way Matching.
                    </p>
                    <div class="flex gap-4 pt-4 flex-wrap">
                        <a href="#simulator" class="px-6 py-4 bg-white text-slate-900 rounded-2xl text-xs font-black uppercase tracking-wider hover:bg-slate-100 transition shadow-lg shadow-white/5 active:scale-[0.98]">
                            Simulasi ROP <i class="fa-solid fa-calculator ml-2 text-slate-500"></i>
                        </a>
                        <a href="#p2p" class="px-6 py-4 bg-slate-800 text-slate-300 rounded-2xl text-xs font-black uppercase tracking-wider hover:text-white hover:bg-slate-700 transition border border-slate-700 active:scale-[0.98]">
                            Edukasi Procurement <i class="fa-solid fa-circle-info ml-2"></i>
                        </a>
                    </div>
                </div>

                <!-- Right: Visual Platform Mockup -->
                <div class="lg:col-span-5 relative">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-[2rem] p-6 border border-slate-700 shadow-2xl relative overflow-hidden group hover:scale-[1.02] transition-transform duration-500">
                        <!-- Card Header Mock -->
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            </div>
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Veridex Console</span>
                        </div>

                        <!-- Card UI Mock Elements -->
                        <div class="space-y-4">
                            <!-- Alert ROP Status -->
                            <div class="bg-red-500/10 border border-red-500/20 p-4 rounded-2xl flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-red-500/20 text-red-400 rounded-xl flex items-center justify-center"><i class="fa-solid fa-triangle-exclamation"></i></div>
                                    <div>
                                        <p class="text-[10px] font-black text-red-400 uppercase tracking-wider">Stok Kritis Terdeteksi</p>
                                        <p class="text-xs font-bold text-white">SKU-209: Kawat Tembaga (< ROP)</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[8px] font-black bg-red-650 text-white uppercase animate-pulse">Kritis</span>
                            </div>

                            <!-- Bid recommendation -->
                            <div class="bg-slate-800/80 border border-slate-700 p-4 rounded-2xl space-y-2.5">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Rekomendasi Penawaran SPH (AI)</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-white">1. PT Sinar Logam Utama</span>
                                    <span class="text-xs font-black text-emerald-400">Score: 94.8%</span>
                                </div>
                                <div class="w-full bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-500 h-full rounded-full" style="width: 95%"></div>
                                </div>
                            </div>

                            <!-- 3-way matching status -->
                            <div class="bg-slate-800/80 border border-slate-700 p-4 rounded-2xl flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center"><i class="fa-solid fa-file-shield"></i></div>
                                    <div>
                                        <p class="text-xs font-bold text-white">AP Matching Status</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">PO #00912 vs DO vs Invoice</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded bg-emerald-500/10 border border-emerald-500/20 text-[8px] font-black text-emerald-400 uppercase">Matched</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- COMPANY PROFILE SECTION -->
        <section id="about" class="py-20 max-w-7xl mx-auto px-6 space-y-12">
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <span class="text-red-500 text-xs font-black uppercase tracking-widest">Company Profile</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 uppercase">Solusi Intelligent Procurement Terpercaya</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">
                    Veridex dikembangkan dengan visi mewujudkan efisiensi rantai pasok korporat. Melalui teknologi otomasi, kami mempertemukan transparansi operasional dan akuntabilitas keuangan secara aman.
                </p>
            </div>

            <!-- Core Pillars -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Pillar 1 -->
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-4 feature-card">
                    <div class="w-12 h-12 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-clipboard-check"></i></div>
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Verified (Kepercayaan)</h3>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Setiap profil rekanan vendor melalui proses kurasi ketat untuk menjamin kepatuhan (compliance), kelayakan operasional, serta legalitas perusahaan sebelum bermitra.
                    </p>
                </div>
                <!-- Pillar 2 -->
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-4 feature-card">
                    <div class="w-12 h-12 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-ranking-star"></i></div>
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Ranked (Kinerja)</h3>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Sistem memberikan evaluasi performa vendor secara real-time berdasarkan akurasi waktu pengiriman barang, kualitas item inbound, serta daya saing harga penawaran.
                    </p>
                </div>
                <!-- Pillar 3 -->
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-4 feature-card">
                    <div class="w-12 h-12 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center text-xl shadow-inner"><i class="fa-solid fa-file-shield"></i></div>
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Trusted (Keamanan)</h3>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">
                        Proses validasi tiga arah (3-Way Matching) memastikan pelepasan dana tagihan finansial hanya terjadi pada item yang sesuai antara pesanan pembelian, tanda terima gudang, dan invoice penagihan.
                    </p>
                </div>
            </div>
        </section>


        <!-- INTERACTIVE EDUCATION: P2P CYCLE ROADMAP -->
        <section id="p2p" class="py-20 gradient-bg-dark text-white">
            <div class="max-w-7xl mx-auto px-6 space-y-12">
                <div class="text-center max-w-2xl mx-auto space-y-3">
                    <span class="text-red-500 text-xs font-black uppercase tracking-widest">Edukasi Rantai Pasok</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight uppercase">Siklus Procure-to-Pay (P2P)</h2>
                    <p class="text-slate-400 text-xs sm:text-sm font-medium leading-relaxed">
                        Pahami bagaimana proses pengadaan berjalan secara otomatis dan akuntabel di dalam ekosistem Veridex dari awal perencanaan stok hingga pelepasan pembayaran.
                    </p>
                </div>

                <!-- Step-by-Step roadmap -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Step 1 -->
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl space-y-4 step-node">
                        <div class="flex justify-between items-start">
                            <span class="text-3xl font-black text-slate-700/80">01</span>
                            <span class="px-2.5 py-1 bg-red-500/10 text-red-400 text-[8px] font-black uppercase tracking-widest rounded-lg border border-red-500/20">Planning</span>
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-wider">Perencanaan & ROP</h4>
                        <p class="text-[11px] text-slate-450 font-medium leading-relaxed">
                            Sistem secara real-time memonitor tingkat persediaan barang. Saat stok berada di bawah batas **Reorder Point (ROP)**, status item berubah menjadi Kritis untuk mencegah kehabisan stok (*out of stock*).
                        </p>
                    </div>
                    <!-- Step 2 -->
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl space-y-4 step-node">
                        <div class="flex justify-between items-start">
                            <span class="text-3xl font-black text-slate-700/80">02</span>
                            <span class="px-2.5 py-1 bg-amber-500/10 text-amber-400 text-[8px] font-black uppercase tracking-widest rounded-lg border border-amber-500/20">Quotation</span>
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-wider">Penawaran RFQ & SPH</h4>
                        <p class="text-[11px] text-slate-450 font-medium leading-relaxed">
                            Staf Procurement merilis **Request for Quotation (RFQ)**. Rekanan vendor menanggapi dengan mengunggah dokumen **Surat Penawaran Harga (SPH)** berisi harga bersaing dan kapasitas kirim.
                        </p>
                    </div>
                    <!-- Step 3 -->
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl space-y-4 step-node">
                        <div class="flex justify-between items-start">
                            <span class="text-3xl font-black text-slate-700/80">03</span>
                            <span class="px-2.5 py-1 bg-indigo-500/10 text-indigo-400 text-[8px] font-black uppercase tracking-widest rounded-lg border border-indigo-500/20">Approval</span>
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-wider">Persetujuan & Terbit PO</h4>
                        <p class="text-[11px] text-slate-450 font-medium leading-relaxed">
                            Manager Procurement mengevaluasi SPH dengan panduan skor AI, melakukan persetujuan (approval) secara digital, dan menerbitkan dokumen **Purchase Order (PO)** sebagai janji pembelian resmi.
                        </p>
                    </div>
                    <!-- Step 4 -->
                    <div class="bg-white/5 border border-white/10 p-6 rounded-3xl space-y-4 step-node">
                        <div class="flex justify-between items-start">
                            <span class="text-3xl font-black text-slate-700/80">04</span>
                            <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 text-[8px] font-black uppercase tracking-widest rounded-lg border border-emerald-500/20">Matching</span>
                        </div>
                        <h4 class="text-sm font-black uppercase tracking-wider">Inbound & 3-Way Matching</h4>
                        <p class="text-[11px] text-slate-450 font-medium leading-relaxed">
                            Gudang memverifikasi kiriman barang masuk (*Inbound*). Sistem Finance secara otomatis mencocokkan tiga dokumen utama: **PO asli**, **Surat Jalan Gudang**, dan **Invoice Penagihan** sebelum pembayaran dilepas.
                        </p>
                    </div>
                </div>

                <!-- Safety Stock / Safety Point Explanation -->
                <div class="bg-white/5 border border-white/10 p-6 md:p-8 rounded-[2rem] grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                    <div class="md:col-span-2 space-y-3">
                        <h4 class="text-lg font-black text-red-400 uppercase tracking-tight"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Mengapa Safety Stock Sangat Krusial?</h4>
                        <p class="text-xs text-slate-450 leading-relaxed font-medium">
                            **Safety Stock (Stok Pengaman)** adalah persediaan ekstra yang disimpan guna mengantisipasi fluktuasi permintaan yang mendadak atau keterlambatan pengiriman oleh vendor (*Lead Time Delay*). Tanpa stok pengaman, perusahaan berisiko mengalami kerugian operasional akibat henti produksi.
                        </p>
                        <p class="text-xs text-slate-450 leading-relaxed font-medium">
                            **Reorder Point (ROP)** adalah batas titik di mana pemesanan ulang harus segera dilakukan. ROP mengintegrasikan tingkat konsumsi harian dan waktu tunggu kirim, ditambah stok pengaman agar barang baru tiba sebelum stok pengaman tersentuh.
                        </p>
                    </div>
                    <div class="bg-white/5 border border-white/10 p-6 rounded-2xl space-y-3 text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rumus Standar ROP</p>
                        <div class="text-lg font-black bg-slate-800 p-4 rounded-xl border border-slate-700 font-mono tracking-wide text-white">
                            ROP = (d &times; L) + SS
                        </div>
                        <div class="text-[10px] text-slate-500 leading-tight">
                            <span class="text-slate-400 font-bold">d</span>: Permintaan Harian • <span class="text-slate-400 font-bold">L</span>: Lead Time (Hari) • <span class="text-slate-400 font-bold">SS</span>: Safety Stock
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- INTERACTIVE CALCULATOR SECTION -->
        <section id="simulator" class="py-20 bg-slate-100 relative">
            <div class="max-w-4xl mx-auto px-6 space-y-10">
                <div class="text-center space-y-3">
                    <span class="text-red-500 text-xs font-black uppercase tracking-widest">ROP Simulator</span>
                    <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 uppercase">Simulasi Reorder Point Interaktif</h2>
                    <p class="text-slate-500 text-xs sm:text-sm font-medium leading-relaxed max-w-xl mx-auto">
                        Cobalah masukkan data operasional Anda di bawah ini untuk melihat bagaimana sistem otomatisasi Veridex merekomendasikan titik pemesanan ulang secara cerdas.
                    </p>
                </div>

                <!-- Calculator Card -->
                <div class="simulator-glass p-6 md:p-10 rounded-[2.5rem] shadow-xl space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <!-- Input 1 -->
                        <div class="space-y-2">
                            <label for="calc-demand" class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">Permintaan Harian (Unit/Hari)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-black"><i class="fa-solid fa-boxes-stacked"></i></span>
                                <input type="number" 
                                       id="calc-demand" 
                                       value="10" 
                                       oninput="calculateROP()"
                                       class="w-full border border-slate-200 bg-white/70 pl-10 pr-4 py-3 rounded-xl font-bold text-slate-700 focus:outline-none focus:border-red-500 transition">
                            </div>
                        </div>

                        <!-- Input 2 -->
                        <div class="space-y-2">
                            <label for="calc-leadtime" class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">Lead Time Vendor (Hari)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-black"><i class="fa-regular fa-clock"></i></span>
                                <input type="number" 
                                       id="calc-leadtime" 
                                       value="5" 
                                       oninput="calculateROP()"
                                       class="w-full border border-slate-200 bg-white/70 pl-10 pr-4 py-3 rounded-xl font-bold text-slate-700 focus:outline-none focus:border-red-500 transition">
                            </div>
                        </div>

                        <!-- Input 3 -->
                        <div class="space-y-2">
                            <label for="calc-safetystock" class="block text-[11px] font-black text-slate-400 uppercase tracking-widest">Safety Stock (Unit)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-black"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="number" 
                                       id="calc-safetystock" 
                                       value="15" 
                                       oninput="calculateROP()"
                                       class="w-full border border-slate-200 bg-white/70 pl-10 pr-4 py-3 rounded-xl font-bold text-slate-700 focus:outline-none focus:border-red-500 transition">
                            </div>
                        </div>

                    </div>

                    <!-- Calculator Results Output (Interactive Visuals) -->
                    <div class="bg-slate-900 rounded-3xl p-6 md:p-8 text-white relative overflow-hidden flex flex-col md:flex-row justify-between items-center gap-6">
                        <!-- BG pattern deco -->
                        <div class="absolute -right-20 -bottom-20 w-44 h-44 bg-red-500 rounded-full blur-3xl opacity-25"></div>
                        
                        <div class="relative z-10 space-y-2 text-center md:text-left">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rekomendasi Titik ROP</span>
                            <h3 class="text-3xl font-black flex items-baseline justify-center md:justify-start gap-2">
                                <span id="calc-result" class="text-4xl text-red-500 font-mono">65</span> <span class="text-sm font-medium text-slate-400">Unit Terhitung</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 max-w-sm">
                                Lakukan pemesanan RFQ baru sesegera mungkin ketika stok fisik di gudang Anda menyusut hingga menyentuh angka batas di atas.
                            </p>
                        </div>

                        <!-- Status badge -->
                        <div class="shrink-0 relative z-10 text-center md:text-right space-y-2 bg-slate-800 border border-slate-700 p-5 rounded-2xl min-w-[180px]">
                            <span class="block text-[9px] font-black text-slate-400 uppercase tracking-widest">Status Simulasi</span>
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-500/10 border border-green-500/20 rounded-full text-[10px] font-black text-green-400 uppercase tracking-wider" id="calc-status">
                                <span class="w-2 h-2 rounded-full bg-green-400 animate-ping"></span> ROP Aman
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>


        <!-- PLATFORM FEATURES SECTION -->
        <section id="features" class="py-20 max-w-7xl mx-auto px-6 space-y-12">
            <div class="text-center max-w-2xl mx-auto space-y-3">
                <span class="text-red-500 text-xs font-black uppercase tracking-widest">Fitur Platform</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 uppercase">Ekosistem Pengadaan Digital Lengkap</h2>
                <p class="text-slate-500 text-sm font-medium leading-relaxed">
                    Setiap peran dalam perusahaan memiliki modul khusus untuk menjalankan tanggung jawab operasional masing-masing dengan alur kerja yang seragam.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- feature 1 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-3 feature-card">
                    <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-chart-line"></i></div>
                    <h4 class="font-black text-slate-850 uppercase text-sm">Dashboard ROP (Procurement)</h4>
                    <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                        Memantau status inventory secara otomatis, menyortir stok kritis, dan menerbitkan RFQ penawaran harga dengan praktis.
                    </p>
                </div>
                <!-- feature 2 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-3 feature-card">
                    <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-barcode"></i></div>
                    <h4 class="font-black text-slate-850 uppercase text-sm">Scan Barcode (Gudang)</h4>
                    <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                        Memproses penerimaan barang masuk (*Inbound*) dan pengeluaran barang keluar (*Outbound*) secara instan menggunakan kamera barcode scanner.
                    </p>
                </div>
                <!-- feature 3 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-3 feature-card">
                    <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-file-shield"></i></div>
                    <h4 class="font-black text-slate-850 uppercase text-sm">3-Way Matching (Finance)</h4>
                    <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                        Verifikasi keabsahan invoice tagihan secara otomatis dengan mencocokkan dokumen PO dan DO pengiriman barang di gudang.
                    </p>
                </div>
                <!-- feature 4 -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm space-y-3 feature-card">
                    <div class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-building-circle-check"></i></div>
                    <h4 class="font-black text-slate-850 uppercase text-sm">Bidding Area (Vendor)</h4>
                    <p class="text-[11px] text-slate-500 font-medium leading-relaxed">
                        Memungkinkan rekanan vendor melihat RFQ yang dibuka, mengajukan SPH penawaran harga, mengunggah invoice penagihan secara terpadu.
                    </p>
                </div>
            </div>
        </section>


        <!-- DYNAMIC CTA CALL TO ACTION -->
        <section class="py-16 bg-slate-900 text-white relative overflow-hidden">
            <!-- Glow orb bg -->
            <div class="glow-orb w-[350px] h-[350px] bg-red-500 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-20"></div>

            <div class="max-w-4xl mx-auto px-6 text-center space-y-6 relative z-10">
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight uppercase">Siap Mengoptimalkan Pengadaan Anda?</h2>
                <p class="text-slate-400 text-xs sm:text-sm font-medium leading-relaxed max-w-xl mx-auto">
                    Masuk ke portal login Veridex sekarang untuk menikmati manajemen procurement otomatis berbasis AI dan optimasi rantai pasokan yang andal.
                </p>
                <div class="pt-2">
                    <a href="<?= $dashboard_link ?>" class="inline-flex items-center px-8 py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-red-500/20 hover:shadow-xl hover:shadow-red-500/30 transition-all active:scale-[0.98]">
                        Masuk Dashboard Utama <i class="fa-solid fa-chevron-right ml-2"></i>
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-950 text-slate-500 py-12 border-t border-slate-900">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-center md:text-left space-y-1.5">
                <span class="text-lg font-black text-red-500">VERIDEX</span>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Intelligent Procurement Ecosystem</p>
            </div>

            <div class="text-center md:text-right space-y-2 text-xs">
                <p>&copy; 2026 Veridex. Hak Cipta Dilindungi Undang-Undang.</p>
                <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest">Verified • Ranked • Trusted</p>
            </div>
        </div>
    </footer>

    <!-- INTERACTIVE CALCULATOR JAVASCRIPT -->
    <script>
        function calculateROP() {
            const demandInput = document.getElementById("calc-demand");
            const leadtimeInput = document.getElementById("calc-leadtime");
            const safetystockInput = document.getElementById("calc-safetystock");
            
            const resultElement = document.getElementById("calc-result");
            const statusElement = document.getElementById("calc-status");
            
            if (!demandInput || !leadtimeInput || !safetystockInput || !resultElement || !statusElement) return;

            const demand = parseFloat(demandInput.value) || 0;
            const leadtime = parseFloat(leadtimeInput.value) || 0;
            const safetystock = parseFloat(safetystockInput.value) || 0;

            // ROP Formula: (d * L) + SS
            const rop = (demand * leadtime) + safetystock;
            
            resultElement.innerText = rop;

            // Dynamic status and visual update
            if (rop <= 0) {
                statusElement.className = "inline-flex items-center gap-2 px-3 py-1 bg-slate-500/10 border border-slate-500/20 rounded-full text-[10px] font-black text-slate-400 uppercase tracking-wider";
                statusElement.innerHTML = "No Data";
            } else if (rop < 40) {
                statusElement.className = "inline-flex items-center gap-2 px-3 py-1 bg-green-500/10 border border-green-500/20 rounded-full text-[10px] font-black text-green-400 uppercase tracking-wider";
                statusElement.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-400 animate-ping"></span> ROP Aman';
            } else if (rop >= 40 && rop < 80) {
                statusElement.className = "inline-flex items-center gap-2 px-3 py-1 bg-yellow-500/10 border border-yellow-500/20 rounded-full text-[10px] font-black text-yellow-400 uppercase tracking-wider";
                statusElement.innerHTML = '<span class="w-2 h-2 rounded-full bg-yellow-400 animate-ping"></span> ROP Siaga';
            } else {
                statusElement.className = "inline-flex items-center gap-2 px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-full text-[10px] font-black text-red-400 uppercase tracking-wider";
                statusElement.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-400 animate-ping"></span> ROP Kritis';
            }
        }

        // Initialize calculator once
        window.addEventListener("DOMContentLoaded", () => {
            calculateROP();
        });
    </script>
</body>
</html>
