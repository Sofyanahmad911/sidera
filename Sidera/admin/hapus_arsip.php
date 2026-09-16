<?php
// admin/hapus_arsip.php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_logged_in'])) { header("Location: ../login.php"); exit; }

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt_get = $koneksi->prepare("SELECT kategori_arsip, file_path FROM arsip_desa WHERE id = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Hapus file fisik
        $folder = ($row['kategori_arsip'] == 'Foto Kegiatan') ? "foto_kegiatan/" : "arsip_dokumen/";
        $file_path = "../assets/uploads/" . $folder . $row['file_path'];
        
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Hapus dari database
        $stmt_del = $koneksi->prepare("DELETE FROM arsip_desa WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        
        if ($stmt_del->execute()) {
            echo "<script>alert('Arsip digital telah dihapus permanen dari server.'); window.location.href='manajemen_arsip.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data dari database.'); window.location.href='manajemen_arsip.php';</script>";
        }
        $stmt_del->close();
    }
    $stmt_get->close();
} else {
    header("Location: manajemen_arsip.php");
}
?>