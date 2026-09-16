<!-- admin/includes/admin_footer.php -->
        </div> <!-- End of .content-area -->
    </main> <!-- End of .main-wrapper -->

    <!-- Integrasi SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Script Global UI/UX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Interceptor untuk semua tombol hapus dengan class 'btn-delete-confirm'
            const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Tahan dulu eksekusi link default
                    const targetUrl = this.getAttribute('href'); // Ambil link tujuan

                    Swal.fire({
                        title: 'Hapus Data Permanen?',
                        text: "Aksi ini tidak dapat dibatalkan. Seluruh berkas terkait juga akan terhapus dari server!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: '<i class="fa-solid fa-trash-can"></i> Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        reverseButtons: true, // Tukar posisi tombol
                        customClass: {
                            confirmButton: 'btn-save', // Menggunakan class CSS yang sudah ada di sistemmu
                            cancelButton: 'btn-back'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Tampilkan animasi loading sebelum pindah halaman
                            Swal.fire({
                                title: 'Memproses...',
                                text: 'Mohon tunggu sebentar',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading()
                                }
                            });
                            // Eksekusi penghapusan
                            window.location.href = targetUrl;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>