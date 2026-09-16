<?php
// admin/cetak_surat.php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_logged_in'])) { die("Akses ditolak."); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die("Akses tidak valid."); }

$is_preview  = isset($_POST['is_preview']) && $_POST['is_preview'] === 'true';
$is_manual   = isset($_POST['mode_manual']) && $_POST['mode_manual'] == '1';

$nik_warga   = $koneksi->real_escape_string($_POST['nik'] ?? '');
$jenis_surat = $koneksi->real_escape_string($_POST['jenis_surat'] ?? '');
$keperluan   = $koneksi->real_escape_string($_POST['keperluan'] ?? ''); 
$admin_id    = $_SESSION['admin_id'] ?? 1;

// 1. AMBIL TEMPLATE & KODE SURAT DARI DATABASE
$q_template = $koneksi->query("SELECT * FROM template_surat WHERE header_surat = '$jenis_surat' LIMIT 1");
if ($q_template->num_rows == 0) {
    die("<div style='padding:20px; background:#fff; font-family:sans-serif; color:#ef4444;'><b>Error:</b> Template surat tidak ditemukan.</div>");
}
$template = $q_template->fetch_assoc();
$template_id = $template['id'];
$isi_surat = $template['isi_template'];
$kode_surat = $template['kode_surat']; // Cth: "Kesra 2. 8" atau "145"

