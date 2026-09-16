<?php
// admin/tambah_arsip.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitasi Input
    $judul = $koneksi->real_escape_string($_POST['judul_arsip']);
    $kategori = $koneksi->real_escape_string($_POST['kategori_arsip']);
    $nomor = $koneksi->real_escape_string($_POST['nomor_dokumen']);
    $tgl = $_POST['tgl_dokumen'];
    $ket = $koneksi->real_escape_string($_POST['keterangan']);

    // Penentuan folder target berdasarkan kategori
    $target_dir = ($kategori == 'Foto Kegiatan') ? "../assets/uploads/foto_kegiatan/" : "../assets/uploads/arsip_dokumen/";
    
    // Ekstensi yang diizinkan & Batas ukuran (misal: 5MB / 5120 KB)
    $allowed_ext = ["pdf", "jpg", "jpeg", "png", "docx", "xlsx"];
    
    // Panggil fungsi Helper kita
    $upload = upload_file_aman("file_arsip", $target_dir, $allowed_ext, 5120);

    if ($upload['status'] === true) {
        $file_baru = $upload['nama_file']; // Ambil nama file unik yang digenerate helper
        
        $stmt = $koneksi->prepare("INSERT INTO arsip_desa (judul_arsip, kategori_arsip, nomor_dokumen, tgl_dokumen, file_path, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $judul, $kategori, $nomor, $tgl, $file_baru, $ket);
        
        if ($stmt->execute()) {
            echo "<script>alert('Arsip digital berhasil diunggah ke server!'); window.location.href='manajemen_arsip.php';</script>";
            exit;
        } else {
            $pesan = "<div class='alert-error'>Error Database: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        // Tampilkan pesan error spesifik dari fungsi helper (misal: ukuran terlalu besar)
        $pesan = "<div class='alert-error'>" . $upload['pesan'] . "</div>";
    }
}
?>

<style>
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; }
    .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: var(--transition); background: var(--bg-light); font-family: 'Poppins', sans-serif; }
    .form-control:focus { border-color: var(--secondary); background: #fff; box-shadow: 0 0 0 3px rgba(89, 213, 224, 0.2); }
    .btn-save { background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.3s; }
    .btn-save:hover { background: var(--sidebar-hover); transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .btn-back { display: inline-block; background: #e2e8f0; color: var(--text-dark); padding: 15px 30px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; transition: 0.3s; margin-right: 10px; }
    .alert-error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ef4444; font-weight: 500; }
</style>

<div class="page-header">
    <h1 class="page-title">Unggah Arsip Digital Baru</h1>
    <p class="page-subtitle">Simpan surat, keputusan desa, dan dokumentasi ke dalam cloud server.</p>
</div>

<?= $pesan ?>

<div class="admin-card">
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div>
                <div class="form-group">
                    <label class="form-label">Kategori Arsip</label>
                    <select name="kategori_arsip" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Surat Masuk">Surat Masuk</option>
                        <option value="Surat Keluar">Surat Keluar</option>
                        <option value="Dokumen Penting">Dokumen Penting (Perdes/SK/Aset)</option>
                        <option value="Foto Kegiatan">Foto Kegiatan</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Judul / Perihal</label>
                    <input type="text" name="judul_arsip" class="form-control" required placeholder="Contoh: Undangan Rapat Kecamatan...">
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Dokumen (Opsional)</label>
                    <input type="text" name="nomor_dokumen" class="form-control" placeholder="Contoh: 140/02/Ds/2026">
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label class="form-label">Tanggal Dokumen</label>
                    <input type="date" name="tgl_dokumen" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">File Dokumen (.PDF, .JPG, .DOCX)</label>
                    <input type="file" name="file_arsip" class="form-control" required style="background: #fff;">
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Tuliskan catatan singkat..."></textarea>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <a href="manajemen_arsip.php" class="btn-back">Batal</a>
            <button type="submit" class="btn-save"><i class="fa-solid fa-cloud-arrow-up"></i> Upload ke Server</button>
        </div>
    </form>
</div>

<?php require_once 'includes/admin_footer.php'; ?>