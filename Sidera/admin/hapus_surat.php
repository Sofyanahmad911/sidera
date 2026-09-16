<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

// Validasi Akses Admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

$id_surat = intval($_GET['id'] ?? 0);
$admin_id = $_SESSION['admin_id'] ?? NULL;

if ($id_surat > 0) {
    // 1. Ambil informasi nomor surat dan jenis sebelum dihapus (untuk kepentingan catatan log)
    $stmt_cek = $koneksi->prepare("SELECT nomor_surat, data_dinamis FROM transaksi_surat WHERE id = ? LIMIT 1");
    $stmt_cek->bind_param("i", $id_surat);
    $stmt_cek->execute();
    $res_cek = $stmt_cek->get_result();

    if ($res_cek->num_rows > 0) {
        $surat = $res_cek->fetch_assoc();
        $nomor_surat = $surat['nomor_surat'];
        $data_json = json_decode($surat['data_dinamis'], true);
        $jenis_surat = $data_json['jenis'] ?? 'Dokumen';
        $stmt_cek->close();

        // 2. Eksekusi Hapus dari Database
        $stmt_hapus = $koneksi->prepare("DELETE FROM transaksi_surat WHERE id = ?");
        $stmt_hapus->bind_param("i", $id_surat);
        
        if ($stmt_hapus->execute()) {
            $stmt_hapus->close();

            // 3. Catat ke Log Aktivitas SIDERA
            catat_log(
                $koneksi, 
                $admin_id, 
                "Hapus Surat", 
                "Menghapus arsip surat keluar [Jenis: {$jenis_surat}] dengan Nomor: {$nomor_surat}"
            );

            // Redirect kembali dengan status sukses (opsional)
            header("Location: manajemen_surat.php?status=sukses_hapus");
            exit;
        } else {
            $_SESSION['error_msg'] = "Gagal menghapus data dari database.";
        }
    } else {
        $_SESSION['error_msg'] = "Data surat tidak ditemukan.";
    }
}

header("Location: manajemen_surat.php");
exit;