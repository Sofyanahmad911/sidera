<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_logged_in'])) { die("Akses ditolak."); }

$id_surat = intval($_GET['id'] ?? 0);

// Ambil data transaksi surat beserta data penduduk (menggunakan LEFT JOIN untuk mengakomodasi surat manual)
$stmt = $koneksi->prepare("
    SELECT t.*, p.nik, p.nama_lengkap, p.tempat_lahir, p.tgl_lahir, p.jenis_kelamin, p.pekerjaan, p.nama_dusun, p.rt, p.rw, p.agama, p.status_perkawinan 
    FROM transaksi_surat t 
    LEFT JOIN penduduk p ON t.penduduk_id = p.id 
    WHERE t.id = ? LIMIT 1
");
$stmt->bind_param("i", $id_surat);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif; color:red;'><h3>Arsip surat tidak ditemukan di database.</h3></div>");
}

$surat = $result->fetch_assoc();
$stmt->close();

// Parse data dinamis JSON
$data_dinamis = json_decode($surat['data_dinamis'], true);
$jenis_surat  = $data_dinamis['jenis'] ?? 'SKTM';
$keperluan    = $data_dinamis['keperluan'] ?? '-';
$is_manual    = $data_dinamis['is_manual'] ?? false;

// Tentukan data warga (jika manual ambil dari json, jika tidak ambil dari hasil join database)
$warga = [];
if ($is_manual && isset($data_dinamis['data_warga'])) {
    $warga = $data_dinamis['data_warga'];
} else {
    $warga = $surat;
}

$nomor_surat_lengkap = $surat['nomor_surat'];

// Data Kepala Desa
$q_kades = $koneksi->query("SELECT nama_pejabat FROM struktur_organisasi WHERE jabatan = 'kepala desa' LIMIT 1");
$data_kades = $q_kades->fetch_assoc();
$nama_kades = $data_kades ? strtoupper($data_kades['nama_pejabat']) : "HERMAN YADI, S. Adm";
$nik_kades = "5202041234560001"; 
$jabatan_kades = "KEPALA DESA";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Surat - <?= htmlspecialchars($nomor_surat_lengkap) ?></title>
    <style>
        @page { size: A4; margin: 2.5cm 2cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; line-height: 1.5; background: #525659; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .kertas { background: #fff; width: 21cm; min-height: 29.7cm; padding: 2.5cm 2cm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.5); margin: 0 auto; }
        .kop-surat { display: flex; align-items: center; border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; position: relative; }
        .kop-surat::after { content: ""; position: absolute; bottom: -6px; left: 0; width: 100%; height: 1.5px; background: #000; }
        .logo-kop { width: 90px; height: auto; margin-right: 20px; }
        .teks-kop { text-align: center; flex: 1; line-height: 1.2; padding-right: 90px; }
        .teks-kop h3 { margin: 0; font-size: 14pt; font-weight: normal; }
        .teks-kop h1 { margin: 0; font-size: 16pt; font-weight: bold; }
        .judul-surat { text-align: center; margin-bottom: 30px; line-height: 1.2; }
        .judul-surat span { text-decoration: underline; font-weight: bold; font-size: 12pt; letter-spacing: 1px; }
        .tabel-identitas { width: 100%; margin-left: 20px; margin-bottom: 15px; border-collapse: collapse; }
        .tabel-identitas td { vertical-align: top; padding: 3px 0; }
        .tabel-identitas td:first-child { width: 30%; }
        .paragraf-indent { text-indent: 45px; text-align: justify; margin-bottom: 15px; line-height: 1.5; }
        .ttd-container { width: 300px; float: right; text-align: center; margin-top: 40px; }
        .ttd-nama { margin-top: 90px; font-weight: bold; text-decoration: underline; }
        .btn-print { position: fixed; bottom: 30px; right: 30px; padding: 15px 30px; background: #1a6f76; color: white; font-weight: bold; border-radius: 8px; cursor: pointer; border: none; font-size: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); z-index: 100;}
        .btn-print:hover { background: #13555b; }
        @media print { body { background: none; display: block; padding: 0; } .kertas { box-shadow: none; margin: 0; padding: 0; width: auto; min-height: auto; } .no-print { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()" class="no-print btn-print"><i class="fa-solid fa-print"></i> Cetak Ulang Dokumen</button>

    <div class="kertas">
        <div class="kop-surat">
            <img src="<?= BASE_URL ?>/assets/img/logo_loteng.png" class="logo-kop" alt="Logo Kabupaten">
            <div class="teks-kop">
                <h3>PEMERINTAH KABUPATEN LOMBOK TENGAH</h3>
                <h3>KECAMATAN PRAYA BARAT DAYA</h3>
                <h1>DESA SERAGE</h1>
            </div>
        </div>

        <?php 
        // Panggil template spesifik berdasarkan jenis surat yang tersimpan di arsip
        $template_file = "templates/" . strtolower($jenis_surat) . ".php";
        if (file_exists($template_file)) { 
            include $template_file; 
        } else {
            // Fallback jika nama file template menggunakan format underscore (misal: ahli_waris.php)
            $template_file_alt = "templates/" . str_replace(' ', '_', strtolower($jenis_surat)) . ".php";
            if (file_exists($template_file_alt)) {
                include $template_file_alt;
            } else {
                echo "<p style='text-align:center; color:red;'>File template untuk jenis surat ini tidak ditemukan.</p>";
            }
        }
        ?>

        <div class="ttd-container">
            <div>Serage, <?= format_tanggal_indo($surat['tgl_terbit']) ?></div>
            <div>Kepala Desa Serage</div>
            <div class="ttd-nama"><?= $nama_kades ?></div>
        </div>
    </div>
</body>
</html>