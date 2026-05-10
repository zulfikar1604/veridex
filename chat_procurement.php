<?php 
session_start(); 
$active = 'chat'; 
include 'header_meta.php'; 
?>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'sidebar_procurement.php'; ?>
    <main class="flex-1 overflow-y-auto p-8">
        <h2 class="text-xl font-bold text-slate-800 uppercase tracking-widest mb-8">Messaging System</h2>
        
        <div class="bg-white rounded-3xl border shadow-xl flex flex-col h-[600px] max-w-4xl mx-auto overflow-hidden">
            <div class="p-4 border-b bg-slate-50 font-bold flex justify-between items-center text-slate-700">
                <span>Diskusi Teknis: PO-2023-088</span>
                <span class="text-[10px] bg-green-100 text-green-700 px-2 py-1 rounded">Online</span>
            </div>
            <div class="flex-1 p-6 overflow-y-auto space-y-4 text-sm bg-gray-50/50">
                <div class="flex flex-col items-start">
                    <span class="text-[10px] text-gray-400 mb-1 ml-2">Tim Procurement</span>
                    <div class="bg-slate-800 text-white p-4 rounded-2xl rounded-tl-none max-w-[80%] shadow-sm">
                        Mohon klarifikasi mengenai garansi panel LCD?
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-[10px] text-gray-400 mb-1 mr-2">Vendor (PT. Solusi Digital)</span>
                    <div class="bg-red-600 text-white p-4 rounded-2xl rounded-tr-none max-w-[80%] shadow-md">
                        Garansi panel berlaku 3 tahun On-Site service Pak.
                    </div>
                </div>
            </div>
            <div class="p-4 border-t bg-white flex gap-2">
                <input type="text" placeholder="Tulis klarifikasi penawaran..." class="flex-1 border p-4 rounded-xl outline-red-500 bg-slate-50">
                <button class="bg-slate-900 text-white p-4 rounded-xl shadow-lg hover:bg-red-600 transition w-14">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </main>
</body>