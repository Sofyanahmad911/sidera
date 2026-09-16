<?php
// admin/edit_penduduk.php
require_once '../config/koneksi.php';
require_once 'includes/admin_header.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan_error = "";
$pesan_sukses = "";

// 1. Ambil Data Lama
$stmt_get = $koneksi->prepare("SELECT * FROM penduduk WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$penduduk = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$penduduk) {
    echo "<div class='admin-card'>Data penduduk tidak ditemukan!</div>";
    require_once 'includes/admin_footer.php';
    exit;
}

// 2. Proses Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifikasi CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $pesan_error = "Sesi tidak valid, silakan muat ulang halaman.";
    } else {
        // Ambil input text dasar
        $nik = $koneksi->real_escape_string($_POST['nik']);
        $nama_lengkap = $koneksi->real_escape_string($_POST['nama_lengkap']);
        $tempat_lahir = $koneksi->real_escape_string($_POST['tempat_lahir']);
        $tgl_lahir = $_POST['tgl_lahir'];
        
        // HITUNG UMUR OTOMATIS
        $umur = hitung_umur($tgl_lahir); 
        
        $jenis_kelamin = $_POST['jenis_kelamin'];
        $rt = $_POST['rt'];
        $rw = $_POST['rw'];
        $nama_dusun = $koneksi->real_escape_string($_POST['nama_dusun']);
        $kel_desa = $koneksi->real_escape_string($_POST['kel_desa']);
        $kecamatan = $koneksi->real_escape_string($_POST['kecamatan']);
        $kabupaten = $koneksi->real_escape_string($_POST['kabupaten']);
        $provinsi = $koneksi->real_escape_string($_POST['provinsi']);
        $agama = $_POST['agama'];
        $status_perkawinan = $_POST['status_perkawinan'];
        $pekerjaan = $koneksi->real_escape_string($_POST['pekerjaan']);
        $kewarganegaraan = $_POST['kewarganegaraan'];

        // Default nama file menggunakan file lama
        $nama_foto = $penduduk['foto_warga'];
        $nama_dokumen = $penduduk['arsip_foto_ktp_kk'];

        // PROSES UPLOAD FOTO BARU JIKA ADA
        if (!empty($_FILES['foto_warga']['name'])) {
            $upload_foto = upload_file_aman('foto_warga', '../assets/uploads/penduduk/', ['jpg', 'jpeg', 'png'], 2048);
            if ($upload_foto['status']) {
                $nama_foto = $upload_foto['nama_file'];
                // Hapus foto lama
                if (!empty($penduduk['foto_warga']) && file_exists('../assets/uploads/penduduk/' . $penduduk['foto_warga'])) {
                    unlink('../assets/uploads/penduduk/' . $penduduk['foto_warga']);
                }
            } else {
                $pesan_error .= "Foto gagal diunggah: " . $upload_foto['pesan'] . "<br>";
            }
        }

        // PROSES UPLOAD DOKUMEN BARU JIKA ADA
        if (!empty($_FILES['arsip_foto_ktp_kk']['name'])) {
            $upload_dokumen = upload_file_aman('arsip_foto_ktp_kk', '../assets/uploads/dokumen_warga/', ['jpg', 'jpeg', 'png', 'pdf'], 5120);
            if ($upload_dokumen['status']) {
                $nama_dokumen = $upload_dokumen['nama_file'];
                // Hapus dokumen lama
                if (!empty($penduduk['arsip_foto_ktp_kk']) && file_exists('../assets/uploads/dokumen_warga/' . $penduduk['arsip_foto_ktp_kk'])) {
                    unlink('../assets/uploads/dokumen_warga/' . $penduduk['arsip_foto_ktp_kk']);
                }
            } else {
                $pesan_error .= "Dokumen gagal diunggah: " . $upload_dokumen['pesan'] . "<br>";
            }
        }

        // Jika tidak ada error pada upload, lakukan UPDATE database
        if ($pesan_error == "") {
            $query_update = "UPDATE penduduk SET 
                nik=?, nama_lengkap=?, tempat_lahir=?, tgl_lahir=?, umur=?, jenis_kelamin=?, 
                rt=?, rw=?, nama_dusun=?, kel_desa=?, kecamatan=?, kabupaten=?, provinsi=?, 
                agama=?, status_perkawinan=?, pekerjaan=?, kewarganegaraan=?, foto_warga=?, arsip_foto_ktp_kk=? 
                WHERE id=?";
                
            $stmt_update = $koneksi->prepare($query_update);
            $stmt_update->bind_param("ssssissssssssssssssi", 
                $nik, $nama_lengkap, $tempat_lahir, $tgl_lahir, $umur, $jenis_kelamin, 
                $rt, $rw, $nama_dusun, $kel_desa, $kecamatan, $kabupaten, $provinsi, 
                $agama, $status_perkawinan, $pekerjaan, $kewarganegaraan, $nama_foto, $nama_dokumen, $id
            );

            if ($stmt_update->execute()) {
                // WAJIB: Hapus cache infografis karena demografi (umur/pekerjaan/gender) mungkin berubah
                bersihkan_cache_statistik();

                // Catat Log
                catat_log($koneksi, $_SESSION['admin_id'], 'Edit Penduduk', "Memperbarui data penduduk NIK: $nik");

                $pesan_sukses = "Data kependudukan berhasil diperbarui!";
                
                // Refresh data untuk ditampilkan di form
                $penduduk['foto_warga'] = $nama_foto;
                $penduduk['arsip_foto_ktp_kk'] = $nama_dokumen;
                // Update sisa field (opsional, jika ingin form langsung merefleksikan input terbaru tanpa query ulang)
            } else {
                $pesan_error = "Gagal menyimpan data: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}
?>

<!-- ============================================== -->
<!-- UI FORM EDIT PENDUDUK (Contoh Potongan HTML)   -->
<!-- ============================================== -->
<div class="page-header">
    <h1 class="page-title">Edit Data Penduduk</h1>
</div>

<div class="admin-card">
    <?php if($pesan_error) echo "<div class='alert-box' style='background: #fee2e2; color: #b91c1c; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>$pesan_error</div>"; ?>
    <?php if($pesan_sukses) echo "<div class='alert-box' style='background: #dcfce7; color: #166534; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>$pesan_sukses</div>"; ?>

    <!-- Pastikan enctype multipart/form-data ada untuk upload file -->
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Kolom NIK -->
            <div class="form-group">
                <label class="form-label">Nomor Induk Kependudukan (NIK)</label>
                <input type="text" name="nik" class="form-control" value="<?= htmlspecialchars($penduduk['nik']) ?>" required maxlength="16">
            </div>
            
            <!-- Kolom Nama Lengkap -->
            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($penduduk['nama_lengkap']) ?>" required>
            </div>

            <!-- Kolom Tanggal Lahir (Umur di-hide karena otomatis) -->
            <div class="form-group">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="tgl_lahir" class="form-control" value="<?= htmlspecialchars($penduduk['tgl_lahir']) ?>" required>
                <small style="color: var(--text-muted);">Umur saat ini: <?= htmlspecialchars($penduduk['umur']) ?> tahun (Akan dihitung ulang otomatis saat disimpan).</small>
            </div>

            <!-- ... Tambahkan input form lainnya (Tempat lahir, Jenis Kelamin, Agama, Pekerjaan, RT/RW, dsb) dengan format value="<?= htmlspecialchars($penduduk['field']) ?>" ... -->

            <!-- Upload Foto Warga -->
            <div class="form-group">
                <label class="form-label">Foto Warga Baru (Opsional)</label>
                <?php if($penduduk['foto_warga']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../assets/uploads/penduduk/<?= htmlspecialchars($penduduk['foto_warga']) ?>" alt="Foto" style="height: 60px; border-radius: 8px;">
                    </div>
                <?php endif; ?>
                <input type="file" name="foto_warga" class="form-control" accept="image/jpeg, image/png">
                <small style="color: var(--text-muted);">Biarkan kosong jika tidak ingin mengubah foto.</small>
            </div>
            
            <!-- Upload KTP/KK -->
            <div class="form-group">
                <label class="form-label">Dokumen KTP/KK Baru (Opsional)</label>
                <?php if($penduduk['arsip_foto_ktp_kk']): ?>
                    <div style="margin-bottom: 10px;">
                        <a href="../assets/uploads/dokumen_warga/<?= htmlspecialchars($penduduk['arsip_foto_ktp_kk']) ?>" target="_blank" style="color: var(--primary);">Lihat Dokumen Saat Ini</a>
                    </div>
                <?php endif; ?>
                <input type="file" name="arsip_foto_ktp_kk" class="form-control" accept="image/jpeg, image/png, application/pdf">
            </div>
        </div>

        <div style="margin-top: 30px;">
            <button type="submit" class="btn-primary" style="background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
            </button>
            <a href="manajemen_penduduk.php" class="btn-outline" style="text-decoration: none; margin-left: 10px;">Batal</a>
        </div>
    </form>
</div>

<?php require_once 'includes/admin_footer.php'; ?>