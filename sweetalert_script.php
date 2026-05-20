<!-- SweetAlert2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

<script>
    // Konfirmasi Logout
    function confirmLogout(event, url) {
        event.preventDefault();
        Swal.fire({
            title: 'Keluar dari Aplikasi?',
            text: "Anda harus login kembali untuk mengakses sistem.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Logout Sekarang!',
            cancelButtonText: 'Batal',
            backdrop: `rgba(15, 23, 42, 0.8)`
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    // Welcome Pop-up
    <?php if(isset($_SESSION['welcome_message'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Selamat Datang!',
                text: '<?= addslashes($_SESSION['welcome_message']) ?>',
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
        <?php unset($_SESSION['welcome_message']); ?>
    <?php endif; ?>
</script>
