<?php
// admin/export_arsip.php
require_once '../config/koneksi.php';

// 1. Proteksi Akses
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit('Akses Ditolak.');
}

// 2. Tangkap Parameter Filter
$search = isset($_GET['search']) ? $koneksi->real_escape_string($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? $koneksi->real_escape_string($_GET['kategori']) : '';

// 3. Susun Klausa WHERE persis seperti di UI
$where_clause = "WHERE 1=1 ";
if ($search != '') { $where_clause .= "AND judul_arsip LIKE '%$search%' "; }
if ($kategori != '') { $where_clause .= "AND kategori_arsip = '$kategori' "; }

// 4. Set Header untuk Force Download CSV
$filename = "Export_Arsip_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 5. Buka Output Stream & Tambahkan BOM UTF-8
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 6. Tulis Header Kolom
fputcsv($output, ['No', 'Judul Arsip', 'Kategori', 'Nomor Dokumen', 'Tanggal Dokumen', 'Keterangan', 'Tanggal Upload']);

// 7. Query Data
$query = "SELECT judul_arsip, kategori_arsip, nomor_dokumen, tgl_dokumen, keterangan, uploaded_at 
          FROM arsip_desa $where_clause ORDER BY tgl_dokumen DESC, uploaded_at DESC";
$result = $koneksi->query($query);

// 8. Tulis Baris Data
$no = 1;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $no++,
            $row['judul_arsip'],
            $row['kategori_arsip'],
            $row['nomor_dokumen'] ? $row['nomor_dokumen'] : '-',
            format_tanggal_indo($row['tgl_dokumen']), // Dari functions.php
            $row['keterangan'],
            $row['uploaded_at']
        ]);
    }
} else {
    fputcsv($output, ['Tidak ada data arsip yang ditemukan.']);
}

// 9. Catat Log Aktivitas
$detail_log = "Export CSV Arsip.";
if ($search != '') $detail_log .= " Keyword: '$search'.";
if ($kategori != '') $detail_log .= " Kategori: '$kategori'.";
catat_log($koneksi, $_SESSION['admin_id'], 'Export Data', $detail_log);

// 10. Bersihkan
fclose($output);
$koneksi->close();
exit;
?>