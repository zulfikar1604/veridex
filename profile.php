<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

require 'database.php';

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Ambil detail data user dari database
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = mysqli_fetch_assoc($query_user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $new_password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $photo_path = $user['profile_photo']; // Default to existing photo

    // Handle Remove Photo
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        if (!empty($photo_path) && file_exists($photo_path)) {
            unlink($photo_path);
        }
        $photo_path = ''; // Set ke kosong untuk dihapus dari DB
    }

    // Handle Crop Image Base64
    if (!empty($_POST['cropped_image'])) {
        $img = $_POST['cropped_image'];
        if (strpos($img, 'data:image') === 0) {
            $img = preg_replace('/^data:image\/\w+;base64,/', '', $img);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            
            if ($data !== false) {
                $new_file_name = "profile_" . $user_id . "_" . time() . ".jpg";
                $dest_path = "uploads/profiles/" . $new_file_name;
                
                if (file_put_contents($dest_path, $data)) {
                    if (!empty($photo_path) && file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                    $photo_path = $dest_path;
                } else {
                    $error_message = "Gagal menyimpan foto hasil potongan.";
                }
            } else {
                $error_message = "Format data gambar tidak valid.";
            }
        }
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_name = $_FILES['profile_photo']['name'];
        $file_size = $_FILES['profile_photo']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 2097152) { // 2MB limit
                $new_file_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
                $dest_path = "uploads/profiles/" . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    // Hapus foto lama jika ada
                    if (!empty($photo_path) && file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                    $photo_path = $dest_path;
                } else {
                    $error_message = "Gagal mengunggah foto profil.";
                }
            } else {
                $error_message = "Ukuran file terlalu besar (maksimal 2MB).";
            }
        } else {
            $error_message = "Format file tidak didukung. Hanya JPG, PNG, dan GIF.";
        }
    }

    if (empty($error_message)) {
        if (empty($new_username)) {
            $error_message = "Username tidak boleh kosong.";
        } elseif (empty($new_password)) {
            $error_message = "Password tidak boleh kosong.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Konfirmasi password baru tidak cocok.";
        } else {
            // Cek apakah username sudah dipakai oleh user lain
            $check_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$new_username' AND id != '$user_id'");
            if (mysqli_num_rows($check_user) > 0) {
                $error_message = "Username sudah digunakan oleh akun lain.";
            } else {
                // Update data
                $update = mysqli_query($conn, "UPDATE users SET username = '$new_username', password = '$new_password', profile_photo = '$photo_path' WHERE id = '$user_id'");
                if ($update) {
                    $_SESSION['username'] = $new_username;
                    $_SESSION['profile_photo'] = $photo_path;
                    $success_message = "Profil berhasil diperbarui.";
                    
                    // Refresh data lokal
                    $user['username'] = $new_username;
                    $user['password'] = $new_password;
                    $user['profile_photo'] = $photo_path;
                } else {
                    $error_message = "Terjadi kesalahan sistem saat memperbarui profil.";
                }
            }
        }
    }
}

$page_title = "Pengaturan Akun";
$page_subtitle = "Kelola Profil & Keamanan Akses Akun";
$active = 'profile'; // Agar sidebar link tidak salah aktif

include 'header_meta.php';

