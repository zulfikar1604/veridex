<?php
session_start();
require 'database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Vendor') {
    header("Location: login.php");
    exit;
}

$active = 'po';
$vendor_id = $_SESSION['vendor_id'] ?? 0;

// PO menunggu konfirmasi vendor
$query_pending = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id AND p.status = 'Approved'
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

// Riwayat PO (sudah dikonfirmasi ke atas)
$query_history = mysqli_query($conn, "
    SELECT p.*, i.nama_barang, i.sku 
    FROM purchase_orders p
    JOIN inventory i ON p.inventory_id = i.id
    WHERE p.vendor_id = $vendor_id AND p.status NOT IN ('Pending Manager', 'Approved', 'Rejected')
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

include 'header_meta.php';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex flex-col h-screen overflow-hidden">

    <?php
    $page_title = "Purchase Orders";
    $page_subtitle = "Pesanan Masuk dari Veridex";
    include 'navbar.php';
?>

    <div class="flex-1 flex overflow-hidden">
        <?php include 'sidebar_vendor.php'; ?>

        <main class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6" x-data="{
            openInvoiceModal(id, number) {
                this.$dispatch('open-invoice-modal', { id: id, number: number });
            }
        }">
            <!-- PO Menunggu Konfirmasi -->
            <?php if(mysqli_num_rows($query_pending) > 0): ?>
            <div>
                <h3 class="text-xs font-black text-red-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-bell"></i> PO Menunggu Konfirmasi Anda
                </h3>
                <div class="space-y-3">
                    <?php while($po = mysqli_fetch_assoc($query_pending)): ?>
                    <div class="bg-white dark:bg-[#1e293b] p-6 rounded-2xl border-2 border-red-200 shadow-sm hover:shadow-md transition-all" 
                         x-data="{ confirming: false }">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="font-black text-slate-800 dark:text-white text-lg"><?= htmlspecialchars($po['po_number']) ?></span>
                                    <span class="bg-yellow-100 text-yellow-700 text-[9px] px-2 py-0.5 rounded-full font-black uppercase">Menunggu Konfirmasi</span>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400 dark:text-slate-500 font-medium">
                                    <i class="fa-solid fa-box mr-1"></i> <?= htmlspecialchars($po['nama_barang']) ?>
                                    <span class="text-slate-300 mx-2">|</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-200">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></span>
                                </p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-bold">
                                    <i class="fa-regular fa-calendar mr-1"></i> <?= date('d M Y H:i', strtotime($po['created_at'])) ?>
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 px-4 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-colors">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <button @click="confirming = true" x-show="!confirming"
                                    class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-green-600 transition-colors shadow-lg">
                                    <i class="fa-solid fa-check mr-2"></i> Terima & Konfirmasi
                                </button>
                                <div x-show="confirming" x-cloak class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 dark:text-slate-500 font-bold">Yakin?</span>
                                    <button @click="
                                        let fd = new FormData();
                                        fd.append('action', 'confirm_po');
                                        fd.append('po_id', <?= $po['id'] ?>);
                                        fetch('process_vendor_po.php', { method: 'POST', body: fd })
                                        .then(r => r.json())
                                        .then(d => { alert(d.message); if(d.success) location.reload(); })
                                    " class="bg-green-600 text-white px-4 py-2 rounded-lg text-xs font-black hover:bg-green-700 transition">
                                        Ya, Konfirmasi
                                    </button>
                                    <button @click="confirming = false" class="bg-slate-200 text-slate-600 dark:text-slate-300 px-4 py-2 rounded-lg text-xs font-black hover:bg-slate-300 transition">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Riwayat PO -->
            <div>
                <h3 class="text-xs font-black text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat PO
                </h3>
                <?php if(mysqli_num_rows($query_history) > 0): ?>
                <div class="bg-white dark:bg-[#1e293b] rounded-2xl border shadow-sm overflow-hidden">
                    <div class="overflow-x-auto w-full">
<table class="w-full text-sm">
                        <thead>
                            <tr class="text-left bg-slate-900 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                <th class="p-4">No PO</th>
                                <th class="p-4">Barang</th>
                                <th class="p-4 text-center">Nilai</th>
                                <th class="p-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php while($po = mysqli_fetch_assoc($query_history)): 
                                $status_colors = [
                                    'Confirmed by Vendor' => 'bg-blue-50 text-blue-600 border-blue-200',
                                    'Shipped' => 'bg-purple-50 text-purple-600 border-purple-200',
                                    'Goods Received' => 'bg-green-50 text-green-600 border-green-200',
                                    'Invoice Submitted' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                    'Payment Approved' => 'bg-amber-50 text-amber-600 border-amber-200',
                                    'Payment Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                ];
                                $color = $status_colors[$po['status']] ?? 'bg-slate-100 text-slate-500 dark:text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-700';
                            ?>
                            <tr class="hover:bg-slate-50 dark:bg-slate-900 transition-colors">
                                <td class="p-4 font-bold text-slate-700 dark:text-slate-200"><?= htmlspecialchars($po['po_number']) ?></td>
                                <td class="p-4 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($po['nama_barang']) ?></td>
                                <td class="p-4 text-center font-bold text-slate-700 dark:text-slate-200">Rp <?= number_format($po['total_harga'], 0, ',', '.') ?></td>
                                <td class="p-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <span class="px-3 py-1 border rounded-lg text-[9px] font-black uppercase <?= $color ?>"><?= $po['status'] ?></span>
                                        <?php if($po['status'] === 'Goods Received'): ?>
                                            <button @click="openInvoiceModal(<?= $po['id'] ?>, '<?= htmlspecialchars($po['po_number']) ?>')" class="bg-indigo-600 text-white px-3 py-1 rounded-lg text-[10px] font-black uppercase hover:bg-indigo-700 transition">
                                                <i class="fa-solid fa-upload"></i> Upload Invoice
                                            </button>
                                        <?php endif; ?>
                                        <a href="print_po.php?id=<?= $po['id'] ?>" target="_blank" class="text-slate-400 dark:text-slate-500 hover:text-red-500 transition"><i class="fa-solid fa-print"></i></a>
                                    </div>
                                </td>
                            </tr>

                            <?php endwhile; ?>
                        </tbody>
                    </table>
</div>
                </div>
                <?php else: ?>
                <div class="bg-white dark:bg-[#1e293b] p-12 rounded-2xl border shadow-sm text-center">
                    <i class="fa-solid fa-folder-open text-5xl text-slate-200 mb-3 block"></i>
                    <p class="text-slate-400 dark:text-slate-500 font-bold">Belum ada riwayat PO.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Modal Upload Invoice -->
        <div x-data="{ 
            showInvoiceModal: false, 
            poId: 0, 
            poNumber: '', 
            invoiceNumber: '', 
            submitting: false,
            openInvoiceModal(id, number) {
                this.poId = id;
                this.poNumber = number;
                this.invoiceNumber = '';
                this.showInvoiceModal = true;
            },
            submitInvoice(e) {
                this.submitting = true;
                let fd = new FormData(e.target);
                fd.append('po_id', this.poId);
                fetch('process_vendor_invoice.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    if (d.success) location.reload();
                    else this.submitting = false;
                })
                .catch(err => {
                    alert('Gagal mengupload invoice');
                    this.submitting = false;
                });
            }
        }" @open-invoice-modal.window="openInvoiceModal($event.detail.id, $event.detail.number)">
            
            <div x-show="showInvoiceModal" x-cloak class="fixed inset-0 z-50 flex justify-center items-center bg-slate-900/50 backdrop-blur-sm p-4">
                <div @click.away="if(!submitting) showInvoiceModal = false" class="bg-white dark:bg-[#1e293b] w-full max-w-md rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="font-black text-slate-800 dark:text-white uppercase tracking-widest text-sm"><i class="fa-solid fa-file-invoice-dollar text-indigo-500 mr-2"></i> Upload E-Invoice</h3>
                        <button @click="if(!submitting) showInvoiceModal = false" class="text-slate-400 hover:text-red-500 transition"><i class="fa-solid fa-xmark text-xl"></i></button>
                    </div>
                    <form @submit.prevent="submitInvoice" class="p-6 space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Untuk PO Number</label>
                            <input type="text" x-model="poNumber" disabled class="w-full bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 p-3 rounded-xl font-bold text-sm text-slate-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Nomor Invoice Anda</label>
                            <input type="text" name="invoice_number" x-model="invoiceNumber" required placeholder="INV-2026/001" class="w-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0f172a] p-3 rounded-xl font-bold text-sm focus:border-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">File E-Invoice (PDF/JPG/PNG max 5MB)</label>
                            <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0f172a] p-2.5 rounded-xl text-sm focus:border-indigo-500 outline-none text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="submit" :disabled="submitting" class="w-full bg-indigo-600 text-white rounded-xl py-3.5 font-black uppercase tracking-widest text-xs hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/20 disabled:opacity-50 flex items-center justify-center gap-2">
                                <span x-show="!submitting"><i class="fa-solid fa-cloud-arrow-up"></i> Upload & Submit Invoice</span>
                                <span x-show="submitting"><i class="fa-solid fa-spinner fa-spin"></i> Mengupload...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>