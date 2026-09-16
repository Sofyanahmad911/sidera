<?php
session_start();
require_once '../config/koneksi.php';
// Load DOMPDF melalui Composer
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Pastikan request dari POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Gunakan string class name agar IDE/static analysis tidak memaksa resolusi type saat library belum terdeteksi
    if (!class_exists('Dompdf\\Dompdf') || !class_exists('Dompdf\\Options')) {
        die("Instalasi DOMPDF belum lengkap. Jalankan composer install.");
    }
    $jenis_surat = $_POST['jenis_surat']; // Kode surat (Misal: SKTM)
    $penduduk_id = $_POST['penduduk_id'];

    // 1. Ambil Data Warga
    $q_warga = $koneksi->prepare("SELECT * FROM penduduk WHERE id = ?");
    $q_warga->bind_param("i", $penduduk_id);
    $q_warga->execute();
    $warga = $q_warga->get_result()->fetch_assoc();

    // 2. Ambil Desain Template Surat dari Database
    $q_temp = $koneksi->prepare("SELECT * FROM template_surat WHERE kode_surat = ?");
    $q_temp->bind_param("s", $jenis_surat);
    $q_temp->execute();
    $template = $q_temp->get_result()->fetch_assoc();

    if (!$warga || !$template) {
        die("Data warga atau template belum tersedia. Silakan buat template $jenis_surat di menu Kelola Template.");
    }

    // 3. Mapping Data (String Replacement)
    // Menyiapkan array data untuk mengganti tag {{nama_lengkap}} dll di dalam desain HTML
    $html_final = $template['isi_template'];

    $data_mapping = [
        '{{nama_lengkap}}' => strtoupper($warga['nama_lengkap']),
        '{{nik}}' => $warga['nik'],
        '{{tempat_lahir}}' => ucwords($warga['tempat_lahir']),
        '{{tgl_lahir}}' => date('d-m-Y', strtotime($warga['tgl_lahir'])),
        '{{umur}}' => $warga['umur'],
        '{{jenis_kelamin}}' => $warga['jenis_kelamin'] == 'l' ? 'Laki-laki' : 'Perempuan',
        '{{pekerjaan}}' => $warga['pekerjaan'],
        '{{agama}}' => $warga['agama'],
        '{{rt}}' => $warga['rt'],
        '{{rw}}' => $warga['rw'],
        '{{tanggal_cetak}}' => date('d F Y')
    ];

    // Ganti semua placeholder dengan data warga
    foreach ($data_mapping as $placeholder => $nilai) {
        $html_final = str_replace($placeholder, $nilai, $html_final);
    }

    // 4. Proses Rendering PDF menggunakan DOMPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    // Setting font default untuk format resmi pemerintahan
    $options->set('defaultFont', 'Times-Roman'); 

    $dompdf = new Dompdf($options);

    // Membungkus HTML dengan struktur dasar web agar style CSS terbaca
    $html_render = '
    <html>
    <head>
        <style>
            body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; color: #000; }
            table { width: 100%; border-collapse: collapse; }
            td { vertical-align: top; padding: 2px 0; }
        </style>
    </head>
    <body>' . $html_final . '</body>
    </html>';

    $dompdf->loadHtml($html_render);

    // Setup ukuran kertas F4 (Folio) / A4 untuk surat resmi
    $dompdf->setPaper('A4', 'portrait');

    // Render ke PDF
    $dompdf->render();

    // 5. Konfigurasi Penyimpanan Fisik & Pencatatan Database
    $nomor_surat = "470/" . time() . "/DS/" . date('Y'); // Bisa disesuaikan dengan format desa
    $tgl_terbit = date('Y-m-d');
    $nama_file = "Surat_{$jenis_surat}_{$warga['nik']}_" . time() . ".pdf";
    $path_simpan = "../assets/uploads/arsip/" . $nama_file;
    
    // Pastikan folder arsip tersedia
    if (!is_dir('../assets/uploads/arsip/')) {
        mkdir('../assets/uploads/arsip/', 0777, true);
    }

    // Simpan file PDF secara fisik ke server
    file_put_contents($path_simpan, $dompdf->output());

    // Catat ke tabel transaksi_surat untuk kebutuhan E-Arsip
    $q_arsip = $koneksi->prepare("INSERT INTO transaksi_surat (nomor_surat, template_id, penduduk_id, data_dinamis, tgl_terbit, file_pdf_path, petugas_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Konversi data tambahan (keperluan, nama usaha) menjadi JSON
    $data_dinamis = json_encode(['keperluan' => $_POST['keperluan'] ?? '']);
    $petugas_id = $_SESSION['admin_id']; // Mengambil ID admin yang sedang login
    
    $q_arsip->bind_param("siisssi", $nomor_surat, $template['id'], $penduduk_id, $data_dinamis, $tgl_terbit, $nama_file, $petugas_id);
    $q_arsip->execute();

    // 6. Output PDF (Stream langsung ke Browser)
    $dompdf->stream($nama_file, array("Attachment" => false));
} else {
    echo "Akses ditolak.";
}
?>