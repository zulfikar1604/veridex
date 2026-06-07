<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Veridex | Verified. Ranked. Trusted.</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
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
    </style>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar) return;
            if (window.innerWidth >= 768) {
                sidebar.classList.toggle('sidebar-collapsed');
            } else {
                sidebar.classList.toggle('sidebar-open');
                if (overlay) overlay.classList.toggle('overlay-open');
            }
        }
    </script>
</head>