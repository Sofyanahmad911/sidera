<?php
// includes/functions.php

/**
 * Fungsi untuk memformat tanggal ke standar Indonesia
 * Contoh: 2026-08-17 menjadi 17 Agustus 2026
 */
function format_tanggal_indo($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array (
        1 =>   'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

/**
 * Fungsi untuk menghitung umur otomatis berdasarkan tanggal lahir
 */
function hitung_umur($tgl_lahir) {
    $tgl_lahir_obj = new DateTime($tgl_lahir);
    $hari_ini = new DateTime("today");
    return $tgl_lahir_obj->diff($hari_ini)->y;
}

/**
 * Fungsi tingkat lanjut untuk keamanan Upload File
 * Mengecek ekstensi, ukuran, dan membuat nama file unik.
 */
function upload_file_aman($file_input, $target_dir, $allowed_extensions, $max_size_kb = 2048) {
    if (!isset($_FILES[$file_input]) || $_FILES[$file_input]['error'] != 0) {
        return ['status' => false, 'pesan' => 'Tidak ada file yang diunggah atau terjadi kesalahan.'];
    }

    $nama_asli = basename($_FILES[$file_input]["name"]);
    $ukuran_file = $_FILES[$file_input]["size"];
    $tmp_name = $_FILES[$file_input]["tmp_name"];
    
    $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    // 1. Validasi Ekstensi String
    if (!in_array($ekstensi, $allowed_extensions)) {
        return ['status' => false, 'pesan' => 'Format file tidak diizinkan!'];
    }

    // 2. Validasi MIME Type Asli (Anti-Spoofing)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    // Daftar MIME type yang aman (sesuaikan dengan kebutuhan sistem desa)
    $allowed_mimes = [
        'image/jpeg', 
        'image/png', 
        'application/pdf', 
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'video/mp4'
    ];

    if (!in_array($mime_type, $allowed_mimes)) {
        return ['status' => false, 'pesan' => 'MIME type file terdeteksi tidak valid atau dimanipulasi!'];
    }

    // 3. Validasi Ukuran
    if ($ukuran_file > ($max_size_kb * 1024)) {
        return ['status' => false, 'pesan' => 'Ukuran file terlalu besar! Maksimal ' . ($max_size_kb / 1024) . ' MB.'];
    }

    $nama_baru = time() . "_" . bin2hex(random_bytes(5)) . "." . $ekstensi;
    $target_file = $target_dir . $nama_baru;

    if (move_uploaded_file($tmp_name, $target_file)) {
        return ['status' => true, 'nama_file' => $nama_baru];
    } else {
        return ['status' => false, 'pesan' => 'Gagal memindahkan file ke server.'];
    }
}
/**
 * Fungsi untuk membuat Token CSRF unik per sesi pengguna
 */
function generate_csrf_token() {
    // Jika token belum ada di sesi ini, buat baru
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Fungsi untuk memverifikasi keaslian Token CSRF dari form
 */
function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}
/**
 * Fungsi untuk mencatat setiap aktivitas krusial yang dilakukan oleh Admin/Operator
 */
function catat_log($koneksi, $admin_id, $aksi, $detail_aksi) {
    // Memastikan koneksi dan parameter valid
    if (!$koneksi || empty($admin_id) || empty($aksi)) return false;

    $stmt = $koneksi->prepare("INSERT INTO log_aktivitas (admin_id, aksi, detail_aksi) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $admin_id, $aksi, $detail_aksi);
    
    $sukses = $stmt->execute();
    $stmt->close();
    
    return $sukses;
}

/**
 * Fungsi untuk menghapus cache statistik agar infografis langsung ter-update
 */
function bersihkan_cache_statistik() {
    $cache_file = __DIR__ . '/../assets/cache/statistik_desa.json';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}
?>