// 2. GENERATE NOMOR SURAT LENGKAP (Kombinasi Otomatis & Manual)
$nomor_urut = !empty($_POST['nomor_urut']) ? htmlspecialchars($_POST['nomor_urut']) : '000';
$bulan_romawi = ['', 'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
$bln = $bulan_romawi[date('n')];
$thn = date('Y');

// Format: [Kode Surat]/[Nomor Urut]/DS-SRG/[Bulan Romawi]/[Tahun]
$nomor_surat_lengkap = $kode_surat . "/" . $nomor_urut . "/DS-SRG/" . $bln . "/" . $thn;

// SINTESIS INPUT DINAMIS MENJADI KALIMAT
if ($jenis_surat == 'SKU') {
    $n_usaha = $_POST['dyn_nama_usaha'] ?? ''; $j_usaha = $_POST['dyn_jenis_usaha'] ?? '';
    $keperluan = "Memiliki/mengelola usaha bernama $n_usaha yang bergerak di bidang $j_usaha. Digunakan untuk: $keperluan.";
} elseif ($jenis_surat == 'Kelahiran') {
    $n_anak = $_POST['dyn_nama_anak'] ?? ''; $t_lahir = $_POST['dyn_tempat_lahir'] ?? ''; 
    $tgl_lahir = isset($_POST['dyn_tgl_lahir']) ? format_tanggal_indo($_POST['dyn_tgl_lahir']) : ''; $a_ke = $_POST['dyn_anak_ke'] ?? '';
    $keperluan = "Telah lahir seorang anak bernama $n_anak di $t_lahir pada tanggal $tgl_lahir, anak ke-$a_ke.";
} elseif ($jenis_surat == 'Kematian') {
    $tgl_m = isset($_POST['dyn_tgl_meninggal']) ? format_tanggal_indo($_POST['dyn_tgl_meninggal']) : ''; 
    $tmpt_m = $_POST['dyn_tempat_meninggal'] ?? ''; $sebab = $_POST['dyn_penyebab'] ?? '';
    $keperluan = "Meninggal dunia pada tanggal $tgl_m di $tmpt_m dikarenakan $sebab.";
} elseif ($jenis_surat == 'Pindah') {
    $almt = $_POST['dyn_alamat_tujuan'] ?? ''; $alsn = $_POST['dyn_alasan'] ?? '';
    $keperluan = "Pindah ke alamat: $almt. Alasan: $alsn.";
} elseif ($jenis_surat == 'Ahli Waris') {
    $alm = $_POST['dyn_nama_alm'] ?? '';
    $keperluan = "Ahli waris yang sah dari Almarhum/ah $alm untuk keperluan: $keperluan.";
} elseif ($jenis_surat == 'Tanah') {
    $ltk = $_POST['dyn_letak_tanah'] ?? ''; $luas = $_POST['dyn_luas_tanah'] ?? ''; $bts = $_POST['dyn_batas'] ?? '';
    $keperluan = "Tanah di $ltk seluas $luas. Batas: $bts.";
} elseif ($jenis_surat == 'Kehilangan') {
    $brg = $_POST['dyn_barang'] ?? '';
    $keperluan = "Kehilangan dokumen/barang berupa: $brg.";
} elseif ($jenis_surat == 'Beda Nama') {
    $dok = $_POST['dyn_dokumen'] ?? ''; $slh = $_POST['dyn_nama_salah'] ?? '';
    $keperluan = "Perbedaan nama pada dokumen $dok tertulis $slh.";
} elseif ($jenis_surat == 'Pengantar Nikah') {
    $psg = $_POST['dyn_pasangan'] ?? ''; $asal = $_POST['dyn_asal_pasangan'] ?? '';
    $keperluan = "Pernikahan dengan calon pasangan $psg asal $asal.";
} elseif ($jenis_surat == 'Penghasilan') {
    $nom = $_POST['dyn_nominal'] ?? '';
    $keperluan = "Penghasilan sebesar $nom per bulan untuk keperluan: $keperluan.";
}

// 3. TARIK DATA WARGA (Sesuai Struktur Variabel $warga di Template)
$warga = [];
$penduduk_id = NULL;

if ($is_manual) {
    // Jika admin mengisi NIK di form manual, gunakan itu. Jika kosong, fallback ke form NIK utama.
    $warga['nik'] = !empty($_POST['nik_manual']) ? htmlspecialchars($_POST['nik_manual']) : $nik_warga;
    $warga['nama_lengkap'] = $_POST['nama_manual'] ?? '-';
    $warga['tempat_lahir'] = $_POST['tempat_manual'] ?? '-';
    $warga['tgl_lahir']    = $_POST['tgl_manual'] ?? date('Y-m-d');
    $warga['jenis_kelamin']= $_POST['jk_manual'] ?? '-';
    $warga['pekerjaan']    = $_POST['pekerjaan_manual'] ?? '-';
    $warga['nama_dusun']   = $_POST['dusun_manual'] ?? '-';
    $warga['rt'] = $_POST['rt_manual'] ?? '-';
    $warga['rw'] = $_POST['rw_manual'] ?? '-';
    $warga['agama'] = $_POST['agama_manual'] ?? 'Islam';
    $warga['status_perkawinan'] = $_POST['status_kawin_manual'] ?? 'Belum Kawin';
    $warga['kewarganegaraan'] = 'WNI';
} else {
    $stmt = $koneksi->prepare("SELECT * FROM penduduk WHERE nik = ? LIMIT 1");
    $stmt->bind_param("s", $nik_warga);
    $stmt->execute();
    $result_warga = $stmt->get_result();
    
    if ($result_warga->num_rows === 0) { 
        die("<div style='padding:20px; background:#fff; font-family:sans-serif; color:#ef4444;'>Data warga dengan NIK $nik_warga tidak ditemukan. Gunakan Mode Manual.</div>"); 
    }
    $warga = $result_warga->fetch_assoc();
    $penduduk_id = $warga['id'];
    $stmt->close();
}

// 4. TARIK DATA KADES (Untuk template SKTM & Domisili)
$q_kades = $koneksi->query("SELECT nama_pejabat FROM struktur_organisasi WHERE jabatan = 'kepala desa' LIMIT 1");
$data_kades = $q_kades->fetch_assoc();
$nama_kades = $data_kades ? strtoupper($data_kades['nama_pejabat']) : "HERMAN YADI, S. Adm";
$nik_kades = "5202041234560001"; 
$jabatan_kades = "KEPALA DESA";

// 5. MERENDER ISI SURAT LANGSUNG DARI FILE NATIVE
$nama_file_template = str_replace(' ', '_', strtolower($jenis_surat)) . '.php';
$path_template = "templates/" . $nama_file_template;

if (!file_exists($path_template)) {
    die("<div style='padding:20px; background:#fff; font-family:sans-serif; color:#ef4444;'><b>Error:</b> File template <b>$nama_file_template</b> tidak ditemukan di direktori templates/.</div>");
}

ob_start();
include $path_template;
$html_isi_surat = ob_get_clean();

// 6. SUSUN STRUKTUR DOKUMEN HTML (Kop & TTD)
$kop_surat = '
<div style="border-bottom: 3px solid #000; padding-bottom: 2px; margin-bottom: 20px;">
    <div style="border-bottom: 1px solid #000; padding-bottom: 10px; display: flex; align-items: center;">
        <div style="width: 80px; margin-right: 20px;">
            <img src="'.BASE_URL.'/assets/img/logo.png" alt="Logo" style="width: 100%; height: auto;">
        </div>
        <div style="flex: 1; text-align: center; padding-right: 100px;">
            <h3 style="margin: 0; font-size: 13pt; font-weight: normal;">PEMERINTAH KABUPATEN LOMBOK TENGAH</h3>
            <h3 style="margin: 0; font-size: 13pt; font-weight: normal;">KECAMATAN PRAYA BARAT DAYA</h3>
            <h1 style="margin: 0; font-size: 14pt; font-weight: normal;">DESA SERAGE</h1>
        </div>
    </div>
</div>';

$ttd_surat = '
<div style="width: 250px; float: right; text-align: center; margin-top: 30px;">
    <div>Serage, '.format_tanggal_indo(date('Y-m-d')).'</div>
    <div>Kepala Desa Serage</div>
    <div style="margin-top: 80px; font-weight: bold;">(<span style="text-decoration: underline;">'.$nama_kades.'</span>)</div>
</div>
<div style="clear: both;"></div>';

$dokumen_lengkap = $kop_surat . $html_isi_surat . $ttd_surat;

// 7. OUTPUT LIVE PREVIEW (Modal iFrame)
if ($is_preview) {
    echo "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5; color: #000; margin: 0; padding: 20px; background: transparent; display: flex; justify-content: center; }
            .kertas { background: #fff; width: 100%; max-width: 21cm; min-height: 29.7cm; padding: 2cm; box-sizing: border-box; box-shadow: 0 5px 15px rgba(0,0,0,0.2); border-radius: 4px; }
            .judul-surat { text-align: center; margin-bottom: 25px; line-height: 1.3; }
            .judul-surat .judul { text-decoration: underline; font-weight: bold; font-size: 12pt; }
            .judul-surat .nomor { font-weight: normal; font-size: 11pt; }
            .tabel-identitas { width: 100%; margin-left: 0; margin-bottom: 15px; border-collapse: collapse; }
            .tabel-identitas td { vertical-align: top; padding: 2px 0; }
            .paragraf-indent { text-indent: 40px; text-align: justify; margin-bottom: 15px; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class='kertas'>$dokumen_lengkap</div>
    </body>
    </html>";
    exit;
}

// 8. SIMPAN TRANSAKSI & CETAK FINAL
$template_id = 0;
$stmt_tpl = $koneksi->prepare("SELECT id FROM template_surat WHERE header_surat = ? LIMIT 1");
$stmt_tpl->bind_param("s", $jenis_surat);
$stmt_tpl->execute();
$res_tpl = $stmt_tpl->get_result();
if($res_tpl->num_rows > 0) $template_id = $res_tpl->fetch_assoc()['id'];
$stmt_tpl->close();

$data_dinamis = [
    'jenis' => $jenis_surat,
    'keperluan' => $keperluan,
    'is_manual' => $is_manual,
    'data_warga' => $is_manual ? $warga : null
];
$json_dinamis = json_encode($data_dinamis);
$tgl_terbit = date('Y-m-d');
$db_penduduk_id = $is_manual ? "NULL" : $penduduk_id;

$query_insert = "INSERT INTO transaksi_surat (nomor_surat, template_id, penduduk_id, data_dinamis, tgl_terbit, file_pdf_path, petugas_id) 
                 VALUES (?, ?, ?, ?, ?, '', ?)";
$stmt_ins = $koneksi->prepare($query_insert);
$stmt_ins->bind_param("siissi", $nomor_surat_lengkap, $template_id, $db_penduduk_id, $json_dinamis, $tgl_terbit, $admin_id);
$stmt_ins->execute();
$stmt_ins->close();

$nama_pemohon = $warga['nama_lengkap'] ?? 'Warga';
catat_log($koneksi, $admin_id, "Cetak Surat", "Menerbitkan surat jenis [$jenis_surat] No: $nomor_surat_lengkap untuk: $nama_pemohon");
?>
<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Cetak Surat - <?= htmlspecialchars($nomor_surat_lengkap) ?></title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5; color: #000; background: #525659; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .kertas { background: #fff; width: 21cm; min-height: 29.7cm; padding: 2cm 2.5cm; box-sizing: border-box; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .judul-surat { text-align: center; margin-bottom: 25px; line-height: 1.3; }
        .judul-surat .judul { text-decoration: underline; font-weight: bold; font-size: 12pt; }
        .judul-surat .nomor { font-weight: normal; font-size: 11pt; }
        .tabel-identitas { width: 100%; margin-left: 0; margin-bottom: 15px; border-collapse: collapse; }
        .tabel-identitas td { vertical-align: top; padding: 2px 0; }
        .paragraf-indent { text-indent: 40px; text-align: justify; margin-bottom: 15px; line-height: 1.5; }
        @media print { 
            body { background: #fff; padding: 0; display: block; }
            .kertas { box-shadow: none; width: auto; min-height: auto; padding: 2cm 2.5cm; }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="kertas">
        <?= $dokumen_lengkap ?>
    </div>
</body>
</html>