// Menentukan sidebar yang tepat sesuai role user
$role = $_SESSION['role'] ?? '';
?>
<body class="bg-slate-50 dark:bg-[#0f172a] flex h-screen overflow-hidden text-slate-800 dark:text-white">

    <?php 
    if ($role === 'Gudang') {
        include 'sidebar_gudang.php';
    } elseif ($role === 'Finance') {
        include 'sidebar_finance.php';
    } elseif ($role === 'Vendor') {
        include 'sidebar_vendor.php';
    } else {
        // Manager Procurement atau Procurement menggunakan sidebar yang sama
        include 'sidebar_procurement.php';
    }
    ?>

    <main class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Panggil Navbar Terpadu -->
        <?php include 'navbar.php'; ?>

        <!-- Area Konten Utama -->
        <div class="flex-1 overflow-y-auto px-4 md:px-8 pb-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Alert Banners -->
                <?php if ($success_message !== ''): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-5 rounded-2xl flex gap-3.5 items-start shadow-sm animate-fade-in">
                        <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-check text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Pembaruan Berhasil</p>
                            <p class="text-xs font-bold mt-0.5"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_message !== ''): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 p-5 rounded-2xl flex gap-3.5 items-start shadow-sm animate-fade-in">
                        <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-circle-exclamation text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-red-700">Pembaruan Gagal</p>
                            <p class="text-xs font-bold mt-0.5"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Grid Layout Layout Utama -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="profileEditor()">

                    <!-- Kolom Kiri: Ringkasan Profile Card (Premium Look) -->
                    <div class="bg-slate-900 rounded-[2rem] p-6 text-white shadow-xl relative overflow-hidden flex flex-col justify-between h-fit min-h-[300px]">
                        <!-- Background Pattern Deco -->
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-red-500 rounded-full blur-3xl opacity-20"></div>
                        <div class="absolute -left-10 -top-10 w-40 h-40 bg-blue-500 rounded-full blur-3xl opacity-10"></div>
                        
                        <div class="relative z-10 text-center">
                            <!-- Avatar Badge -->
                            <div class="w-20 h-20 bg-slate-800 border-2 border-slate-700 rounded-2xl text-white flex items-center justify-center shadow-lg mx-auto mb-4 overflow-hidden">
                                <template x-if="photoPreview">
                                    <img :src="photoPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!photoPreview">
                                    <div class="w-full h-full">
                                        <?php if (!empty($user['profile_photo'])): ?>
                                            <img src="<?= htmlspecialchars($user['profile_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile Photo" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <?php
                                                $profile_icon = 'fa-user-astronaut';
                                                if ($role === 'Manager Procurement') $profile_icon = 'fa-user-tie';
                                                elseif ($role === 'Procurement') $profile_icon = 'fa-user-gear';
                                                elseif ($role === 'Gudang') $profile_icon = 'fa-warehouse';
                                                elseif ($role === 'Finance') $profile_icon = 'fa-file-invoice-dollar';
                                                elseif ($role === 'Vendor') $profile_icon = 'fa-truck-field';
                                                ?>
                                                <i class="fa-solid <?= $profile_icon ?> text-3xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Account Meta -->
                            <h3 class="text-lg font-black tracking-tight uppercase"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="inline-block mt-2 px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-red-600 text-white shadow-lg shadow-red-600/30">
                                <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="relative z-10 pt-6 border-t border-slate-800 mt-6 space-y-3">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">User ID</span>
                                <span class="font-black text-slate-200">#<?= $user['id'] ?></span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider text-[10px]">Status Akun</span>
                                <span class="font-black text-green-400 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-ping"></span> Aktif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Form Edit Data -->
                    <div class="bg-white dark:bg-[#1e293b] rounded-[2rem] p-6 md:p-8 border border-slate-100 dark:border-slate-800 shadow-sm md:col-span-2 space-y-6">
                        <div>
                            <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Informasi Profil & Keamanan</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Perbarui username dan password Anda untuk menjaga keamanan akses akun.</p>
                        </div>

                        <form action="profile.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                            
                            <!-- Profile Photo Field -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Foto Profil (Opsional)</label>
                                <div class="relative">
                                    <input type="hidden" name="cropped_image" id="cropped_image_input">
                                    <input type="file" 
                                           id="profile_photo_input"
                                           accept="image/*"
                                           @change="handleFileSelect"
                                           class="w-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 px-4 py-3 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-red-50 file:text-red-700 hover:file:bg-red-100 cursor-pointer">
                                </div>
                                <?php if (!empty($user['profile_photo'])): ?>
                                <div class="pt-2 flex items-center gap-2">
                                    <input type="checkbox" id="remove_photo_checkbox" name="remove_photo" value="1" class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500" @change="if($el.checked) photoPreview = null;">
                                    <label for="remove_photo_checkbox" class="text-xs font-bold text-slate-600 dark:text-slate-300 cursor-pointer">Hapus foto profil saat ini</label>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Username Field -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Username Baru</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400 dark:text-slate-500">
                                        <i class="fa-solid fa-user text-sm"></i>
                                    </span>
                                    <input type="text" 
                                           name="username" 
                                           value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" 
                                           required 
                                           style="padding-left: 3rem;"
                                           class="w-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 pr-4 py-3.5 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition">
                                </div>
                            </div>

                            <!-- Role Field (Read-only) -->
                            <div class="space-y-1.5 opacity-70">
                                <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tingkat Jabatan / Role (Read-only)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400 dark:text-slate-500">
                                        <i class="fa-solid fa-shield text-sm"></i>
                                    </span>
                                    <input type="text" 
                                           value="<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>" 
                                           disabled 
                                           style="padding-left: 3rem;"
                                           class="w-full border border-slate-200 dark:border-slate-700 bg-slate-100 pr-4 py-3.5 rounded-xl font-bold text-slate-500 dark:text-slate-400 dark:text-slate-500 cursor-not-allowed">
                                </div>
                            </div>

                            <div class="my-6 border-t border-slate-100 dark:border-slate-800"></div>

                            <!-- Password Field -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Password Baru</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400 dark:text-slate-500">
                                        <i class="fa-solid fa-lock text-sm"></i>
                                    </span>
                                    <input type="password" 
                                           name="password" 
                                           value="<?= htmlspecialchars($user['password'], ENT_QUOTES, 'UTF-8') ?>" 
                                           required 
                                           style="padding-left: 3rem;"
                                           class="w-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 pr-4 py-3.5 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition">
                                </div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Konfirmasi Password Baru</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-4 flex items-center text-slate-400 dark:text-slate-500">
                                        <i class="fa-solid fa-circle-check text-sm"></i>
                                    </span>
                                    <input type="password" 
                                           name="confirm_password" 
                                           value="<?= htmlspecialchars($user['password'], ENT_QUOTES, 'UTF-8') ?>" 
                                           required 
                                           style="padding-left: 3rem;"
                                           class="w-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 pr-4 py-3.5 rounded-xl font-bold text-slate-700 dark:text-slate-200 focus:outline-none focus:border-red-500 focus:bg-white dark:bg-[#1e293b] transition">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-3">
                                <button type="submit" 
                                        class="w-full bg-red-600 hover:bg-red-700 text-white font-black uppercase tracking-wider py-4 rounded-xl shadow-lg shadow-red-500/20 hover:shadow-xl hover:shadow-red-500/30 transition-all active:scale-[0.98]">
                                    Simpan Perubahan
                                </button>
                            </div>

                        </form>
                    </div>

                </div>

            </div>
        </div>

        <!-- Slide-Over Cropper Panel -->
        <div x-show="isCropperOpen" x-cloak class="fixed inset-0 z-[60] flex justify-end">
            <div x-show="isCropperOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="cancelCrop"></div>
            
            <div x-show="isCropperOpen" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="relative w-full max-w-lg h-full bg-white dark:bg-[#1e293b] shadow-2xl flex flex-col z-[60] border-l border-slate-100 dark:border-slate-800">
                 
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 shrink-0">
                    <div>
                        <h3 class="text-base font-black tracking-tight text-slate-800 dark:text-white uppercase">Sesuaikan Foto</h3>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 dark:text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Geser & Perbesar</p>
                    </div>
                    <button @click="cancelCrop" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500 hover:text-red-500 hover:border-red-200 dark:hover:border-red-900 transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 bg-slate-100 dark:bg-slate-900">
                    <div class="w-full h-full min-h-[400px] bg-black rounded-2xl overflow-hidden shadow-inner border-2 border-slate-200 dark:border-slate-700 flex flex-col">
                        <img id="cropperImage" src="" class="max-w-full hidden flex-1">
                    </div>
                </div>
                
                <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 dark:bg-slate-900/50 flex justify-end gap-3 shrink-0">
                    <button @click="cancelCrop" type="button" class="px-6 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] text-slate-600 dark:text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 transition flex-1 text-center">
                        Batal
                    </button>
                    <button @click="applyCrop" type="button" class="px-6 py-3.5 rounded-xl font-black uppercase tracking-widest text-[10px] text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-500/30 transition flex-1 text-center">
                        Terapkan
                    </button>
                </div>
            </div>
        </div>

    </main>

    <!-- Cropper.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('profileEditor', () => ({
                photoPreview: null,
                isCropperOpen: false,
                cropperInstance: null,
                init() {
                    this.$watch('isCropperOpen', value => {
                        if (!value && this.cropperInstance) {
                            this.cropperInstance.destroy();
                            this.cropperInstance = null;
                        }
                    });
                },
                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    
                    // Validate if it's an image
                    if (!file.type.startsWith('image/')) {
                        alert('Silakan pilih file gambar.');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.isCropperOpen = true;
                        // Wait for modal to render
                        setTimeout(() => {
                            const image = document.getElementById('cropperImage');
                            image.src = e.target.result;
                            image.classList.remove('hidden');
                            
                            if (this.cropperInstance) {
                                this.cropperInstance.destroy();
                            }
                            
                            this.cropperInstance = new Cropper(image, {
                                aspectRatio: 1,
                                viewMode: 1,
                                dragMode: 'move',
                                autoCropArea: 0.8,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: false,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                            });
                        }, 50);
                    };
                    reader.readAsDataURL(file);
                },
                applyCrop() {
                    if (!this.cropperInstance) return;
                    
                    const canvas = this.cropperInstance.getCroppedCanvas({
                        width: 400,
                        height: 400,
                    });
                    
                    const base64Image = canvas.toDataURL('image/jpeg', 0.9);
                    
                    this.photoPreview = base64Image;
                    document.getElementById('cropped_image_input').value = base64Image;
                    
                    const cb = document.getElementById('remove_photo_checkbox');
                    if(cb) cb.checked = false;
                    
                    this.isCropperOpen = false;
                },
                cancelCrop() {
                    this.isCropperOpen = false;
                    document.getElementById('profile_photo_input').value = '';
                }
            }));
        });
    </script>
</body>
</html>
