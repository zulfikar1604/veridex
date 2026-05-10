<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}
$active = 'po';
include 'header_meta.php';
?>
<body class="bg-slate-50 h-screen flex overflow-hidden">
    <?php include 'sidebar_vendor.php'; ?>
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">MANAJEMEN PO</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">V</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="bg-white p-8 rounded-3xl border shadow-sm">
                <table class="w-full text-sm">
                    <thead><tr class="text-left bg-slate-50"><th class="p-4 border-b uppercase text-[10px] font-black">No PO</th><th class="p-4 border-b uppercase text-[10px] font-black">Nilai Kontrak</th><th class="p-4 border-b text-right uppercase text-[10px] font-black">Aksi</th></tr></thead>
                    <tbody>
                        <tr class="border-b hover:bg-slate-50 transition">
                            <td class="p-4 font-bold text-slate-700">PO-VERIDEX-2023-911</td>
                            <td class="p-4 font-medium text-slate-600">Rp 4.500.000</td>
                            <td class="p-4 text-right">
                                <button class="bg-red-600 text-white px-4 py-2 rounded-lg font-black text-[10px] shadow-lg shadow-red-100 italic uppercase tracking-widest">TERIMA / KONFIRMASI PO</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>