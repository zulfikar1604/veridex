<?php 
session_start(); 
require 'database.php';

// Proteksi Role: Hanya Procurement dan Manager Procurement
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Manager Procurement', 'Procurement'])) {
    header("Location: login.php");
    exit;
}

$active = 'chat'; 
include 'header_meta.php'; 

// Cek apakah ada PO yang dipilih dari URL (misal: chat_procurement.php?po=PO-123)
$selected_po = isset($_GET['po']) ? $_GET['po'] : '';

// Ambil daftar PO dan nama vendornya untuk ditampilkan di Sidebar Kiri
$query_list_po = mysqli_query($conn, "
    SELECT p.po_number, p.status, v.nama_perusahaan 
    FROM purchase_orders p
    JOIN vendors v ON p.vendor_id = v.id
    ORDER BY p.created_at DESC
") or handle_db_error($conn);

?>
<body class="bg-slate-50 flex h-screen overflow-hidden">
    
    <?php include 'sidebar_procurement.php'; ?>
    
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        
        <div class="p-6 pb-0">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex justify-between items-center">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">MESSAGING SYSTEM</h2>
                <div class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center font-black shadow-md">
                    <i class="fa-solid fa-comments"></i>
                </div>
            </div>
        </div>

        <div class="flex-1 p-6 flex flex-col overflow-hidden">
            <div class="bg-white rounded-3xl border shadow-xl flex h-full max-w-6xl mx-auto w-full overflow-hidden">
                
                <div class="w-1/3 border-r border-slate-100 flex flex-col bg-slate-50/50">
                    <div class="p-5 border-b border-slate-100 bg-white">
                        <h3 class="font-black text-slate-800 uppercase tracking-widest text-xs"><i class="fa-solid fa-list mr-2 text-red-500"></i>Daftar Percakapan</h3>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto">
                        <?php if (mysqli_num_rows($query_list_po) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($query_list_po)): 
                                // Tandai mana yang sedang aktif
                                $is_active = ($selected_po == $row['po_number']) ? 'bg-red-50 border-l-4 border-red-500' : 'hover:bg-white border-l-4 border-transparent';
                            ?>
                                <a href="chat_procurement.php?po=<?= $row['po_number'] ?>" class="block p-4 border-b border-slate-100 transition <?= $is_active ?>">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-black text-slate-800 text-sm"><?= htmlspecialchars($row['po_number']) ?></span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest bg-slate-200 px-2 py-0.5 rounded"><?= $row['status'] ?></span>
                                    </div>
                                    <p class="text-xs font-bold text-red-600 uppercase truncate">
                                        <i class="fa-solid fa-building mr-1 text-slate-400"></i> <?= htmlspecialchars($row['nama_perusahaan']) ?>
                                    </p>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-6 text-center text-slate-400 text-xs italic font-medium">
                                Belum ada Purchase Order (PO) yang dibuat.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="w-2/3 flex flex-col bg-white">
                    <?php if ($selected_po == ''): ?>
                        <div class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-slate-50/30">
                            <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center text-slate-300 mb-4">
                                <i class="fa-regular fa-comments text-4xl"></i>
                            </div>
                            <h3 class="text-lg font-black text-slate-700 uppercase tracking-widest">Pilih Obrolan</h3>
                            <p class="text-slate-400 text-sm mt-2 font-medium">Silakan pilih nomor PO dari daftar di sebelah kiri untuk memulai diskusi teknis dengan Vendor.</p>
                        </div>
                    <?php else: ?>
                        <div class="p-5 border-b border-slate-100 bg-white font-bold flex justify-between items-center text-slate-700 z-10 shadow-sm">
                            <div>
                                <i class="fa-solid fa-file-signature text-red-500 mr-2"></i> 
                                <span>Diskusi PO: <span id="current-po" class="text-slate-900 font-black"><?= htmlspecialchars($selected_po) ?></span></span>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest bg-green-100 text-green-700 px-3 py-1.5 rounded-full flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Live
                            </span>
                        </div>
                        
                        <div id="chat-box" class="flex-1 p-6 overflow-y-auto space-y-6 text-sm bg-slate-50/50 relative">
                            <div id="loading-chat" class="absolute inset-0 flex items-center justify-center bg-slate-50/80 z-10 backdrop-blur-sm">
                                <div class="text-slate-500 font-bold animate-pulse flex flex-col items-center">
                                    <i class="fa-solid fa-circle-notch fa-spin text-3xl mb-3 text-red-500"></i>
                                    Menyinkronkan Data...
                                </div>
                            </div>
                        </div>
                        
                        <form id="form-chat" class="p-4 border-t border-slate-100 bg-white flex gap-3 items-center">
                            <input type="text" id="input-pesan" placeholder="Ketik pesan untuk vendor..." autocomplete="off" required
                                   class="flex-1 border-2 border-slate-100 p-4 rounded-xl outline-none focus:border-red-500 focus:bg-red-50/30 transition-all font-medium text-slate-700">
                            
                            <button type="submit" id="btn-kirim" class="bg-slate-900 text-white p-4 rounded-xl shadow-lg hover:bg-red-600 hover:-translate-y-1 transition-all w-16 flex items-center justify-center">
                                <i class="fa-solid fa-paper-plane text-lg"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    <?php if ($selected_po != ''): ?>
    <script>
        const chatBox = document.getElementById('chat-box');
        const formChat = document.getElementById('form-chat');
        const inputPesan = document.getElementById('input-pesan');
        const loadingChat = document.getElementById('loading-chat');
        const btnKirim = document.getElementById('btn-kirim');
        
        // Ambil nomor PO dari PHP
        const poNumber = "<?= htmlspecialchars($selected_po) ?>"; 
        const myRole = "<?= $_SESSION['role'] ?>"; 
        
        let lastMessageCount = 0;

        function loadChats() {
            fetch(`api_chat.php?action=fetch&po_number=${poNumber}`)
                .then(response => response.json())
                .then(data => {
                    if(loadingChat) loadingChat.style.display = 'none';

                    if (data.length !== lastMessageCount) {
                        lastMessageCount = data.length;
                        chatBox.innerHTML = ''; 
                        
                        if(data.length === 0) {
                            chatBox.innerHTML = '<div class="text-center text-slate-400 italic mt-10 font-medium">Belum ada percakapan untuk PO ini. Mulai sapa vendor Anda!</div>';
                        } else {
                            data.forEach(chat => {
                                let isMe = (chat.sender_role === myRole);
                                let alignment = isMe ? 'items-end' : 'items-start';
                                let bubbleColor = isMe ? 'bg-slate-800 text-white rounded-tr-none' : 'bg-red-600 text-white rounded-tl-none';
                                let nameAlign = isMe ? 'mr-2 text-right' : 'ml-2 text-left';

                                let chatTime = new Date(chat.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});

                                let chatHtml = `
                                    <div class="flex flex-col ${alignment}">
                                        <div class="text-[10px] font-bold text-slate-400 mb-1 ${nameAlign} uppercase tracking-widest">
                                            ${chat.sender_role} <span class="normal-case font-normal text-slate-300 ml-1">(${chat.sender_name})</span>
                                        </div>
                                        <div class="${bubbleColor} p-4 rounded-2xl max-w-[80%] shadow-md relative group">
                                            ${chat.message}
                                            <div class="text-[8px] opacity-50 mt-2 text-right">${chatTime}</div>
                                        </div>
                                    </div>
                                `;
                                chatBox.innerHTML += chatHtml;
                            });
                        }
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                })
                .catch(error => console.error('Error fetching chat:', error));
        }

        formChat.addEventListener('submit', function(e) {
            e.preventDefault(); 
            
            let messageText = inputPesan.value.trim();
            if(messageText === '') return;

            btnKirim.disabled = true;
            btnKirim.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            let formData = new FormData();
            formData.append('message', messageText);
            formData.append('po_number', poNumber);

            fetch('api_chat.php?action=send', {
                method: 'POST',
                body: formData
            }).then(response => response.text())
              .then(result => {
                inputPesan.value = ''; 
                btnKirim.disabled = false;
                btnKirim.innerHTML = '<i class="fa-solid fa-paper-plane text-lg"></i>';
                loadChats(); 
            }).catch(error => {
                console.error('Error sending chat:', error);
                btnKirim.disabled = false;
                btnKirim.innerHTML = '<i class="fa-solid fa-paper-plane text-lg"></i>';
            });
        });

        loadChats();
        setInterval(loadChats, 2000);
    </script>
    <?php endif; ?>
</body>
</html>