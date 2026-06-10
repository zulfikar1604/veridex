<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | Veridex' : 'Veridex | Verified. Ranked. Trusted.' ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    animation: {
                        'fade-in-up': 'fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(15px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    <script>
        // FOUC Prevention & Dark Mode Manager
        function initTheme() {
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        initTheme();

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const darkIcon = document.getElementById('theme-toggle-dark-icon');
            const lightIcon = document.getElementById('theme-toggle-light-icon');
            if(darkIcon && lightIcon) {
                if (document.documentElement.classList.contains('dark')) {
                    darkIcon.classList.add('hidden');
                    lightIcon.classList.remove('hidden');
                } else {
                    darkIcon.classList.remove('hidden');
                    lightIcon.classList.add('hidden');
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- GLOBAL UI OVERRIDES FOR FORMS & TABLES -->
    <style type="text/tailwindcss">
        @layer components {
            /* Sleek Inputs */
            input:not([type="checkbox"]):not([type="radio"]), select, textarea {
                @apply w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-red-500/20 focus:border-red-500 dark:focus:ring-red-500/20 dark:focus:border-red-500 transition-all outline-none text-slate-800 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500;
            }
            
            /* Data Tables Upgrade */
            table {
                @apply w-full text-left border-collapse;
            }
            thead th {
                @apply bg-slate-100 dark:bg-slate-800/80 text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest p-4 sticky top-0 z-10 backdrop-blur-md;
            }
            tbody tr {
                @apply hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors border-b border-slate-100 dark:border-slate-800/80;
            }
            tbody td {
                @apply p-4 text-sm text-slate-700 dark:text-slate-300;
            }
        }
    </style>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Alpine.js cloak */
        [x-cloak] { display: none !important; }

        /* ===== SIDEBAR SYSTEM ===== */
        #main-sidebar {
            transition: width 0.3s ease, min-width 0.3s ease, transform 0.3s ease;
            will-change: transform;
        }

        /* DESKTOP default: sidebar visible */
        @media (min-width: 768px) {
            #main-sidebar {
                position: relative !important;
                transform: translateX(0) !important;
                width: 280px;
                min-width: 280px;
                overflow: hidden;
            }
            /* When collapsed on desktop */
            #main-sidebar.sidebar-collapsed {
                width: 0 !important;
                min-width: 0 !important;
            }
            #sidebar-overlay {
                display: none !important;
            }
        }

        /* MOBILE default: sidebar hidden off-canvas */
        @media (max-width: 767px) {
            #main-sidebar {
                position: fixed !important;
                top: 0; left: 0; bottom: 0;
                z-index: 50;
                width: 280px !important;
                min-width: 280px !important;
                transform: translateX(-100%);
            }
            #main-sidebar.sidebar-open {
                transform: translateX(0);
            }
            #sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(15,23,42,0.5);
                z-index: 40;
            }
            #sidebar-overlay.overlay-open {
                display: block;
            }
        }

        /* Hamburger button style */
        .sidebar-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, transform 0.1s;
            flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }
        .sidebar-toggle-btn:active {
            transform: scale(0.92);
        }

        /* Sidebar inner close button */
        .sidebar-close-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255,255,255,0.08);
            color: #94a3b8;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            flex-shrink: 0;
            border: none;
        }
        .sidebar-close-btn:hover {
            background: rgba(239,68,68,0.2);
            color: #fca5a5;
        }

        /* Prevent horizontal layout overflow stretching */
        main.flex-1 {
            min-width: 0 !important;
        }

        /* Disable hover lift for specific layout elements */
        .no-hover-lift:hover {
            transform: none !important;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1) !important;
        }
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            } else {
                sidebar.classList.toggle('sidebar-open');
                if (overlay) overlay.classList.toggle('overlay-open');
            }
        }
    </script>
</head>