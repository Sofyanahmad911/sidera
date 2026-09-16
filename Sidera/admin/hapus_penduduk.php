<?php
// admin/hapus_penduduk.php
require_once '../config/koneksi.php';

// Proteksi Akses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit('Akses Ditolak.');
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 1. Ambil data file dan nama warga sebelum dihapus
    $stmt_select = $koneksi->prepare("SELECT nik, nama_lengkap, foto_warga, arsip_foto_ktp_kk FROM penduduk WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // 2. Hapus File Fisik Foto Warga jika ada
        if (!empty($data['foto_warga'])) {
            $path_foto = "../assets/uploads/penduduk/" . $data['foto_warga'];
            if (file_exists($path_foto)) { unlink($path_foto); }
        }

        // 3. Hapus File Fisik Dokumen KTP/KK jika ada
        if (!empty($data['arsip_foto_ktp_kk'])) {
            $path_dokumen = "../assets/uploads/dokumen_warga/" . $data['arsip_foto_ktp_kk'];
            if (file_exists($path_dokumen)) { unlink($path_dokumen); }
        }

        // 4. Hapus Data dari Database
        $stmt_delete = $koneksi->prepare("DELETE FROM penduduk WHERE id = ?");
        $stmt_delete->bind_param("i", $id);
        
        if ($stmt_delete->execute()) {
            // 5. WAJIB: Hapus cache agar infografis di beranda ter-update
            bersihkan_cache_statistik();

            // 6. Catat Log
            $detail_log = "Menghapus data penduduk: " . $data['nama_lengkap'] . " (NIK: " . $data['nik'] . ")";
            catat_log($koneksi, $_SESSION['admin_id'], 'Hapus Penduduk', $detail_log);

            header("Location: manajemen_penduduk.php?pesan=dihapus");
            exit;
        }
        $stmt_delete->close();
    }
    $stmt_select->close();
}

header("Location: manajemen_penduduk.php?pesan=gagal");
exit;
?>